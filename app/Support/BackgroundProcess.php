<?php

namespace App\Support;

class BackgroundProcess
{
    /**
     * Resuelve el ejecutable de PHP CLI real (incluso si se llama desde Apache mod_php donde PHP_BINARY es httpd.exe).
     */
    public static function phpBinary(): string
    {
        $bin = PHP_BINARY;
        if (DIRECTORY_SEPARATOR === '\\' && !str_ends_with(strtolower($bin), 'php.exe')) {
            $candidates = [
                'C:\\xampp\\php\\php.exe',
                dirname($bin, 2) . '\\php\\php.exe',
                'php',
            ];
            foreach ($candidates as $candidate) {
                if ($candidate === 'php' || is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return $bin;
    }

    /**
     * Lanza un comando artisan en segundo plano sin bloquear el hilo HTTP en Windows ni en Linux.
     */
    public static function artisan(string $commandAndArgs, string $logFile): void
    {
        $php     = self::phpBinary();
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
