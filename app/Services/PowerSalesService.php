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
            'articulo'     => ['label' => 'Artículos (productos)',       'rows' => collect()],
            'pricelist'    => ['label' => 'Listas de Precios',           'rows' => collect()],
            'discountlist' => ['label' => 'Listas de Descuentos',        'rows' => collect()],
            'articuloalm'  => ['label' => 'Inventario (articuloalm)',    'rows' => collect()],
            'cliente'      => ['label' => 'Clientes (customers)',        'rows' => collect()],
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

        foreach ($this->discountListAutoRules() as $auto) {
            $groups['discountlist']['rows']->push($auto);
        }

        foreach ($this->clienteAutoRules() as $auto) {
            $groups['cliente']['rows']->push($auto);
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

    protected function clienteAutoRules(): array
    {
        return [
            (object) [
                'ps_field'    => 'PriceListNumber',
                'erp_column'  => null,
                'fixed_value' => 'Precio_Venta',
                'auto_note'   => 'Automático — Requerido por PowerSales, por defecto "Precio_Venta" si no está mapeado',
            ],
            (object) [
                'ps_field'    => 'BranchId',
                'erp_column'  => null,
                'fixed_value' => '9',
                'auto_note'   => 'Automático — Requerido por PowerSales, por defecto 9 (AIESA)',
            ],
            (object) [
                'ps_field'    => 'CallDay / DayOffSet / DefaultPaymentTypeId',
                'erp_column'  => 'Dias_Credito',
                'fixed_value' => '0 / 1',
                'auto_note'   => 'Automático — Valores enteros por defecto para evitar restricciones no-null en PowerSales',
            ],
            (object) [
                'ps_field'    => 'Address1 / InvoiceAddress',
                'erp_column'  => 'Calle, Exterior, Interior, Colonia, Cod_Postal',
                'fixed_value' => null,
                'auto_note'   => 'Automático — Concatena Calle, Núm. Exterior/Interior, Colonia y CP para PowerSales',
            ],
        ];
    }

    protected function discountListAutoRules(): array
    {
        return [
            (object) [
                'ps_field'    => 'DiscountList 1 (Desc_Precio_Venta)',
                'erp_column'  => 'des_precio_venta',
                'fixed_value' => null,
                'auto_note'   => 'Columna ERP: des_precio_venta (% Desc. Venta)',
            ],
            (object) [
                'ps_field'    => 'DiscountList 2 (Desc_Precio_Espec)',
                'erp_column'  => 'desc_precio_espec',
                'fixed_value' => null,
                'auto_note'   => 'Columna ERP: desc_precio_espec (% Desc. Especial)',
            ],
            (object) [
                'ps_field'    => 'DiscountList 3 (Desc_Precio4)',
                'erp_column'  => 'desc_precio4',
                'fixed_value' => null,
                'auto_note'   => 'Columna ERP: desc_precio4 (% Desc. 4)',
            ],
            (object) [
                'ps_field'    => 'DiscountList 4 (Descuento Gerente)',
                'erp_column'  => 'desc_proveedor',
                'fixed_value' => null,
                'auto_note'   => 'Columna ERP: desc_proveedor (Desc. Proveedor/Gerente)',
            ],
            (object) [
                'ps_field'    => 'DiscountList 5 (Descuento Pricing)',
                'erp_column'  => 'porcetaje_descuento',
                'fixed_value' => null,
                'auto_note'   => 'Columna ERP: porcetaje_descuento (% Descuento)',
            ],
            (object) [
                'ps_field'    => 'DiscountList 6 (Descuento Encargado Pricing)',
                'erp_column'  => null,
                'fixed_value' => '100',
                'auto_note'   => 'Fijo: 100 (Hardcodeado)',
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
            } else {
                $payload[$row->ps_field] = '';
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

            $body = $response->body();
            $json = json_decode($body, true);

            $isSuccess = $response->successful();
            if ($isSuccess && is_array($json)) {
                if (!empty($json['error']) && $json['error'] != 0) {
                    $isSuccess = false;
                } elseif (isset($json['ok']) && $json['ok'] == 0 && !empty($json['noInsert'])) {
                    $isSuccess = false;
                }
            }

            if ($isSuccess) {
                $logger->info("PowerSales {$endpoint} [{$refLabel}] OK. Body: " . $body);
            } else {
                $logger->error("PowerSales {$endpoint} [{$refLabel}] FALLO {$response->status()}: " . $body);
            }

            $this->saveAudit($entity, $endpoint, $refLabel, $payload, $isSuccess, $response->status(), $body);
        } catch (Throwable $e) {
            $logger->error("PowerSales {$endpoint} [{$refLabel}] EXCEPCION: " . $e->getMessage());
            $this->saveAudit($entity, $endpoint, $refLabel, $payload, false, null, 'EXCEPCION DE CONEXION/CODIGO: ' . $e->getMessage());
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

            $body = $response->body();
            $json = json_decode($body, true);

            $isSuccess = $response->successful();
            if ($isSuccess && is_array($json)) {
                if (!empty($json['error']) && $json['error'] != 0) {
                    $isSuccess = false;
                } elseif (isset($json['ok']) && $json['ok'] == 0 && !empty($json['noInsert'])) {
                    $isSuccess = false;
                }
            }

            if ($isSuccess) {
                $logger->info("PowerSales {$endpoint} [{$refLabel}] OK. Body: " . $body);
            } else {
                $logger->error("PowerSales {$endpoint} [{$refLabel}] FALLO {$response->status()}: " . $body);
            }

            $this->saveAudit($entity, $endpoint, $refLabel, $rows, $isSuccess, $response->status(), $body);
        } catch (Throwable $e) {
            $logger->error("PowerSales {$endpoint} [{$refLabel}] EXCEPCION: " . $e->getMessage());
            $this->saveAudit($entity, $endpoint, $refLabel, $rows, false, null, 'EXCEPCION DE CONEXION/CODIGO: ' . $e->getMessage());
        }
    }

    /**
     * Guarda un registro de auditoria en powersales_sync_logs para poder revisarlo desde la UI.
     * Nunca lanza excepciones (mismo criterio que el resto del servicio).
     */
    protected function saveAudit(string $entity, string $endpoint, string $refLabel, array $payload, bool $success, ?int $statusCode, ?string $responseBody): void
    {
        try {
            $cleanResponseBody = $responseBody !== null ? mb_substr($responseBody, 0, 60000) : null;

            DB::connection('mysql')->table('powersales_sync_logs')->insert([
                'entity'        => $entity,
                'endpoint'      => $endpoint,
                'referencia'    => $refLabel,
                'payload'       => json_encode($payload),
                'success'       => $success,
                'status_code'   => $statusCode,
                'response_body' => $cleanResponseBody,
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
        // Las listas de precios ya están creadas en PowerSales; no es necesario enviar encabezados.
        return;
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
            $mnUsd = strtoupper(trim((string) ($branchData['MN_USD'] ?? $branchData['mn_usd'] ?? 'M')));
            $currency = in_array($mnUsd, ['U', '1', 'USD'], true) ? 'USD' : 'MXN';

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
                    'Currency'    => $currency,
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
     * Headers de las 6 listas de descuento que PowerSales espera en /discountlist.
     */
    protected function discountListDefinitions(): array
    {
        return [
            ['DiscountListId' => 1, 'DiscountListNumber' => '1', 'Name' => 'Desc_Precio_Venta',           'Description' => 'Descuento Precio Venta',          'IsActive' => 1, 'CreatedBy' => 1],
            ['DiscountListId' => 2, 'DiscountListNumber' => '2', 'Name' => 'Desc_Precio_Espec',           'Description' => 'Descuento Precio Especial',       'IsActive' => 1, 'CreatedBy' => 1],
            ['DiscountListId' => 3, 'DiscountListNumber' => '3', 'Name' => 'Desc_Precio4',                'Description' => 'Descuento Precio 4',              'IsActive' => 1, 'CreatedBy' => 1],
            ['DiscountListId' => 4, 'DiscountListNumber' => '4', 'Name' => 'Descuento Gerente',           'Description' => 'Descuento Gerente',               'IsActive' => 1, 'CreatedBy' => 1],
            ['DiscountListId' => 5, 'DiscountListNumber' => '5', 'Name' => 'Descuento Pricing',           'Description' => 'Descuento Pricing',               'IsActive' => 1, 'CreatedBy' => 1],
            ['DiscountListId' => 6, 'DiscountListNumber' => '6', 'Name' => 'Descuento Encargado Pricing', 'Description' => 'Descuento Encargado Pricing',     'IsActive' => 1, 'CreatedBy' => 1],
        ];
    }

    /**
     * Registra las 6 listas de descuento en PowerSales (POST /discountlist). Se cachea 1 dia.
     */
    public function syncDiscountListHeaders(): void
    {
        // Las listas de descuentos ya están creadas en PowerSales; no es necesario enviar encabezados.
        return;
    }

    /**
     * Envia a /discountlistdetail los 6 descuentos de este articulo.
     */
    public function syncArticuloDiscountListDetails(array $branchData): void
    {
        $sku = $branchData['Clave_Articulo'] ?? $branchData['clave'] ?? null;
        if ($sku === null || $sku === '') {
            return;
        }

        try {
            $now = now()->format('Y-m-d H:i:s');

            $getVal = function ($pascalKey, $snakeKey, $default = '0') use ($branchData) {
                if (isset($branchData[$pascalKey]) && $branchData[$pascalKey] !== null && $branchData[$pascalKey] !== '') {
                    return (string) $branchData[$pascalKey];
                }
                if (isset($branchData[$snakeKey]) && $branchData[$snakeKey] !== null && $branchData[$snakeKey] !== '') {
                    return (string) $branchData[$snakeKey];
                }
                return (string) $default;
            };

            $discountMap = [
                1 => $getVal('Desc_Precio_Venta', 'des_precio_venta'),
                2 => $getVal('Desc_Precio_Espec', 'desc_precio_espec'),
                3 => $getVal('Desc_Precio4', 'desc_precio4'),
                4 => $getVal('Desc_Proveedor', 'desc_proveedor'),
                5 => $getVal('PorcentajeDescuento', 'porcetaje_descuento'),
                6 => '100',
            ];

            $rows = [];
            foreach ($discountMap as $listId => $discountVal) {
                $valFloat = round((float)$discountVal, 4);
                $discNum  = (string) $listId;
                $rows[] = [
                    'DiscountListId'     => (int) $listId,
                    'DiscountListNumber' => $discNum,
                    'ProductId'          => (string) $sku,
                    'RangeMin'           => 1,
                    'RangeMax'           => 9999,
                    'Discount'           => $valFloat,
                    'DiscountPct'        => $valFloat,
                    'DiscountAppliedTo'  => 1,
                    'Type'               => '%',
                    'IsActive'           => 1,
                    'ExternalReference'  => $discNum,
                    'CreatedBy'          => 1,
                    'CreatedDate'        => $now,
                    'ModifiedBy'         => 1,
                    'ModifiedDate'       => $now,
                ];
            }
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /discountlistdetail [{$sku}] EXCEPCION armando payload: " . $e->getMessage());
            $this->saveAudit('discountlist', '/discountlistdetail', (string) $sku, [], false, null, 'EXCEPCION armando payload: ' . $e->getMessage());
            return;
        }

        $this->postBatch('discountlist', '/discountlistdetail', $rows, (string) $sku);
    }

    /**
     * Sincroniza la ficha del cliente hacia PowerSales (endpoint /customers).
     * $branchData: array en formato sucursal (PascalCase), ej. RFC, Razon_Social...
     * $branchId (opcional): ID de la sucursal en PowerSales. Por defecto asigna 9 (AIESA).
     *
     * EXPANSION MULTI-SUCURSAL POWERSALES (FUTURO):
     * Para enviar a mas sucursales en el futuro, pasa el BranchId especifico como 2do argumento:
     * $powerSales->syncCliente($branchData, $branchIdEspecifico);
     *
     * No lanza excepciones: cualquier fallo (mapeo, red, API) se loguea en storage/logs/powersales.log.
     */
    public function syncCliente(array $branchData, ?int $branchId = null): void
    {
        $ref = $branchData['RFC'] ?? 'sin-rfc';
        try {
            $payload = $this->buildPayload('cliente', $branchData);

            // Sanear / Defaultear IsCredit (evita fallo 1048 Column 'IsCredit' cannot be null en PowerSales DB)
            if (!isset($payload['IsCredit']) || $payload['IsCredit'] === '' || $payload['IsCredit'] === null) {
                $rawCredit = $branchData['OtorgoCredito'] ?? $branchData['OtorgoCreditO'] ?? $branchData['otorgo_credito'] ?? 0;
                $payload['IsCredit'] = (int)$rawCredit;
            } else {
                $payload['IsCredit'] = (int)$payload['IsCredit'];
            }

            // Sanear IsActive: convertir 'A' -> 1, 'B'/'I' -> 0 o mantener entero
            if (isset($payload['IsActive']) && $payload['IsActive'] !== '') {
                $valActive = strtoupper((string)$payload['IsActive']);
                $payload['IsActive'] = in_array($valActive, ['A', '1', 'TRUE'], true) ? 1 : 0;
            } else {
                $payload['IsActive'] = 1;
            }

            // Armar la dirección completa en Address1 e InvoiceAddress a partir de Calle, Exterior, Interior, Colonia, Cod_Postal
            $partesDireccion = [];
            if (!empty($branchData['Calle'])) {
                $partesDireccion[] = trim((string)$branchData['Calle']);
            }
            if (!empty($branchData['Exterior'])) {
                $partesDireccion[] = '#' . trim((string)$branchData['Exterior']);
            }
            if (!empty($branchData['Interior'])) {
                $partesDireccion[] = 'Int ' . trim((string)$branchData['Interior']);
            }
            if (!empty($branchData['Colonia'])) {
                $partesDireccion[] = 'Col. ' . trim((string)$branchData['Colonia']);
            }
            if (!empty($branchData['Cod_Postal'])) {
                $partesDireccion[] = 'C.P. ' . trim((string)$branchData['Cod_Postal']);
            }

            $direccionCompleta = implode(', ', $partesDireccion);

            // Si Address1 está vacío o solo contiene la Calle, enriquecer con la dirección completa
            if (empty($payload['Address1']) || trim((string)$payload['Address1']) === trim((string)($branchData['Calle'] ?? ''))) {
                if (!empty($direccionCompleta)) {
                    $payload['Address1'] = $direccionCompleta;
                }
            }

            if (empty($payload['InvoiceAddress'])) {
                $payload['InvoiceAddress'] = !empty($payload['Address1']) ? $payload['Address1'] : $direccionCompleta;
            }

            if (empty($payload['Address2'])) {
                $municipio = $branchData['Municipio'] ?? $branchData['municipio'] ?? '';
                $ciudad    = $branchData['Ciudad'] ?? $branchData['ciudad'] ?? '';
                $payload['Address2'] = !empty($municipio) ? (string)$municipio : (!empty($ciudad) ? (string)$ciudad : ' ');
            }

            // Determinar BranchId: argumento $branchId, $payload['BranchId'], o por defecto 9 (AIESA)
            $resolvedBranchId = $branchId 
                ?? (!empty($payload['BranchId']) ? (int)$payload['BranchId'] 
                : (!empty($branchData['BranchId']) ? (int)$branchData['BranchId'] : 9));

            // Resolver StateId y CityId mediante mapeo geográfico si existen
            $resolvedStateId = null;
            $resolvedCityId  = null;

            $cveCiudad = trim((string)($branchData['Ciudad'] ?? $branchData['ciudad'] ?? ''));
            if ($cveCiudad !== '') {
                $cityMapping = \App\Models\PowerSalesMappingCiudad::where('magic_cve_ciudad', $cveCiudad)
                    ->orWhere('magic_dsc_ciudad', $cveCiudad)
                    ->first();
                if ($cityMapping && $cityMapping->ps_city_id) {
                    $resolvedCityId = (int)$cityMapping->ps_city_id;
                    if ($cityMapping->ps_state_id) {
                        $resolvedStateId = (int)$cityMapping->ps_state_id;
                    }
                }
                if (!$resolvedStateId && $cityMapping && $cityMapping->magic_cve_estado) {
                    $stateMapping = \App\Models\PowerSalesMappingEstado::where('magic_clave', $cityMapping->magic_cve_estado)->first();
                    if ($stateMapping && $stateMapping->ps_state_id) {
                        $resolvedStateId = (int)$stateMapping->ps_state_id;
                    }
                }
            }

            // Fallback por Municipio si Ciudad no resolvió
            if (!$resolvedCityId) {
                $municipio = trim((string)($branchData['Municipio'] ?? $branchData['municipio'] ?? ''));
                if ($municipio !== '') {
                    $cityMapping = \App\Models\PowerSalesMappingCiudad::where('magic_dsc_ciudad', $municipio)
                        ->orWhere('magic_cve_ciudad', $municipio)
                        ->first();
                    if ($cityMapping && $cityMapping->ps_city_id) {
                        $resolvedCityId = (int)$cityMapping->ps_city_id;
                        if (!$resolvedStateId && $cityMapping->ps_state_id) {
                            $resolvedStateId = (int)$cityMapping->ps_state_id;
                        }
                    }
                }
            }

            if (!$resolvedStateId) {
                $cveEstado = trim((string)($branchData['Estado'] ?? $branchData['estado'] ?? ''));
                if ($cveEstado !== '') {
                    $stateMapping = \App\Models\PowerSalesMappingEstado::where('magic_clave', $cveEstado)
                        ->orWhere('magic_descripcion', $cveEstado)
                        ->first();
                    if ($stateMapping && $stateMapping->ps_state_id) {
                        $resolvedStateId = (int)$stateMapping->ps_state_id;
                    }
                }
            }

            if ($resolvedStateId) {
                $payload['StateId'] = $resolvedStateId;
            }
            if ($resolvedCityId) {
                $payload['CityId'] = $resolvedCityId;
            }

            // PowerSales /customers requiere campos especificos no-nulos en su BD.
            $defaults = [
                'PriceListNumber'        => 'Precio_Venta',
                'BranchId'               => $resolvedBranchId,
                'CallDay'                => 0,
                'DayOffSet'              => 0,
                'DefaultPaymentTypeId'   => 1,
                'IsEarlyOrderEnabled'    => 0,
                'IsPOMandatory'          => 0,
                'IsPriorityEnabled'      => 0,
                'IsProspect'             => 0,
                'IsSignatureMandatory'   => 0,
                'IsTop10Enabled'         => 0,
                'IsCredit'               => 0,
                'IsActive'               => 1,
                'CustomerTypeId'         => 1,
                'ChannelId'              => 1,
                'BannerId'               => 1,
                'StateId'                => 1,
                'CityId'                 => 1,
                'LocationId'             => 1,
                'Top10Id'                => 1,
                'ParentCustomerId'       => 0,
                'PriceListId'            => 1,
                'ProductListsId'         => 1,
                'RouteId'                => 1,
                'RouteNumber'            => 1,
                'Address2'               => ' ',
                'Cellphone'              => ' ',
                'LeftStreet'             => ' ',
                'RightStreet'            => ' ',
                'UniqueId'               => ' ',
            ];

            foreach ($defaults as $key => $defaultVal) {
                if (!isset($payload[$key]) || $payload[$key] === '') {
                    $payload[$key] = $defaultVal;
                }
            }
        } catch (Throwable $e) {
            $this->logger()->error("PowerSales /customers [{$ref}] EXCEPCION armando payload: " . $e->getMessage());
            $this->saveAudit('cliente', '/customers', (string) $ref, [], false, null, 'EXCEPCION armando payload: ' . $e->getMessage());
            return;
        }
        $this->post('cliente', '/customers', $payload, (string) $ref);
    }

    /**
     * Obtiene los Estados desde la API de PowerSales (con cache de 24h).
     */
    public function fetchPowerSalesStates(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget('powersales_states');
        }

        return Cache::remember('powersales_states', now()->addHours(24), function () {
            try {
                $response = Http::withToken($this->token())
                    ->acceptJson()
                    ->timeout(15)
                    ->get($this->baseUrl() . '/state');

                if ($response->successful()) {
                    return $response->json('data') ?? [];
                }
                $this->logger()->error("PowerSales GET /state respondió {$response->status()}: " . $response->body());
            } catch (Throwable $e) {
                $this->logger()->error("PowerSales GET /state EXCEPCION: " . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Obtiene las Ciudades desde la API de PowerSales (con cache de 24h).
     */
    public function fetchPowerSalesCities(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget('powersales_cities');
        }

        return Cache::remember('powersales_cities', now()->addHours(24), function () {
            try {
                $response = Http::withToken($this->token())
                    ->acceptJson()
                    ->timeout(25)
                    ->get($this->baseUrl() . '/city');

                if ($response->successful()) {
                    return $response->json('data') ?? [];
                }
                $this->logger()->error("PowerSales GET /city respondió {$response->status()}: " . $response->body());
            } catch (Throwable $e) {
                $this->logger()->error("PowerSales GET /city EXCEPCION: " . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Sincroniza los catalogos de Magic (tabgen Tipo='ES' y ciudades) hacia las tablas locales de mapeo.
     */
    public function syncMagicGeografia(): array
    {
        $conn = null;
        try {
            $cm = app(\App\Services\BranchConnectionManager::class);
            $branches = $cm->getActiveBranches();
            foreach ($branches as $b) {
                try {
                    $c = $cm->connect($b->code);
                    $c->select("SELECT 1 FROM tabgen LIMIT 1");
                    $conn = $c;
                    break;
                } catch (Throwable $e) {
                    continue;
                }
            }
        } catch (Throwable $e) {}

        if (!$conn) {
            try {
                $conn = DB::connection('aiesa');
            } catch (Throwable $e) {}
        }

        $estadosCount = 0;
        $ciudadesCount = 0;

        if ($conn) {
            $estados = $conn->select("SELECT TRIM(Clave) as Clave, TRIM(Descripcion) as Descripcion FROM tabgen WHERE Tipo = 'ES'");
            foreach ($estados as $e) {
                \App\Models\PowerSalesMappingEstado::firstOrCreate(
                    ['magic_clave' => $e->Clave],
                    ['magic_descripcion' => $e->Descripcion]
                );
                $estadosCount++;
            }

            $ciudades = $conn->select("SELECT TRIM(Cve_Ciudad) as Cve_Ciudad, TRIM(Dsc_Ciudad) as Dsc_Ciudad, TRIM(Cve_Estado) as Cve_Estado, TRIM(Cve_Pais) as Cve_Pais FROM ciudades");
            foreach ($ciudades as $c) {
                \App\Models\PowerSalesMappingCiudad::firstOrCreate(
                    ['magic_cve_ciudad' => $c->Cve_Ciudad],
                    [
                        'magic_dsc_ciudad' => $c->Dsc_Ciudad,
                        'magic_cve_estado' => $c->Cve_Estado,
                        'magic_cve_pais'   => $c->Cve_Pais ?: 'MEX',
                    ]
                );
                $ciudadesCount++;
            }
        }

        return [
            'estados'  => $estadosCount,
            'ciudades' => $ciudadesCount,
        ];
    }

    /**
     * Normaliza un string para comparaciones difusas (sin acentos, mayúsculas, alfanumérico).
     */
    protected function normalizeGeoString(?string $str): string
    {
        if ($str === null || $str === '') {
            return '';
        }
        $clean = @iconv('UTF-8', 'ASCII//TRANSLIT', $str) ?: $str;
        $clean = strtoupper(trim($clean));
        return (string) preg_replace('/[^A-Z0-9]/', '', $clean);
    }

    /**
     * Mapeo automático de estados de Magic contra PowerSales.
     */
    public function autoMatchEstados(): int
    {
        $psStates = $this->fetchPowerSalesStates();
        if (empty($psStates)) {
            return 0;
        }

        $unmapped = \App\Models\PowerSalesMappingEstado::whereNull('ps_state_id')->get();
        $matchedCount = 0;

        foreach ($unmapped as $estado) {
            $mClave = strtoupper(trim($estado->magic_clave));
            $mNorm  = $this->normalizeGeoString($estado->magic_descripcion);

            $bestMatch = null;
            foreach ($psStates as $ps) {
                $psNorm = $this->normalizeGeoString($ps['Name'] ?? '');
                $psCol  = strtoupper(trim($ps['StatesCol'] ?? ''));

                // 1. Clave == StatesCol (ej. JAL == JAL, BCN == BCN)
                if ($mClave !== '' && $psCol !== '' && $mClave === $psCol) {
                    $bestMatch = $ps;
                    break;
                }

                // 2. Coincidencia exacta de nombre normalizado
                if ($mNorm !== '' && $mNorm === $psNorm) {
                    $bestMatch = $ps;
                    break;
                }

                // 3. Contenido mutuo (ej. COAHUILA dentro de COAHUILA DE ZARAGOZA)
                if ($mNorm !== '' && $psNorm !== '') {
                    if (str_contains($psNorm, $mNorm) || str_contains($mNorm, $psNorm)) {
                        $bestMatch = $ps;
                        break;
                    }
                }

                // 4. Similaridad fonética/texto >= 85%
                if ($mNorm !== '' && $psNorm !== '') {
                    similar_text($mNorm, $psNorm, $perc);
                    if ($perc >= 85) {
                        $bestMatch = $ps;
                        break;
                    }
                }
            }

            if ($bestMatch) {
                $estado->update([
                    'ps_state_id'     => $bestMatch['Id'],
                    'ps_state_name'   => $bestMatch['Name'] ?? '',
                    'ps_state_number' => $bestMatch['StateNumber'] ?? '',
                    'ps_states_col'   => $bestMatch['StatesCol'] ?? '',
                ]);
                $matchedCount++;
            }
        }

        return $matchedCount;
    }

    /**
     * Mapeo automático de ciudades de Magic contra PowerSales.
     */
    public function autoMatchCiudades(?string $magicCveEstado = null): int
    {
        $psCities = $this->fetchPowerSalesCities();
        if (empty($psCities)) {
            return 0;
        }

        // Agrupar ciudades de PS por StateId para búsqueda rápida O(1)
        $psCitiesByState = [];
        foreach ($psCities as $city) {
            $stateId = (int) ($city['StateId'] ?? 0);
            $psCitiesByState[$stateId][] = $city;
        }

        $query = \App\Models\PowerSalesMappingCiudad::whereNull('ps_city_id');
        if ($magicCveEstado !== null && $magicCveEstado !== '') {
            $query->where('magic_cve_estado', $magicCveEstado);
        }

        $unmapped = $query->get();
        $matchedCount = 0;

        // Cachear mapeos de estados Magic -> ps_state_id
        $stateMap = \App\Models\PowerSalesMappingEstado::whereNotNull('ps_state_id')
            ->pluck('ps_state_id', 'magic_clave')
            ->toArray();

        foreach ($unmapped as $ciudad) {
            $psStateId = $stateMap[$ciudad->magic_cve_estado] ?? null;
            if (!$psStateId || !isset($psCitiesByState[$psStateId])) {
                continue;
            }

            $cNorm = $this->normalizeGeoString($ciudad->magic_dsc_ciudad);
            if ($cNorm === '') {
                continue;
            }

            $bestMatch = null;
            foreach ($psCitiesByState[$psStateId] as $psCity) {
                $psNameNorm = $this->normalizeGeoString($psCity['Name'] ?? '');
                if ($cNorm === $psNameNorm) {
                    $bestMatch = $psCity;
                    break;
                }
                if (similar_text($cNorm, $psNameNorm, $perc) && $perc >= 90) {
                    $bestMatch = $psCity;
                    break;
                }
            }

            if ($bestMatch) {
                $ciudad->update([
                    'ps_city_id'     => $bestMatch['Id'],
                    'ps_city_name'   => $bestMatch['Name'] ?? '',
                    'ps_city_number' => $bestMatch['CityNumber'] ?? '',
                    'ps_state_id'    => $psStateId,
                ]);
                $matchedCount++;
            }
        }

        return $matchedCount;
    }
}
