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
}
