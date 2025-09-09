<?php

namespace App\Libraries;

use App\Commands\SyncCloud;

class AutoSync
{
    public static function maybeSync(): void
    {
        try {
            // Só tenta sincronizar quando a nuvem está configurada
            $cfg = config('Database');
            $cloudConfigured = !empty($cfg->cloud['database']) || !empty($cfg->cloud['DSN']);
            if (! $cloudConfigured) {
                return;
            }

            // Se estamos offline (usando local_backup), não sincroniza
            if (function_exists('is_offline_mode') && is_offline_mode()) {
                return;
            }

            // Sincroniza rapidamente o outbox, limite pequeno por requisição para não impactar resposta
            $sync = new SyncCloud();
            $sync->runWithOptions([
                'tables'    => [],
                'limit'     => 200,
                'dry'       => false,
                'useOutbox' => true,
            ]);
        } catch (\Throwable $e) {
            // Não interrompe a resposta ao usuário
            log_message('error', 'AutoSync error: {message}', ['message' => $e->getMessage()]);
        }
    }

    public static function maybeDailyBackup(): void
    {
        try {
            $markerDir = WRITEPATH . 'autosync';
            $marker = $markerDir . DIRECTORY_SEPARATOR . 'backup-' . date('Y-m-d') . '.done';
            if (! is_dir($markerDir)) {
                @mkdir($markerDir, 0777, true);
            }
            if (file_exists($marker)) {
                return; // já fez hoje
            }

            $cfg = config('Database');
            $local = \Config\Database::connect('local_backup');
            $dbName = $cfg->local_backup['database'] ?? null;
            if (! $dbName) {
                return;
            }
            $backupDir = WRITEPATH . 'backups';
            if (! is_dir($backupDir)) {
                @mkdir($backupDir, 0777, true);
            }
            $file = $backupDir . DIRECTORY_SEPARATOR . 'local-' . $dbName . '-' . date('Y-m-d-His') . '.sql';

            // Tenta mysqldump se disponível (Windows: bundle do XAMPP)
            $mysqldump = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
            if (! file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
                $mysqldump = 'mysqldump';
            }
            $host = $cfg->local_backup['hostname'] ?? '127.0.0.1';
            $user = $cfg->local_backup['username'] ?? 'root';
            $pass = $cfg->local_backup['password'] ?? '';
            $cmd = $mysqldump . ' -h ' . escapeshellarg($host) . ' -u ' . escapeshellarg($user) . ($pass !== '' ? ' -p' . escapeshellarg($pass) : '') . ' ' . escapeshellarg($dbName) . ' > ' . escapeshellarg($file);
            @shell_exec($cmd);

            @file_put_contents($marker, 'ok');
        } catch (\Throwable $e) {
            log_message('error', 'AutoBackup error: {message}', ['message' => $e->getMessage()]);
        }
    }
}


