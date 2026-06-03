<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClienteCampo;
use Illuminate\Support\Facades\DB;

class ClientesCamposSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiamos la tabla para insertarlos de nuevo con la nueva estructura
        DB::table('clientes_campos')->truncate();

        $campos = [
            // Campos requeridos (siempre activos, no se pueden desactivar)
            ['campo' => 'rfc',             'label' => 'RFC',                 'is_required' => true,  'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'razon_social',    'label' => 'Razón Social',        'is_required' => true,  'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'id_global',       'label' => 'ID Global',           'is_required' => true,  'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'status',          'label' => 'Estatus',             'is_required' => true,  'show_in_create' => true,  'show_in_edit' => true],

            // Dirección
            ['campo' => 'calle',           'label' => 'Calle',               'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'exterior',        'label' => 'No. Exterior',        'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'interior',        'label' => 'No. Interior',        'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'colonia',         'label' => 'Colonia',             'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'cod_postal',      'label' => 'Código Postal',       'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'ciudad',          'label' => 'Ciudad',              'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'municipio',       'label' => 'Municipio',           'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],

            // Contacto
            ['campo' => 'telefono1',       'label' => 'Teléfono 1',          'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'telefono2',       'label' => 'Teléfono 2',          'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'telefono3',       'label' => 'Teléfono 3',          'is_required' => false, 'show_in_create' => false, 'show_in_edit' => false],
            ['campo' => 'fax',             'label' => 'Fax',                 'is_required' => false, 'show_in_create' => false, 'show_in_edit' => false],
            ['campo' => 'representante',   'label' => 'Representante',       'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],

            // Crédito y condiciones
            ['campo' => 'vendedor',        'label' => 'Vendedor',            'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'dias_pago',       'label' => 'Días de Pago',        'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'dias_revision',   'label' => 'Días de Revisión',    'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'condicion_pago',  'label' => 'Condición de Pago',   'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'dias_credito',    'label' => 'Días de Crédito',     'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'limite_credito',  'label' => 'Límite de Crédito',   'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'otorgo_credito',  'label' => 'Otorgó Crédito',      'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],

            // Fiscal
            ['campo' => 'regimen_fiscal',  'label' => 'Régimen Fiscal',      'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'id_reg_id_trib',  'label' => 'ID Registro Trib.',   'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'cta_contable',    'label' => 'Cuenta Contable',     'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'anticipos',       'label' => 'Anticipos',           'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],

            // Configuración adicional
            ['campo' => 'fecha_alta',      'label' => 'Fecha de Alta',       'is_required' => false, 'show_in_create' => true,  'show_in_edit' => true],
            ['campo' => 'clasificacion',   'label' => 'Clasificación',       'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'identificador',   'label' => 'Identificador',       'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'addenda',         'label' => 'Addenda',             'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'giro_principal',  'label' => 'Giro Principal',      'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'asesor',          'label' => 'Asesor',              'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'ubicacion',       'label' => 'Ubicación',           'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'observaciones_caja', 'label' => 'Observaciones Caja', 'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],

            // NUEVOS CAMPOS (antes excluidos)
            ['campo' => 'documentos',         'label' => 'Documentos',             'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'saldo_actual',       'label' => 'Saldo Actual',           'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'codigo_contpaq',     'label' => 'Código Contpaq',         'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'modificar_fpmpfac',  'label' => 'Modificar FPMPFac',      'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'id_opcion_bloqueo',  'label' => 'Opción Bloqueo',         'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'sync',               'label' => 'Sync',                   'is_required' => false, 'show_in_create' => false, 'show_in_edit' => false],
            ['campo' => 'prefijo_descripcion','label' => 'Prefijo Descripción',    'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
            ['campo' => 'id_sugar',           'label' => 'ID Sugar',               'is_required' => false, 'show_in_create' => false, 'show_in_edit' => true],
        ];

        foreach ($campos as $campo) {
            ClienteCampo::create([
                'campo'          => $campo['campo'],
                'label'          => $campo['label'],
                'is_required'    => $campo['is_required'],
                'show_in_create' => $campo['show_in_create'],
                'show_in_edit'   => $campo['show_in_edit'],
            ]);
        }
    }
}
