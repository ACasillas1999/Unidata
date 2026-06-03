<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteMaestro extends Model
{
    use HasFactory;

    protected $connection = 'db_master';
    protected $table = 'clientes_maestro';

    protected $fillable = [
        'id_global',
        'rfc',
        'razon_social',
        'calle',
        'exterior',
        'interior',
        'colonia',
        'cod_postal',
        'ciudad',
        'municipio',
        'telefono1',
        'telefono2',
        'telefono3',
        'fax',
        'vendedor',
        'documentos',
        'dias_pago',
        'dias_revision',
        'condicion_pago',
        'dias_credito',
        'limite_credito',
        'otorgo_credito',
        'saldo_actual',
        'status',
        'cta_contable',
        'fecha_alta',
        'representante',
        'addenda',
        'id_reg_id_trib',
        'regimen_fiscal',
        'anticipos',
        'codigo_contpaq',
        'clasificacion',
        'modificar_fpmpfac',
        'identificador',
        'id_opcion_bloqueo',
        'observaciones_caja',
        'sync',
        'prefijo_descripcion',
        'id_sugar',
        'giro_principal',
        'asesor',
        'ubicacion',
    ];

    protected $casts = [
        'fecha_alta' => 'date',
    ];
}
