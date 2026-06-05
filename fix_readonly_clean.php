<?php
$file = '\\\\192.168.60.117\\xampp\\htdocs\\Unidata\\resources\\views\\clientes\\editar.blade.php';
$content = file_get_contents($file);

// Replace <input type="..." name="NAME" value="..." maxlength="..." class="modal-input">
// We will look for name="([^"]+)" and insert our logic just before the closing >
$content = preg_replace_callback(
    '/(<input[^>]+name="([^"]+)"[^>]*?)(>)/i',
    function($matches) {
        $name = $matches[2];
        if ($name === '_method' || $name === '_token') return $matches[0];
        
        $logic = ' {{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"\' : \'\' }}';
        
        return $matches[1] . $logic . '>';
    },
    $content
);

// Replace <select ...>
$content = preg_replace_callback(
    '/(<select[^>]+name="([^"]+)"[^>]*?)(>)/i',
    function($matches) {
        $name = $matches[2];
        
        $logic = ' {{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'style="pointer-events:none; opacity:0.6;" tabindex="-1"\' : \'\' }}';
        
        return $matches[1] . $logic . '>';
    },
    $content
);

file_put_contents($file, $content);
echo "Clean Replacement Done";
