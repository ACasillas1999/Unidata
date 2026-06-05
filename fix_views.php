<?php
$f1 = __DIR__ . '/resources/views/clientes/crear.blade.php';
$f2 = __DIR__ . '/resources/views/clientes/editar.blade.php';
file_put_contents($f1, str_replace('?->is_active', '!== null', file_get_contents($f1)));
file_put_contents($f2, str_replace('?->is_active', '!== null', file_get_contents($f2)));
echo "Done";
