<?php
$file = '\\\\192.168.60.117\\xampp\\htdocs\\Unidata\\resources\\views\\clientes\\editar.blade.php';

// Fix the - > issue first
$content = file_get_contents($file);
$content = str_replace('- >', '->', $content);

// Now apply the readonly logic safely using str_replace for each field
$fields_input = ['razon_social', 'calle', 'exterior', 'interior', 'colonia', 'cod_postal', 'ciudad', 'municipio', 'telefono1', 'telefono2', 'fax', 'representante', 'vendedor', 'dias_pago', 'condicion_pago', 'dias_credito', 'limite_credito', 'saldo_actual', 'cta_contable', 'documentos', 'codigo_contpaq', 'modificar_fpmpfac', 'id_opcion_bloqueo', 'sync', 'prefijo_descripcion', 'id_sugar', 'regimen_fiscal', 'fecha_alta'];

foreach ($fields_input as $f) {
    // find class="modal-input" for this specific name
    // this is much safer:
    $search = 'name="'.$f.'" value="{{ old(\''.$f.'\', $cliente->'.$f.') }}"';
    
    // add readonly based on $campos
    $logic = ' {{ !($campos->where(\'campo\',\''.$f.'\')->first()?->show_in_edit ?? false) ? \'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"\' : \'\' }}';
    
    $replace = 'name="'.$f.'" value="{{ old(\''.$f.'\', $cliente->'.$f.') }}"' . $logic;
    
    // some fields like cod_postal might not have maxlength or have different attributes, so we attach to the name=... part!
    $content = str_replace($search, $replace, $content);
}

// For selects:
$fields_select = ['status', 'anticipos', 'otorgo_credito'];
foreach ($fields_select as $f) {
    $search = 'name="'.$f.'" class="modal-input"';
    $logic = ' {{ !($campos->where(\'campo\',\''.$f.'\')->first()?->show_in_edit ?? false) ? \'style="pointer-events:none; opacity:0.6;" tabindex="-1"\' : \'\' }}';
    $replace = 'name="'.$f.'" class="modal-input"' . $logic;
    $content = str_replace($search, $replace, $content);
}

file_put_contents($file, $content);
echo "Manual precise injection done";
