<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MatrizHomologacion;
use Illuminate\Support\Facades\File;

class ExportExcelBg extends Command
{
    protected $signature = 'unidata:export-bg {module} {job_id}';
    protected $description = 'Genera exportaciones pesadas a Excel en proceso asíncrono para no bloquear la aplicación';

    private const BRANCHES = [
        'DEASA'      => ['col' => 'en_deasa'],
        'AIESA'      => ['col' => 'en_aiesa'],
        'CEDIS'      => ['col' => 'en_cedis'],
        'DIMEGSA'    => ['col' => 'en_dimegsa'],
        'FESA'       => ['col' => 'en_fesa'],
        'GABSA'      => ['col' => 'en_gabsa'],
        'ILU'        => ['col' => 'en_ilu'],
        'QUERÉTARO'  => ['col' => 'en_queretaro'],
        'SEGSA'      => ['col' => 'en_segsa'],
        'TAPATÍA'    => ['col' => 'en_tapatia'],
        'VALLARTA'   => ['col' => 'en_vallarta'],
        'WASHINGTON' => ['col' => 'en_washington'],
    ];

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G'); // Generous limit for safe big files

        $module = $this->argument('module');
        $jobId = $this->argument('job_id');
        
        $jobFile = storage_path('app/exports/' . $jobId . '.json');
        
        if (!File::exists($jobFile)) {
            $this->error("Job file not found: $jobId");
            return;
        }

        $jobData = json_decode(file_get_contents($jobFile), true);
        
        try {
            if ($module === 'homologacion') {
                $this->exportHomologacion($jobId, $jobData, $jobFile);
            } else {
                throw new \Exception("Modulo '{$module}' no soportado para background export.");
            }
        } catch (\Throwable $e) {
            $jobData['status'] = 'error';
            $jobData['message'] = $e->getMessage();
            file_put_contents($jobFile, json_encode($jobData, JSON_UNESCAPED_UNICODE));
            $this->error("Export Error: " . $e->getMessage());
        }
    }

    private function exportHomologacion($jobId, &$jobData, $jobFile)
    {
        $filters = $jobData['filters'] ?? [];
        $search    = $filters['q'] ?? '';
        $filterCol = $filters['filtro'] ?? '';
        $filterVal = $filters['estado'] ?? '';
        $cobertura = $filters['cobertura'] ?? '';
        $tienEn    = array_filter((array) ($filters['tiene_en'] ?? []));
        $faltaEn   = array_filter((array) ($filters['falta_en'] ?? []));
        $exact     = $filters['exact'] ?? null;

        $publicDir = public_path('exports');
        if (!File::exists($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }

        $filename = 'homologacion_' . now()->format('Y-m-d_His') . '_' . substr($jobId, 0, 5) . '.xls';
        $filepath = $publicDir . DIRECTORY_SEPARATOR . $filename;
        $fileUrl  = url('exports/' . $filename);

        $out = fopen($filepath, 'w');
        if (!$out) {
            throw new \Exception("No se pudo escribir en la carpeta exports.");
        }

        // HTML Headers
        fwrite($out, '<html xmlns:x="urn:schemas-microsoft-com:office:excel">');
        fwrite($out, '<head><meta charset="utf-8"></head><body>');
        fwrite($out, '<table border="1" style="font-family: Arial, sans-serif; font-size: 11px;">');
        
        // Tabla Cabecera
        fwrite($out, '<thead><tr>');
        fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Código Maestro</th>');
        fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Descripción Universal</th>');
        
        $branchCols = [];
        $allCols = [];
        foreach (self::BRANCHES as $name => $info) {
            $branchCols[] = $info['col'];
            $allCols[]    = $info['col'];
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">' . htmlspecialchars($name) . '</th>');
        }
        fwrite($out, '</tr></thead><tbody>');

        // Construir la Query
        $query = MatrizHomologacion::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('clave', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($filterCol && $filterVal) {
            if ($filterVal === 'ACTIVO') {
                $query->where($filterCol, 1);
            } elseif ($filterVal === 'INACTIVO') {
                $query->where($filterCol, 0);
            } elseif ($filterVal === 'FALTA') {
                $query->whereNull($filterCol);
            }
        }

        $sumParts = array_map(fn($c) => "COALESCE(`{$c}`, 0)", $allCols);
        $sumExpr  = implode(' + ', $sumParts);
        $total    = count($allCols);
        
        if ($cobertura === 'todas')            { foreach ($allCols as $c) $query->where($c, 1); }
        elseif ($cobertura === 'ninguna')      { foreach ($allCols as $c) $query->whereNull($c); }
        elseif ($cobertura === 'incompleta')   { $query->whereRaw("({$sumExpr}) > 0")->whereRaw("({$sumExpr}) < {$total}"); }
        elseif ($cobertura === 'solo_una')     { $query->whereRaw("({$sumExpr}) = 1"); }
        elseif ($cobertura === 'todas_menos_una') { $query->whereRaw("({$sumExpr}) = " . ($total - 1)); }

        if (!is_null($exact) && $exact !== '') {
            $exactCount = (int) $exact;
            $query->whereRaw("({$sumExpr}) = {$exactCount}");
        }
        
        foreach ($tienEn  as $c) { if (in_array($c, $allCols, true)) $query->where($c, 1); }
        foreach ($faltaEn as $c) { if (in_array($c, $allCols, true)) $query->whereNull($c); }

        $sumExprRaw = '';
        foreach ($branchCols as $col) {
            $sumExprRaw .= ($sumExprRaw === '' ? '' : ' + ') . "COALESCE(`{$col}`, 0)";
        }
        
        if ($sumExprRaw !== '') {
            $query->orderByRaw("({$sumExprRaw}) DESC");
        }
        $query->orderBy('clave');

        $totalRecords = $query->count();
        $processedRecords = 0;
        
        $jobData['total'] = $totalRecords;
        file_put_contents($jobFile, json_encode($jobData, JSON_UNESCAPED_UNICODE));

        // Procesar y escribir
        $query->chunk(500, function ($rows) use ($out, $branchCols, &$processedRecords, $jobId, &$jobData, $jobFile) {
            foreach ($rows as $item) {
                fwrite($out, '<tr>');
                fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->clave) . '</td>');
                fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->descripcion) . '</td>');
                
                foreach ($branchCols as $col) {
                    $raw = $item->getRawOriginal($col);
                    if ($raw === 1 || $raw === '1') {
                        fwrite($out, '<td style="background-color:#d1fae5; color:#065f46; text-align:center; font-weight:bold;">ACTIVO</td>');
                    } elseif ($raw === 0 || $raw === '0') {
                        fwrite($out, '<td style="background-color:#fef3c7; color:#92400e; text-align:center;">INACTIVO</td>');
                    } else {
                        fwrite($out, '<td style="background-color:#fee2e2; color:#991b1b; text-align:center;">FALTA</td>');
                    }
                }
                fwrite($out, '</tr>');
                $processedRecords++;
            }
            
            // Actualizar progreso cada chunk
            $jobData['processed'] = $processedRecords;
            file_put_contents($jobFile, json_encode($jobData, JSON_UNESCAPED_UNICODE));
        });

        // Completar archivo
        fwrite($out, '</tbody></table></body></html>');
        fclose($out);

        // Limpiar el json anunciando que se acabó
        $jobData['status'] = 'done';
        $jobData['file_url'] = $fileUrl;
        $jobData['processed'] = $totalRecords;
        file_put_contents($jobFile, json_encode($jobData, JSON_UNESCAPED_UNICODE));
    }
}
