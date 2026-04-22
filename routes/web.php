<?php

use App\Http\Controllers\ArticulosController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ConexionesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomologacionController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\DownloadsController;
use App\Http\Controllers\DBMasterController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// AutenticaciÃ³n
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Redirect raÃ­z a dashboard
    Route::redirect('/', '/dashboard');

// MÃ³dulo: Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// MÃ³dulo: EstadÃ­sticas
Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');


// MÃ³dulo: ArtÃ­culos
Route::get('/articulos', [ArticulosController::class, 'index'])->name('articulos.index');
Route::get('/articulos/crear', [ArticulosController::class, 'crear'])->name('articulos.crear');
Route::post('/articulos/crear', [ArticulosController::class, 'storeManual'])->name('articulos.storeManual');
Route::get('/articulos/subir', [ArticulosController::class, 'subirForm'])->name('articulos.subir');
Route::post('/articulos/subir/proceso', [ArticulosController::class, 'procesarSubida'])->name('articulos.subir.proceso');
Route::get('/articulos/export', [ArticulosController::class, 'export'])->name('articulos.export');
Route::post('/articulos/subir/preview', [ArticulosController::class, 'previewSubida'])->name('articulos.subir.preview');
Route::get('/articulos/historial', [ArticulosController::class, 'historialSubidas'])->name('articulos.historial');
Route::get('/articulos/historial/{id}/detalles', [ArticulosController::class, 'historialDetalles'])->name('articulos.historial.detalles');
Route::get('/articulos/historial/{id}/descargar', [ArticulosController::class, 'descargarCsv'])->name('articulos.historial.descargar');
Route::post('/articulos/revertir/{id}', [ArticulosController::class, 'revertirSubida'])->name('articulos.revertir');

// MÃ³dulo: HomologaciÃ³n
Route::get('/homologacion', [HomologacionController::class, 'index'])->name('homologacion.index');
Route::post('/homologacion/sync', [HomologacionController::class, 'sync'])->name('homologacion.sync');
Route::post('/homologacion/sync/cancel', [HomologacionController::class, 'cancelSync'])->name('homologacion.sync.cancel');
Route::get('/homologacion/sync/status', [HomologacionController::class, 'syncStatus'])->name('homologacion.sync.status');
Route::get('/homologacion/export', [HomologacionController::class, 'export'])->name('homologacion.export');
Route::post('/homologacion/export/bg', [HomologacionController::class, 'exportBgStart'])->name('homologacion.export.bg');
Route::get('/homologacion/export/status/{job_id}', [HomologacionController::class, 'exportBgStatus'])->name('homologacion.export.status');

// MÃ³dulo: DB Master
Route::get('/db-master', [DBMasterController::class, 'index'])->name('db_master.index');
Route::get('/db-master/export', [DBMasterController::class, 'export'])->name('db_master.export');
Route::post('/db-master/item/{id}', [DBMasterController::class, 'updateManual'])->name('db_master.update_item');
Route::post('/db-master/sync', [DBMasterController::class, 'sync'])->name('db_master.sync');
Route::get('/db-master/sync/status', [DBMasterController::class, 'syncStatus'])->name('db_master.sync.status');
Route::get('/db-master/history', [DBMasterController::class, 'history'])->name('db_master.history');

// MÃ³dulo: Descargas Globales
Route::get('/descargas', [DownloadsController::class, 'index'])->name('descargas.index');
Route::delete('/descargas/{id}', [DownloadsController::class, 'destroy'])->name('descargas.destroy');
// MÃ³dulo: Clientes
Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');

// MÃ³dulo: Proveedores
Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores.index');

// MÃ³dulo: Conexiones
Route::get('/conexiones', [ConexionesController::class, 'index'])->name('conexiones.index');
Route::post('/conexiones', [ConexionesController::class, 'store'])->name('conexiones.store');
Route::post('/conexiones/test-all', [ConexionesController::class, 'testAll'])->name('conexiones.test-all');
Route::post('/conexiones/{id}/test', [ConexionesController::class, 'test'])->name('conexiones.test');
Route::put('/conexiones/{id}', [ConexionesController::class, 'update'])->name('conexiones.update');
Route::delete('/conexiones/{id}', [ConexionesController::class, 'destroy'])->name('conexiones.destroy');

// MÃ³dulo: Usuarios
Route::get('/usuarios', [\App\Http\Controllers\UsuariosController::class, 'index'])->name('usuarios.index');
Route::post('/usuarios', [\App\Http\Controllers\UsuariosController::class, 'store'])->name('usuarios.store');
Route::put('/usuarios/{id}', [\App\Http\Controllers\UsuariosController::class, 'update'])->name('usuarios.update');
Route::delete('/usuarios/{id}', [\App\Http\Controllers\UsuariosController::class, 'destroy'])->name('usuarios.destroy');

// MÃ³dulo: Roles y Permisos
Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
Route::post('/roles/{id}/duplicate', [RolesController::class, 'duplicate'])->name('roles.duplicate');
Route::put('/roles/{id}', [RolesController::class, 'update'])->name('roles.update');
Route::delete('/roles/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');
});

