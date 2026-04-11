<?php

use App\Http\Controllers\ArticulosController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ConexionesController;
use App\Http\Controllers\HomologacionController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\DownloadsController;
use App\Http\Controllers\DBMasterController;
use Illuminate\Support\Facades\Route;

// Redirect raíz a artículos
Route::redirect('/', '/articulos');

// Módulo: Estadísticas
Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');


// Módulo: Artículos
Route::get('/articulos', [ArticulosController::class, 'index'])->name('articulos.index');
Route::get('/articulos/subir', [ArticulosController::class, 'subirForm'])->name('articulos.subir');
Route::post('/articulos/subir/proceso', [ArticulosController::class, 'procesarSubida'])->name('articulos.subir.proceso');
Route::get('/articulos/export', [ArticulosController::class, 'export'])->name('articulos.export');
Route::post('/articulos/subir/preview', [ArticulosController::class, 'previewSubida'])->name('articulos.subir.preview');
Route::get('/articulos/historial', [ArticulosController::class, 'historialSubidas'])->name('articulos.historial');
Route::get('/articulos/historial/{id}/detalles', [ArticulosController::class, 'historialDetalles'])->name('articulos.historial.detalles');
Route::get('/articulos/historial/{id}/descargar', [ArticulosController::class, 'descargarCsv'])->name('articulos.historial.descargar');
Route::post('/articulos/revertir/{id}', [ArticulosController::class, 'revertirSubida'])->name('articulos.revertir');

// Módulo: Homologación
Route::get('/homologacion', [HomologacionController::class, 'index'])->name('homologacion.index');
Route::post('/homologacion/sync', [HomologacionController::class, 'sync'])->name('homologacion.sync');
Route::post('/homologacion/sync/cancel', [HomologacionController::class, 'cancelSync'])->name('homologacion.sync.cancel');
Route::get('/homologacion/sync/status', [HomologacionController::class, 'syncStatus'])->name('homologacion.sync.status');
Route::get('/homologacion/export', [HomologacionController::class, 'export'])->name('homologacion.export');
Route::post('/homologacion/export/bg', [HomologacionController::class, 'exportBgStart'])->name('homologacion.export.bg');
Route::get('/homologacion/export/status/{job_id}', [HomologacionController::class, 'exportBgStatus'])->name('homologacion.export.status');

// Módulo: DB Master
Route::get('/db-master', [DBMasterController::class, 'index'])->name('db_master.index');
Route::get('/db-master/export', [DBMasterController::class, 'export'])->name('db_master.export');
Route::post('/db-master/sync', [DBMasterController::class, 'sync'])->name('db_master.sync');
Route::get('/db-master/history', [DBMasterController::class, 'history'])->name('db_master.history');

// Módulo: Descargas Globales
Route::get('/descargas', [DownloadsController::class, 'index'])->name('descargas.index');
Route::delete('/descargas/{id}', [DownloadsController::class, 'destroy'])->name('descargas.destroy');
// Módulo: Clientes
Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');

// Módulo: Proveedores
Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores.index');

// Módulo: Conexiones
Route::get('/conexiones', [ConexionesController::class, 'index'])->name('conexiones.index');
Route::post('/conexiones', [ConexionesController::class, 'store'])->name('conexiones.store');
Route::post('/conexiones/test-all', [ConexionesController::class, 'testAll'])->name('conexiones.test-all');
Route::post('/conexiones/{id}/test', [ConexionesController::class, 'test'])->name('conexiones.test');
Route::put('/conexiones/{id}', [ConexionesController::class, 'update'])->name('conexiones.update');
Route::delete('/conexiones/{id}', [ConexionesController::class, 'destroy'])->name('conexiones.destroy');

