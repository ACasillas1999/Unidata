<?php

namespace App\Support;

/**
 * Mapa snake_case (DbMasterArticle / columnas del maestro) -> PascalCase
 * (columnas reales de la tabla `articulo` en cada sucursal).
 * Fuente unica usada por ArticulosController (replicacion a sucursales) y
 * por las exportaciones que necesitan formato sucursal (ej. export PowerSales).
 */
class ArticuloFieldMap
{
    public static function map(): array
    {
        return [
            'descripcion'         => 'Descripcion',
            'unidad_medida'       => 'Unidad_Medida',
            'linea'               => 'Linea',
            'clasificacion'       => 'Clasificacion',
            'area'                => 'Area',
            'mn_usd'              => 'MN_USD',
            'precio_lista'        => 'Precio_Lista',
            'precio_venta'        => 'Precio_Venta',
            'des_precio_venta'    => 'Desc_Precio_Venta',
            'precio_especial'     => 'Precio_Especial',
            'desc_precio_espec'   => 'Desc_Precio_Espec',
            'precio4'             => 'Precio4',
            'desc_precio4'        => 'Desc_Precio4',
            'precio_minimo'       => 'Precio_Minimo',
            'desc_precio_minimo'  => 'Desc_Precio_Minimo',
            'precio_tope'         => 'PrecioTope',
            'costo_venta'         => 'CostoVenta',
            'porcetaje_descuento' => 'PorcentajeDescuento',
            'desc_proveedor'      => 'Desc_Proveedor',
            'articulo_kit'        => 'Articulo_Kit',
            'articulo_serie'      => 'Articulo_Serie',
            'margen_minimo'       => 'Margen_Minimo',
            'color'               => 'Color',
            'protocolo'           => 'Protocolo',
            'idsat'               => 'IDSAT',
            'habilitado'          => 'Habilitado',
            'clave_proveedor_1'   => 'Clave_Proveedor_1',
            'costo_act_prov_1'    => 'Costo_Act_Prov_1',
            'clave_prov_2'        => 'Clave_Prov_2',
            'costo_act_prov_2'    => 'Costo_Act_Prov_2',
            'clave_prov_3'        => 'Clave_Prov_3',
            'costo_act_prov_3'    => 'Costo_Act_Prov_3',
            'fecha_costo_act_p'   => 'Fecha_Costo_Act_P',
            'inventario_maximo'   => 'Inventario_Maximo',
            'inventario_minimo'   => 'Inventario_Minimo',
            'punto_reorden'       => 'Punto_Reorden',
            'existencia_teorica'  => 'Existencia_Teorica',
            'existencia_fisica'   => 'Existencia_Fisica',
            'costo_promedio'      => 'Costo_Promedio',
            'costo_promedio_ant'  => 'Costo_Promedio_Ant',
            'costo_ult_compra'    => 'Costo_Ult_Compra',
            'fecha_ult_compra'    => 'Fecha_Ult_Compra',
            'costo_compra_ant'    => 'Costo_Compra_Ant',
            'fecha_compra_ant'    => 'Fecha_Compra_Ant',
            'fecha_alta'          => 'Fecha_Alta',
            'en_promocion'        => 'En_Promocion',
            'critico'             => 'Critico',
            'control_pedimentos'  => 'ControlPedimentos',
            'id_impuesto_sat'     => 'IDImpuestoSAT',
            'iva'                 => 'IVA',
            'id_tipo_factor'      => 'IDTipoFactor',
            'sustituto'           => 'Sustituto',
            'sustituto1'          => 'Sustituto1',
            'sustituto2'          => 'Sustituto2',
            'articulo_conversion' => 'ArticuloConversion',
            'conversion'          => 'Conversion',
            'peso'                => 'Peso',
            'ubicacion'           => 'Ubicacion',
            'std_pack'            => 'StdPack',
        ];
    }

    /**
     * Convierte una fila snake_case (DbMasterArticle::toArray()) a formato
     * sucursal PascalCase, igual que ArticulosController al replicar altas.
     */
    public static function toBranchFormat(array $masterRow): array
    {
        $map = self::map();
        $branchData = [];
        foreach ($masterRow as $field => $val) {
            if ($field === 'clave') {
                $branchData['Clave_Articulo'] = $val;
            } elseif (isset($map[$field])) {
                $branchData[$map[$field]] = $val;
            }
        }
        return $branchData;
    }
}
