<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DownloadsController extends Controller
{
    public function index()
    {
        $exportsDir = storage_path('app/exports');
        if (!File::exists($exportsDir)) {
            return response()->json([]);
        }

        $files = File::files($exportsDir);
        $jobs = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $content = json_decode(file_get_contents($file->getPathname()), true);
                if ($content) {
                    $jobId = $file->getFilenameWithoutExtension();
                    $module = $content['module'] ?? 'Base de Datos';
                    
                    $total = $content['total'] ?? 0;
                    $processed = $content['processed'] ?? 0;
                    $pct = $total > 0 ? floor(($processed / $total) * 100) : 0;
                    if ($pct > 100) $pct = 100;

                    $jobs[] = [
                        'id' => $jobId,
                        'name' => 'Reporte_' . strtoupper(substr($jobId, 0, 5)),
                        'module' => $module,
                        'status' => $content['status'] ?? 'unknown',
                        'progress' => $pct,
                        'total' => $total,
                        'processed' => $processed,
                        'file_url' => $content['file_url'] ?? null,
                        'error' => $content['error'] ?? $content['message'] ?? null,
                        'time' => $file->getMTime(),
                        'size_mb' => 0
                    ];
                }
            }
        }

        // Enrich with real file size if done
        foreach ($jobs as &$job) {
            if ($job['status'] === 'done' && $job['file_url']) {
                 $basename = basename($job['file_url']);
                 $publicPath = public_path('exports/' . $basename);
                 if (File::exists($publicPath)) {
                     // Get size in MB
                     $job['size_mb'] = round(File::size($publicPath) / 1048576, 2);
                     $job['name'] = $basename; // Use actual filename
                     $job['file_url'] = asset('exports/' . $basename); // Re-generate URL dynamically
                 } else {
                     // The file was deleted manually from disk, mark as deleted so we scrub the JSON later.
                     $job['status'] = 'deleted';
                 }
            }
        }

        // Filter out deleted and sort by newest first
        $jobs = array_filter($jobs, fn($j) => $j['status'] !== 'deleted');
        usort($jobs, fn($a, $b) => $b['time'] <=> $a['time']);

        // Limit to 15 items
        return response()->json(array_values(array_slice($jobs, 0, 15)));
    }

    public function destroy($id)
    {
        $exportsDir = storage_path('app/exports');
        $jobFile = $exportsDir . '/' . $id . '.json';
        
        if (File::exists($jobFile)) {
            $content = json_decode(file_get_contents($jobFile), true);
            if ($content && !empty($content['file_url'])) {
                $basename = basename($content['file_url']);
                $publicPath = public_path('exports/' . $basename);
                if (File::exists($publicPath)) {
                    File::delete($publicPath);
                }
            }
            File::delete($jobFile);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Not found'], 404);
    }
}
