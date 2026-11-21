<?php

namespace App\Support;

/**
 * Mensajes de validación en español y nombres de campo amigables para el alta
 * (ArticulosController::storeManual) y la edición (DBMasterController::updateManual)
 * de artículos. ART-11 / ED-10.
 */
class ArticuloValidacion
{
    public static function mensajes(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string'   => 'El campo :attribute debe ser texto.',
            'integer'  => 'El campo :attribute debe ser un número entero.',
            'numeric'  => 'El campo :attribute debe ser un número.',
            'boolean'  => 'El campo :attribute tiene un valor no válido.',
            'in'       => 'El campo :attribute tiene un valor no válido.',
            'array'    => 'El campo :attribute tiene un formato no válido.',
            'date'     => 'El campo :attribute no es una fecha válida.',
            'unique'   => 'Ya existe un artículo con esa :attribute.',
            'clave.regex' => 'La clave solo puede llevar letras, números, Ñ, espacios y los símbolos / . - + * " _ ( )',
            // ART-07
            'idsat.regex'         => 'La clave de producto SAT debe tener exactamente 8 dígitos (ej. 39121400).',
            'linea.regex'         => 'La línea solo puede llevar letras y números, sin espacios ni símbolos.',
            'unidad_medida.regex' => 'La unidad de medida solo puede llevar letras y números, sin espacios ni símbolos (ej. PZA).',
            'area.in'             => 'El área no existe en el catálogo de áreas de las sucursales.',
            'max'      => [
                'string'  => 'El campo :attribute no puede tener más de :max caracteres.',
                'numeric' => 'El campo :attribute no puede ser mayor que :max.',
            ],
            'min'      => [
                'string'  => 'El campo :attribute debe tener al menos :min caracteres.',
                'numeric' => 'El campo :attribute no puede ser menor que :min.',
            ],
            'gt'       => [
                'numeric' => 'El campo :attribute debe ser mayor que :value.',
            ],
            'between'  => [
                'numeric' => 'El campo :attribute debe estar entre :min y :max.',
            ],
        ];
    }

    public static function atributos(): array
    {
        return [
            'clave'               => 'clave',
            'descripcion'         => 'descripción',
            'unidad_medida'       => 'unidad de medida',
            'linea'               => 'línea',
            'clasificacion'       => 'clasificación',
            'area'                => 'área',
            'mn_usd'              => 'moneda',
            'precio_lista'        => 'precio de lista',
            'porcentaje_pv'       => 'porcentaje PV',
            'precio_venta'        => 'precio de venta',
            'des_precio_venta'    => 'descuento precio de venta',
            'precio_especial'     => 'precio especial',
            'desc_precio_espec'   => 'descuento precio especial',
            'precio4'             => 'precio 4',
            'desc_precio4'        => 'descuento precio 4',
            'desc_proveedor'      => 'descuento proveedor',
            'precio_gerente'      => 'precio gerente',
            'precio_tope'         => 'precio tope',
            'costo_venta'         => 'costo de venta',
            'porcetaje_descuento' => 'porcentaje de descuento',
            'articulo_kit'        => 'artículo kit',
            'articulo_serie'      => 'artículo con serie',
            'margen_minimo'       => 'margen mínimo',
            'color'               => 'color',
            'color_branch'        => 'color por sucursal',
            'color_branch.*'      => 'color por sucursal',
            'protocolo'           => 'protocolo',
            'idsat'               => 'clave de producto SAT',
            'id_impuesto_sat'     => 'impuesto SAT',
            'id_tipo_factor'      => 'tipo de factor',
            'iva'                 => 'IVA',
            'precio_minimo'       => 'precio mínimo',
            'desc_precio_minimo'  => 'descuento precio mínimo',
            'sustituto'           => 'sustituto',
            'sustituto1'          => 'sustituto 1',
            'sustituto2'          => 'sustituto 2',
            'costo_promedio'      => 'costo promedio',
            'costo_promedio_ant'  => 'costo promedio anterior',
            'costo_ult_compra'    => 'costo última compra',
            'fecha_ult_compra'    => 'fecha última compra',
            'costo_compra_ant'    => 'costo compra anterior',
            'fecha_compra_ant'    => 'fecha compra anterior',
            'en_promocion'        => 'en promoción',
            'critico'             => 'crítico',
            'control_pedimentos'  => 'control de pedimentos',
            'ubicacion'           => 'ubicación',
            'peso'                => 'peso',
            'std_pack'            => 'Std Pack',
            'inventario_maximo'   => 'inventario máximo',
            'inventario_minimo'   => 'inventario mínimo',
            'punto_reorden'       => 'punto de reorden',
            'habilitado'          => 'habilitado',
        ];
    }

    /**
     * AU-04: Desc. P. Venta = 100 - (P. Venta / P. Lista * 100), con la misma regla del ERP:
     * si P. Venta es 0 el descuento es 100 (así están todos los artículos sin precio en las sucursales).
     * P. Lista 0 con P. Venta > 0 no existe en los datos; se deja en 0.
     */
    public static function descPrecioVenta(float $pLista, float $pVenta): float
    {
        if ($pVenta <= 0) return 100.0;
        if ($pLista <= 0) return 0.0;
        return round(100 - ($pVenta / $pLista * 100), 2);
    }

    /**
     * ART-07 / ED-07: en los artículos reales línea y unidad van en mayúsculas sin espacios,
     * y el IDSAT llega con espacios invisibles (U+00A0) al pegar desde Excel.
     * Solo normaliza los campos que vienen en la petición.
     */
    public static function normalizarCatalogo(\Illuminate\Http\Request $request): void
    {
        foreach (['linea', 'unidad_medida'] as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => mb_strtoupper(trim($request->input($campo)))]);
            }
        }
        if (is_string($request->input('idsat'))) {
            $request->merge(['idsat' => preg_replace('/[\s\x{00A0}]+/u', '', $request->input('idsat'))]);
        }
        foreach (['sustituto', 'sustituto1', 'sustituto2'] as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => trim($request->input($campo))]);
            }
        }
    }

    /**
     * ART-07 / ED-07: el sustituto debe ser una clave que exista en el maestro y no la del mismo artículo.
     * Vacío o '0' se permite (así están la mayoría de los reales).
     */
    public static function sustitutoExiste(string $claveArticulo): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($claveArticulo) {
            if ($value === null || $value === '' || $value === '0') {
                return;
            }
            if (mb_strtoupper($value) === mb_strtoupper(trim($claveArticulo))) {
                $fail('El :attribute no puede ser la misma clave del artículo.');
                return;
            }
            if (!\App\Models\DbMasterArticle::where('clave', $value)->exists()) {
                $fail('El :attribute "' . $value . '" no existe en el Catálogo Maestro.');
            }
        };
    }

    /**
     * ART-07 / ED-07: áreas permitidas según la tabla `areas` de las sucursales (hoy 0 a 5 en las 4).
     * Si no se puede leer ninguna sucursal se usa ese mismo rango.
     */
    public static function areasValidas(\App\Services\BranchConnectionManager $connectionManager): array
    {
        foreach ($connectionManager->getActiveBranches() as $branch) {
            try {
                $ids = $connectionManager->connect($branch->code)
                    ->table('areas')->pluck('IDArea')->map(fn ($id) => (int) $id)->all();
                if (!empty($ids)) {
                    return $ids;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return [0, 1, 2, 3, 4, 5];
    }

    /**
     * ART-07 / ED-07: no hay catálogo de líneas ni unidades; se comparan contra las que ya usan
     * los artículos del maestro. Devuelve los avisos (HTML) de las que nadie usa; null = no se revisa.
     */
    public static function catalogoNuevo(?string $linea, ?string $unidad): array
    {
        $nuevos = [];
        if ($linea !== null && $linea !== '' && !\App\Models\DbMasterArticle::where('linea', $linea)->exists()) {
            $nuevos[] = 'La línea <b>' . e($linea) . '</b> no la usa ningún artículo del Catálogo Maestro.';
        }
        if ($unidad !== null && $unidad !== '' && !\App\Models\DbMasterArticle::where('unidad_medida', $unidad)->exists()) {
            $nuevos[] = 'La unidad de medida <b>' . e($unidad) . '</b> no la usa ningún artículo del Catálogo Maestro.';
        }
        return $nuevos;
    }
}
