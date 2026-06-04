<?php

namespace App\Http\Controllers;

use App\Models\HomologacionLineaConfig;
use App\Models\ArticuloRechazado;
use App\Models\ArticuloSinConfigurar;
use App\Models\MatrizHomologacion;
use Illuminate\Http\Request;

class HomologacionLineasController extends Controller
{
    public function index()
    {
        $si  = HomologacionLineaConfig::siSePasa()->orderBy('linea')->get();
        $no  = HomologacionLineaConfig::noSePasa()->orderBy('linea')->get();

        // Líneas "sin configurar": existen en la matriz pero no en la config
        $configuradas = HomologacionLineaConfig::pluck('linea')->toArray();
        $sinConfig    = MatrizHomologacion::query()
            ->whereNotNull('linea')
            ->where('linea', '!=', '')
            ->whereNotIn('linea', $configuradas)
            ->distinct()
            ->orderBy('linea')
            ->pluck('linea');

        return view('homologacion.lineas', compact('si', 'no', 'sinConfig'));
    }

    public function rechazados(Request $request)
    {
        $query = ArticuloRechazado::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($b) use ($q) {
                $b->where('clave', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%")
                  ->orWhere('linea', 'like', "%{$q}%");
            });
        }

        if ($request->filled('sucursal')) {
            $query->where('sucursal', $request->sucursal);
        }

        $rechazados = $query->orderBy('sucursal')->orderBy('clave')->paginate(50)->withQueryString();
        
        $sucursales = ArticuloRechazado::select('sucursal')->distinct()->pluck('sucursal');

        return view('homologacion.rechazados', compact('rechazados', 'sucursales'));
    }

    public function pendientes(Request $request)
    {
        $query = ArticuloSinConfigurar::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($b) use ($q) {
                $b->where('clave', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%")
                  ->orWhere('linea', 'like', "%{$q}%");
            });
        }

        if ($request->filled('sucursal')) {
            $query->where('sucursal', $request->sucursal);
        }

        $pendientes = $query->orderBy('sucursal')->orderBy('clave')->paginate(50)->withQueryString();
        
        $sucursales = ArticuloSinConfigurar::select('sucursal')->distinct()->pluck('sucursal');

        return view('homologacion.pendientes', compact('pendientes', 'sucursales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'linea' => 'required|string|max:30',
            'tipo'  => 'required|in:si,no',
        ]);

        $linea = strtoupper(trim($request->linea));

        HomologacionLineaConfig::updateOrCreate(
            ['linea' => $linea],
            ['tipo'  => $request->tipo, 'descripcion' => $request->descripcion]
        );

        return back()->with('success', "Línea {$linea} guardada como «" . ($request->tipo === 'si' ? 'Sí se pasa' : 'No se pasa') . "».");
    }

    public function destroy($id)
    {
        HomologacionLineaConfig::findOrFail($id)->delete();
        return back()->with('success', 'Línea eliminada de la configuración.');
    }

    /**
     * Importar desde CSV con columnas: linea,tipo
     * Formato:
     *   linea,tipo
     *   10300,si
     *   00217,no
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file    = $request->file('archivo');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle); // saltar encabezados

        // Normalizar encabezados
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);
        $idxLinea = array_search('linea', $headers);
        $idxTipo  = array_search('tipo',  $headers);

        if ($idxLinea === false || $idxTipo === false) {
            fclose($handle);
            return back()->withErrors(['archivo' => 'El CSV debe tener columnas "linea" y "tipo" en la primera fila.']);
        }

        $importados = 0;
        $errores    = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[$idxLinea])) continue;

            $linea = strtoupper(trim($row[$idxLinea]));
            $tipo  = strtolower(trim($row[$idxTipo] ?? ''));

            if (!in_array($tipo, ['si', 'no'])) {
                $errores++;
                continue;
            }

            HomologacionLineaConfig::updateOrCreate(
                ['linea' => $linea],
                ['tipo'  => $tipo]
            );
            $importados++;
        }

        fclose($handle);

        $msg = "{$importados} líneas importadas correctamente.";
        if ($errores) $msg .= " {$errores} filas omitidas (tipo inválido, debe ser 'si' o 'no').";

        return back()->with('success', $msg);
    }
}
