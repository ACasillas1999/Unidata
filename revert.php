<?php
$file = '\\\\192.168.60.117\\xampp\\htdocs\\Unidata\\resources\\views\\clientes\\editar.blade.php';
$content = file_get_contents($file);

// The exact string that was injected incorrectly inside {{ old(..., $cliente->field) }}
// We can use a regex to remove it:
$content = preg_replace('/\{\{ !\(\$campos->where\(\'campo\',\'[^\']+\'\)->first\(\)\?->show_in_edit \?\? false\) \? \'[^\']+\' : \'\' \}\}>/', '>', $content);

file_put_contents($file, $content);
echo "Reverted directly via script";
