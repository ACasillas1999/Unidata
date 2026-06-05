<?php
$file = '\\\\192.168.60.117\\xampp\\htdocs\\Unidata\\resources\\views\\clientes\\editar.blade.php';
$content = file_get_contents($file);

// Add readonly logic to text/number inputs
$content = preg_replace_callback(
    '/(<input[^>]+name="([^"]+)"[^>]+class="modal-input"[^>]*>)/i',
    function($matches) {
        $full_tag = $matches[1];
        $name = $matches[2];
        if ($name === 'status' || $name === '_method' || $name === '_token') return $full_tag;
        
        $logic = ' {{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'readonly style=opacity:0.6;cursor:not-allowed;\' : \'\' }}';
        
        return str_replace('>', $logic . '>', $full_tag);
    },
    $content
);

// Add pointer-events:none logic to select inputs
$content = preg_replace_callback(
    '/(<select[^>]+name="([^"]+)"[^>]+class="modal-input"[^>]*>)/i',
    function($matches) {
        $full_tag = $matches[1];
        $name = $matches[2];
        if ($name === 'status' || $name === 'anticipos' || $name === 'otorgo_credito') {
            $logic = ' {{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'style=pointer-events:none;opacity:0.6; tabindex=-1\' : \'\' }}';
            return str_replace('>', $logic . '>', $full_tag);
        }
        
        $logic = ' {{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'style=pointer-events:none;opacity:0.6; tabindex=-1\' : \'\' }}';
        
        return str_replace('>', $logic . '>', $full_tag);
    },
    $content
);

file_put_contents($file, $content);
echo "Replaced properly";
