<?php
$file = '\\\\192.168.60.117\\xampp\\htdocs\\Unidata\\resources\\views\\clientes\\editar.blade.php';
$content = file_get_contents($file);

// 1. Inyectar lógica readonly en INPUTS
$content = preg_replace_callback(
    '/(<input[^>]+name="([^"]+)"[^>]+)class="modal-input"/i',
    function($matches) {
        $full_match = $matches[0]; 
        $name = $matches[2];
        
        if (in_array($name, ['_method', '_token'])) {
            return $full_match;
        }

        $logic = '{{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"\' : \'\' }}';
        return $matches[1] . 'class="modal-input" ' . $logic;
    },
    $content
);

// 2. Inyectar lógica disabled-like en SELECTS
$content = preg_replace_callback(
    '/(<select[^>]+name="([^"]+)"[^>]+)class="modal-input"/i',
    function($matches) {
        $full_match = $matches[0]; 
        $name = $matches[2];

        $logic = '{{ !($campos->where(\'campo\',\'' . $name . '\')->first()?->show_in_edit ?? false) ? \'style="pointer-events:none; opacity:0.6;" tabindex="-1"\' : \'\' }}';
        return $matches[1] . 'class="modal-input" ' . $logic;
    },
    $content
);

file_put_contents($file, $content);
echo "Inyectado correctamente con class='modal-input'";
