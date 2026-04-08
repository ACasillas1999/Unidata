<?php

use App\Http\Controllers\ArticulosController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ConexionesController;
use App\Http\Controllers\HomologacionController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\EstadisticasController;
use Illuminate\Support\Facades\Route;

// Redirect raíz a artículos
Route::redirect('/', '/articulos');

// Módulo: Estadísticas
Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');


// Módulo: Artículos
Route::get('/articulos', [ArticulosController::class, 'index'])->name('articulos.index');
Route::get('/articulos/export', [ArticulosController::class, 'export'])->name('articulos.export');

// Módulo: Homologación
Route::get('/homologacion', [HomologacionController::class, 'index'])->name('homologacion.index');
Route::post('/homologacion/sync', [HomologacionController::class, 'sync'])->name('homologacion.sync');
Route::post('/homologacion/sync/cancel', [HomologacionController::class, 'cancelSync'])->name('homologacion.sync.cancel');
Route::get('/homologacion/sync/status', [HomologacionController::class, 'syncStatus'])->name('homologacion.sync.status');
Route::get('/homologacion/export', [HomologacionController::class, 'export'])->name('homologacion.export');

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

