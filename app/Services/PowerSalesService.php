<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
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

        // Campos "automaticos" que Proteo calcula por logica propia y por eso NUNCA
        // viven en field_mapping (ni siquiera aparecen como fila). Se agregan aqui
        // solo para que la vista de mapeo no los muestre como "sin mapear".
        foreach ($this->articuloAutoRules() as $auto) {
            $groups['articulo']['rows']->push($auto);
        }

        return $groups;
    }

    protected function isPriceListField(string $psField): bool
    {
        return str_starts_with($psField, 'PL_');
    }

    /**
     * Filas sinteticas (no vienen de la BD) que describen campos con logica automatica
     * hardcodeada en Proteo. Usadas para mostrar en /powersales/mapeo y para calcular
     * el valor real en buildArticuloPayload().
     */
    protected function articuloAutoRules(): array
    {
        return [
            (object) [
                'ps_field'    => 'BrandId',
                'erp_column'  => null,
                'fixed_value' => null,
                'auto_note'   => 'Automático — primeros 5 caracteres del SKU',
            ],
        ];
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
        $payload = $this->buildPayload('articulo', $branchData, fn ($f) => !$this->isPriceListField($f));

        // BrandId es requerido en PowerSales pero no vive en field_mapping (regla
        // automatica de Proteo: primeros 5 caracteres del SKU). Sin esto, PowerSales
        // lo defaultea a 0 y el producto no aparece filtrado por marca.
        $sku = $branchData['Clave_Articulo'] ?? null;
        if ($sku !== null && $sku !== '') {
            $payload['BrandId'] = mb_substr((string) $sku, 0, 5);
        }

        return $payload;
    }

    /**
     * Payload de lista de precios (entity=articulo, solo PL_*) a partir de datos formato sucursal.
     * Publico para reutilizar en exportaciones (ej. DBMasterController).
     */
    public function buildPriceListPayload(array $branchData): array
    {
        return $this->buildPayload('articulo', $branchData, fn ($f) => $this->isPriceListField($f));
    }

    /**
     * Payload de inventario (entity=articuloalm) a partir de una fila de la tabla `articuloalm`
     * de sucursal (ya viene en PascalCase real, ej. Clave_Articulo, Almacen, Existencia_Fisica).
     * Publico para reutilizar en exportaciones (ej. InventarioController).
     */
    public function buildInventarioPayload(array $source): array
    {
        return $this->buildPayload('articuloalm', $source);
    }

    protected function post(string $entity, string $endpoint, array $payload, string $refLabel): void
    {
        $logger = $this->logger();

        try {
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
     * Igual que post() pero para endpoints que reciben varias filas en un solo POST
     * (ej. /pricelists, /pricelistsdetails), donde $rows ya viene armado como lista de dicts.
     */
    protected function postBatch(string $entity, string $endpoint, array $rows, string $refLabel): void
    {
        $logger = $this->logger();

        try {
            if (empty($rows)) {
                $logger->warning("PowerSales {$endpoint} [{$refLabel}]: sin filas, no se envia.");
                $this->saveAudit($entity, $endpoint, $refLabel, [], false, null, 'Sin filas para enviar.');
                return;
            }

            $logger->info("PowerSales {$endpoint} [{$refLabel}] payload enviado: " . json_encode($rows));

            $response = Http::withToken($this->token())
                ->timeout(15)
                ->post($this->baseUrl() . $endpoint, ['data' => $rows]);

            if ($response->successful()) {
                $logger->info("PowerSales {$endpoint} [{$refLabel}] OK. Body: " . $response->body());
            } else {
                $logger->error("PowerSales {$endpoint} [{$refLabel}] FALLO {$response->status()}: {$response->body()}");
            }

            $this->saveAudit($entity, $endpoint, $refLabel, $rows, $response->successful(), $response->status(), $response->body());
        } catch (Throwable $e) {
            $logger->error("PowerSales {$endpoint} [{$refLabel}] EXCEPCION: " . $e->getMessage());
            $this->saveAudit($entity, $endpoint, $refLabel, $rows, false, null, 'EXCEPCION: ' . $e->getMessage());
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
        try {
            $payload = $this->buildArticuloPayload($branchData);
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /products [{$ref}] EXCEPCION armando payload: " . $e->getMessage());
            $this->saveAudit('articulo', '/products', (string) $ref, [], false, null, 'EXCEPCION armando payload: ' . $e->getMessage());
            return;
        }
        $this->post('articulo', '/products', $payload, (string) $ref);
    }

    /**
     * Headers de las 4 listas de precio que PowerSales espera en /pricelists.
     * Nombres fijos (no vienen de field_mapping): Precio_Lista, Precio_Venta, Precio_Especial, Precio4.
     */
    protected function priceListDefinitions(): array
    {
        return [
            ['Name' => 'Precio_Lista',    'IsActive' => 1, 'IsDefault' => 0, 'PriceListNumber' => 'Precio_Lista'],
            ['Name' => 'Precio_Venta',    'IsActive' => 1, 'IsDefault' => 0, 'PriceListNumber' => 'Precio_Venta'],
            ['Name' => 'Precio_Especial', 'IsActive' => 1, 'IsDefault' => 0, 'PriceListNumber' => 'Precio_Especial'],
            ['Name' => 'Precio4',         'IsActive' => 1, 'IsDefault' => 0, 'PriceListNumber' => 'Precio4'],
        ];
    }

    /**
     * Registra las 4 listas de precio en PowerSales (POST /pricelists). Se cachea 1 dia
     * para no re-enviar en cada alta/edicion de articulo (los headers casi nunca cambian).
     * No lanza excepciones.
     */
    public function syncPriceListHeaders(): void
    {
        $cacheKey = 'powersales_pricelists_registered';

        try {
            if (Cache::has($cacheKey)) {
                return;
            }
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /pricelists: no se pudo leer cache: " . $e->getMessage());
        }

        $this->postBatch('pricelist', '/pricelists', $this->priceListDefinitions(), 'headers');

        try {
            Cache::put($cacheKey, true, now()->addDay());
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /pricelists: no se pudo guardar cache: " . $e->getMessage());
        }
    }

    /**
     * Envia a /pricelistsdetails el precio de este articulo en cada lista mapeada (PL_*).
     * $branchData: array en formato sucursal (PascalCase), ej. Clave_Articulo, Precio_Lista...
     * Cost sale siempre de Costo_Ult_Compra (misma columna para las 4 listas, no configurable).
     * No lanza excepciones.
     */
    public function syncArticuloPriceListDetails(array $branchData): void
    {
        $sku = $branchData['Clave_Articulo'] ?? null;
        if ($sku === null || $sku === '') {
            return;
        }

        try {
            $cost = $branchData['Costo_Ult_Compra'] ?? null;

            $rows = [];
            foreach ($this->mappingRows('articulo') as $row) {
                if (!$this->isPriceListField($row->ps_field)) {
                    continue;
                }
                if ($row->erp_column === null || $row->erp_column === '' || !array_key_exists($row->erp_column, $branchData)) {
                    continue;
                }
                $price = $branchData[$row->erp_column];
                if ($price === null || $price === '') {
                    continue;
                }

                $rows[] = [
                    'ProductId'   => $sku,
                    'PriceListId' => substr($row->ps_field, 3), // quita el prefijo "PL_"
                    'Cost'        => $cost,
                    'Price'       => $price,
                    'IsActive'    => 1,
                ];
            }
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /pricelistsdetails [{$sku}] EXCEPCION armando payload: " . $e->getMessage());
            $this->saveAudit('pricelist', '/pricelistsdetails', (string) $sku, [], false, null, 'EXCEPCION armando payload: ' . $e->getMessage());
            return;
        }

        $this->postBatch('pricelist', '/pricelistsdetails', $rows, (string) $sku);
    }

    /**
     * $branchData: array en formato sucursal (PascalCase), ej. RFC, Razon_Social...
     * No lanza excepciones: cualquier fallo (mapeo, red, API) se loguea en storage/logs/powersales.log.
     */
    public function syncCliente(array $branchData): void
    {
        $ref = $branchData['RFC'] ?? 'sin-rfc';
        try {
            $payload = $this->buildPayload('cliente', $branchData);
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /customers [{$ref}] EXCEPCION armando payload: " . $e->getMessage());
            $this->saveAudit('cliente', '/customers', (string) $ref, [], false, null, 'EXCEPCION armando payload: ' . $e->getMessage());
            return;
        }
        $this->post('cliente', '/customers', $payload, (string) $ref);
    }
}
