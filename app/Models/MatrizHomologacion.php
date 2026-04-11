<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrizHomologacion extends Model
{
    use HasFactory;

    protected $table = 'matriz_homologacions';

    public static function resolveColumnName(string $code): string
    {
        $map = [
            'codi'        => 'en_washington',
            'iluminacion' => 'en_ilu',
        ];

        return $map[strtolower($code)] ?? ('en_' . strtolower($code));
    }

    /**
     * Hace que todas las columnas en_* sean fillable dinámicamente.
     */
    public function getFillable()
    {
        $baseFillable = [
            'clave', 'descripcion', 'unidad_medida', 'linea', 'clasificacion',
            'mn_usd', 'precio_lista', 'des_precio_venta', 'precio_venta', 'desc_precio_espec',
            'precio_especial', 'desc_precio4', 'precio4', 'articulo_kit', 'margen_minimo',
            'articulo_serie', 'color', 'protocolo', 'idsat', 'costo_venta', 'porcetaje_descuento',
        ];

        return array_merge($baseFillable, self::getPhysicalBranchColumns());
    }

    /**
     * Retorna todas las columnas 'en_*' que realmente existen en la tabla física.
     */
    public static function getPhysicalBranchColumns(): array
    {
        return array_filter(
            \Illuminate\Support\Facades\Schema::getColumnListing('matriz_homologacions'),
            fn($col) => str_starts_with($col, 'en_')
        );
    }
}
