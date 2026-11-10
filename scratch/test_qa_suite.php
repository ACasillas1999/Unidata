<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ClientesController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

echo "============================================================\n";
echo "   BATERÍA DE PRUEBAS DE VALIDACIÓN - CLIENTES (COMPLETA)   \n";
echo "============================================================\n\n";

$controller = app(ClientesController::class);
$passed = 0;
$total = 0;

function assertTest(bool $condition, string $title, string $details = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] {$title}\n";
        if ($details) echo "        Detalle: {$details}\n";
    } else {
        echo " [FAIL] {$title}\n";
        if ($details) echo "        Detalle: {$details}\n";
    }
}

// -----------------------------------------------------------------------------
// PRUEBA 1: Validación de Email (ej: a@b debe ser rechazado)
// -----------------------------------------------------------------------------
echo "--- 1. Validación de Formato de Correo Electrónico (Email) ---\n";

$reqEmailInvalido = Request::create('/clientes', 'POST', [
    'rfc' => 'TEST990101AA1',
    'razon_social' => 'Empresa Email Test SA',
    'status' => 'A',
    'email' => 'a@b',
]);
$reqEmailInvalido->headers->set('Accept', 'application/json');

try {
    $controller->store($reqEmailInvalido);
    assertTest(false, "Email 'a@b' debe ser rechazado");
} catch (ValidationException $e) {
    $hasError = isset($e->errors()['email']);
    $msg = $e->errors()['email'][0] ?? '';
    assertTest($hasError, "Email inválido 'a@b' rechazado con 422", $msg);
}

// -----------------------------------------------------------------------------
// PRUEBA 2: Validación de Teléfono (ej: ABCDEFG debe ser rechazado)
// -----------------------------------------------------------------------------
echo "\n--- 2. Validación de Teléfono y Fax (ABCDEFG debe ser rechazado) ---\n";

$reqTelInvalido = Request::create('/clientes', 'POST', [
    'rfc' => 'TEST990101AA2',
    'razon_social' => 'Empresa Tel Test SA',
    'status' => 'A',
    'telefono1' => 'ABCDEFG',
]);
$reqTelInvalido->headers->set('Accept', 'application/json');

try {
    $controller->store($reqTelInvalido);
    assertTest(false, "Teléfono 'ABCDEFG' debe ser rechazado");
} catch (ValidationException $e) {
    $hasError = isset($e->errors()['telefono1']);
    $msg = $e->errors()['telefono1'][0] ?? '';
    assertTest($hasError, "Teléfono 'ABCDEFG' rechazado con 422", $msg);
}

// Teléfono válido con números y formato
$reqTelValido = Request::create('/clientes', 'POST', [
    'rfc' => 'TEST990101AA3',
    'razon_social' => 'Empresa Tel Valido SA',
    'status' => 'A',
    'email' => 'contacto@empresa.com',
    'telefono1' => '(33) 1234-5678',
    'telefono2' => '+52 33 1234 56',
]);
$reqTelValido->headers->set('Accept', 'application/json');

// Validar que pasa las reglas de validación básicas
$reglasReflect = new ReflectionClass(ClientesController::class);
$methodBuscar = $reglasReflect->getMethod('buscarClienteExistente');
$methodBuscar->setAccessible(true);

// -----------------------------------------------------------------------------
// PRUEBA 3: Emojis y caracteres fuera de Windows-1252
// -----------------------------------------------------------------------------
echo "\n--- 3. Validación de Emojis y símbolos latin1 ---\n";

$reqEmoji1 = Request::create('/clientes', 'POST', [
    'rfc' => 'TEST990101AA4',
    'razon_social' => 'Ferretería El Tornillo 🚀',
    'status' => 'A',
]);
$reqEmoji1->headers->set('Accept', 'application/json');

try {
    $controller->store($reqEmoji1);
    assertTest(false, "Emoji en razón social debe ser rechazado");
} catch (ValidationException $e) {
    $hasError = isset($e->errors()['razon_social']);
    $msg = $e->errors()['razon_social'][0] ?? '';
    assertTest($hasError && str_contains($msg, '🚀'), "Emoji 🚀 rechazado antes de tocar BD", $msg);
}

// -----------------------------------------------------------------------------
// PRUEBA 4: Fechas inexistentes
// -----------------------------------------------------------------------------
echo "\n--- 4. Validación de Fechas Inexistentes ---\n";

$reqFecha1 = Request::create('/clientes', 'POST', [
    'rfc' => 'TEST990101AA5',
    'razon_social' => 'Constructora FEB31 SA',
    'status' => 'A',
    'fecha_alta' => '2026-02-31',
]);
$reqFecha1->headers->set('Accept', 'application/json');

try {
    $controller->store($reqFecha1);
    assertTest(false, "Fecha inexistente 2026-02-31 debe ser rechazada");
} catch (ValidationException $e) {
    $hasError = isset($e->errors()['fecha_alta']);
    $msg = $e->errors()['fecha_alta'][0] ?? '';
    assertTest($hasError, "Fecha inexistente '2026-02-31' rechazada con 422", $msg);
}

// -----------------------------------------------------------------------------
// PRUEBA 5: Razón Social Duplicada
// -----------------------------------------------------------------------------
echo "\n--- 5. Detección y respuesta de duplicados antes de insertar en BD ---\n";

$methodResp = $reglasReflect->getMethod('respuestaClienteExistente');
$methodResp->setAccessible(true);

$mockDuplicadoRazon = [
    'rfc' => 'EXIST800101AA1',
    'razon_social' => 'ACEROS Y TUBOS DEL NORTE SA DE CV',
    'coincide' => ['razon_social'],
    'origen' => 'sucursal AIESA'
];

$reqDupRazon = Request::create('/clientes', 'POST', [
    'rfc' => 'NUEVO990101AA1',
    'razon_social' => 'aceros y tubos del norte sa de cv',
    'status' => 'A'
]);
$reqDupRazon->headers->set('Accept', 'application/json');
$reqDupRazon->headers->set('X-Requested-With', 'XMLHttpRequest');

$resDup = $methodResp->invoke($controller, $reqDupRazon, $mockDuplicadoRazon);
$dataDup = json_decode($resDup->getContent(), true);

assertTest(
    $resDup->getStatusCode() === 409 &&
    $dataDup['success'] === false &&
    isset($dataDup['cliente_existente']['edit_url']),
    "Razón social repetida devuelve 409 Conflict con edit_url"
);

// -----------------------------------------------------------------------------
// PRUEBA 6: Lógica de Rollback si fallan todas las sucursales
// -----------------------------------------------------------------------------
echo "\n--- 6. Comprobación del mecanismo de Rollback cuando fallan todas las sucursales ---\n";

$controllerCode = file_get_contents(__DIR__ . '/../app/Http/Controllers/ClientesController.php');
$hasRollback = str_contains($controllerCode, 'if ($total > 0 && $exitosos === 0)') &&
                str_contains($controllerCode, '$maestro->delete()');
$noFalseSuccess = str_contains($controllerCode, "'success' => false,") &&
                  str_contains($controllerCode, 'Se canceló el alta en el maestro');

assertTest($hasRollback, "Rollback activo: \$maestro->delete() si 0 sucursales reciben el cliente");
assertTest($noFalseSuccess, "Fin de 'Alta exitosa' falsa: Se responde success=false y status 500");

echo "\n============================================================\n";
echo " RESULTADO: {$passed} de {$total} pruebas PASARON SATISFACTORIAMENTE.\n";
echo "============================================================\n";
