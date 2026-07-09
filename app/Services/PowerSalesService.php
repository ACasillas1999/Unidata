<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envia altas/ediciones de articulos y clientes a PowerSales,
 * armando el payload segun el mapeo de campos guardado en proteo_db.field_mapping.
 */
class PowerSalesService
{
    protected function baseUrl(): string
    {
        return rtrim((string) config('services.powersales.base_url'), '/');
    }

    protected function token(): string
    {
        return (string) config('services.powersales.token');
    }

    protected function logger()
    {
        return Log::build([
            'driver' => 'single',
            'path'   => storage_path('logs/powersales.log'),
        ]);
    }

    /**
     * Filas crudas de field_mapping para una entidad (ps_field/erp_column/fixed_value).
     */
    public function mappingRows(string $entity): \Illuminate\Support\Collection
    {
        return DB::connection('proteo_db')
            ->table('field_mapping')
            ->where('entity', $entity)
            ->orderBy('ps_field')
            ->get();
    }

    /**
     * Igual que mappingRows() pero agrupado en las 4 categorias visuales de Proteo:
     * los campos "PL_*" de entity=articulo son su propia categoria (Listas de Precios),
     * aunque en la tabla comparten `entity=articulo` con los campos de producto.
     */
    public function mappingGroups(): array
    {
        $groups = [
            'articulo'    => ['label' => 'Artículos (productos)',       'rows' => collect()],
            'pricelist'   => ['label' => 'Listas de Precios',           'rows' => collect()],
            'articuloalm' => ['label' => 'Inventario (articuloalm)',    'rows' => collect()],
            'cliente'     => ['label' => 'Clientes (customers)',        'rows' => collect()],
        ];

        foreach ($this->mappingRows('articulo') as $row) {
            $groups[$this->isPriceListField($row->ps_field) ? 'pricelist' : 'articulo']['rows']->push($row);
        }
        $groups['articuloalm']['rows'] = $this->mappingRows('articuloalm');
        $groups['cliente']['rows']     = $this->mappingRows('cliente');

        return $groups;
    }

    protected function isPriceListField(string $psField): bool
    {
        return str_starts_with($psField, 'PL_');
    }

    /**
     * Arma el payload PowerSales a partir de datos en formato sucursal (PascalCase)
     * usando field_mapping. $source debe tener las llaves tal como existen en la
     * tabla `articulo`/`clientes` de sucursal (ej. Clave_Articulo, RFC, Razon_Social).
     * $psFieldFilter opcional: solo incluye filas cuyo ps_field pase el filtro.
     */
    protected function buildPayload(string $entity, array $source, ?callable $psFieldFilter = null): array
    {
        $payload = [];
        foreach ($this->mappingRows($entity) as $row) {
            if ($psFieldFilter !== null && !$psFieldFilter($row->ps_field)) {
                continue;
            }
            if ($row->erp_column !== null && $row->erp_column !== '' && array_key_exists($row->erp_column, $source)) {
                $payload[$row->ps_field] = $source[$row->erp_column];
            } elseif ($row->fixed_value !== null && $row->fixed_value !== '') {
                $payload[$row->ps_field] = $row->fixed_value;
            }
        }

        return $payload;
    }

    /**
     * Payload de producto (entity=articulo, excluyendo PL_*) a partir de datos formato sucursal.
     * Publico para reutilizar en exportaciones (ej. DBMasterController).
     */
    public function buildArticuloPayload(array $branchData): array
    {
        return $this->buildPayload('articulo', $branchData, fn ($f) => !$this->isPriceListField($f));
    }

    /**
     * Payload de lista de precios (entity=articulo, solo PL_*) a partir de datos formato sucursal.
     * Publico para reutilizar en exportaciones (ej. DBMasterController).
     */
    public function buildPriceListPayload(array $branchData): array
    {
        return $this->buildPayload('articulo', $branchData, fn ($f) => $this->isPriceListField($f));
    }

    protected function post(string $entity, string $endpoint, array $source, string $refLabel, ?callable $psFieldFilter = null): void
    {
        $logger = $this->logger();
        $payload = [];

        try {
            $payload = $this->buildPayload($entity, $source, $psFieldFilter);

            if (empty($payload)) {
                $logger->warning("PowerSales {$endpoint} [{$refLabel}]: payload vacio, no se envia (revisar field_mapping).");
                $this->saveAudit($entity, $endpoint, $refLabel, $payload, false, null, 'Payload vacio (revisar field_mapping).');
                return;
            }

            $logger->info("PowerSales {$endpoint} [{$refLabel}] payload enviado: " . json_encode($payload));

            $response = Http::withToken($this->token())
                ->timeout(15)
                ->post($this->baseUrl() . $endpoint, ['data' => [$payload]]);

            if ($response->successful()) {
                $logger->info("PowerSales {$endpoint} [{$refLabel}] OK. Body: " . $response->body());
            } else {
                $logger->error("PowerSales {$endpoint} [{$refLabel}] FALLO {$response->status()}: {$response->body()}");
            }

            $this->saveAudit($entity, $endpoint, $refLabel, $payload, $response->successful(), $response->status(), $response->body());
        } catch (Throwable $e) {
            $logger->error("PowerSales {$endpoint} [{$refLabel}] EXCEPCION: " . $e->getMessage());
            $this->saveAudit($entity, $endpoint, $refLabel, $payload, false, null, 'EXCEPCION: ' . $e->getMessage());
        }
    }

    /**
     * Guarda un registro de auditoria en powersales_sync_logs para poder revisarlo desde la UI.
     * Nunca lanza excepciones (mismo criterio que el resto del servicio).
     */
    protected function saveAudit(string $entity, string $endpoint, string $refLabel, array $payload, bool $success, ?int $statusCode, ?string $responseBody): void
    {
        try {
            DB::connection('mysql')->table('powersales_sync_logs')->insert([
                'entity'        => $entity,
                'endpoint'      => $endpoint,
                'referencia'    => $refLabel,
                'payload'       => json_encode($payload),
                'success'       => $success,
                'status_code'   => $statusCode,
                'response_body' => $responseBody,
                'created_at'    => now(),
            ]);
        } catch (Throwable $e) {
            $this->logger()->error("No se pudo guardar auditoria PowerSales: " . $e->getMessage());
        }
    }

    /**
     * $branchData: array en formato sucursal (PascalCase), ej. Clave_Articulo, Descripcion, Costo_Promedio...
     * No lanza excepciones: cualquier fallo (mapeo, red, API) se loguea en storage/logs/powersales.log.
     */
    public function syncArticulo(array $branchData): void
    {
        $ref = $branchData['Clave_Articulo'] ?? 'sin-clave';
        $this->post('articulo', '/products', $branchData, (string) $ref, fn ($f) => !$this->isPriceListField($f));
    }

    /**
     * $branchData: array en formato sucursal (PascalCase), ej. RFC, Razon_Social...
     * No lanza excepciones: cualquier fallo (mapeo, red, API) se loguea en storage/logs/powersales.log.
     */
    public function syncCliente(array $branchData): void
    {
        $ref = $branchData['RFC'] ?? 'sin-rfc';
        $this->post('cliente', '/customers', $branchData, (string) $ref);
    }
}
