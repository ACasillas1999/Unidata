<?php

namespace App\Support;

class BackgroundProcess
{
    /**
     * Lanza un comando artisan en segundo plano sin bloquear el hilo HTTP en Windows ni en Linux.
     */
    public static function artisan(string $commandAndArgs, string $logFile): void
    {
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');

        if (DIRECTORY_SEPARATOR === '\\') {
            $errLog = preg_replace('/\.log$/i', '.err.log', $logFile) ?: ($logFile . '.err');
            $ps = sprintf(
                "Start-Process -FilePath '%s' -ArgumentList '\"%s\" %s' -RedirectStandardOutput '%s' -RedirectStandardError '%s' -WindowStyle Hidden",
                str_replace("'", "''", $php),
                str_replace("'", "''", $artisan),
                str_replace("'", "''", $commandAndArgs),
                str_replace("'", "''", $logFile),
                str_replace("'", "''", $errLog)
            );
            pclose(popen('powershell -NoProfile -Command "' . str_replace('"', '\"', $ps) . '"', 'r'));
            return;
        }

        $cmd = sprintf('nohup %s %s %s >> %s 2>&1 &', escapeshellarg($php), escapeshellarg($artisan), $commandAndArgs, escapeshellarg($logFile));
        pclose(popen($cmd, 'r'));
    }
}
