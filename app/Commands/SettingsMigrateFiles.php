<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SettingsMigrateFiles extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'settings:migrate-files';
    protected $description = 'Migra arquivos JSON de configurações (printing, payments, users) para a tabela empresa_settings';

    public function run(array $params)
    {
        $baseDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
        if (! is_dir($baseDir)) {
            CLI::write('Diretório não encontrado: ' . $baseDir, 'yellow');
            return;
        }

        $svc = new \App\Libraries\SettingsService();

        $migrated = 0;
        $errors = 0;

        // printing-<id>.json
        foreach (glob($baseDir . 'printing-*.json') as $file) {
            $idEmpresa = (int) preg_replace('/^.*printing\-([0-9]+)\.json$/', '$1', $file);
            $json = json_decode((string) @file_get_contents($file), true);
            if (! is_array($json)) { $json = []; }
            try {
                $svc->save($idEmpresa, 'printing', $json);
                $migrated++;
                CLI::write("printing migrado para empresa {$idEmpresa}", 'green');
            } catch (\Throwable $e) {
                $errors++;
                CLI::write("Falha ao migrar printing da empresa {$idEmpresa}: {$e->getMessage()}", 'red');
            }
        }

        // payments-<id>.json
        foreach (glob($baseDir . 'payments-*.json') as $file) {
            $idEmpresa = (int) preg_replace('/^.*payments\-([0-9]+)\.json$/', '$1', $file);
            $json = json_decode((string) @file_get_contents($file), true);
            if (! is_array($json)) { $json = []; }
            try {
                $svc->save($idEmpresa, 'payments', $json);
                $migrated++;
                CLI::write("payments migrado para empresa {$idEmpresa}", 'green');
            } catch (\Throwable $e) {
                $errors++;
                CLI::write("Falha ao migrar payments da empresa {$idEmpresa}: {$e->getMessage()}", 'red');
            }
        }

        // users-<id>.json
        foreach (glob($baseDir . 'users-*.json') as $file) {
            $idEmpresa = (int) preg_replace('/^.*users\-([0-9]+)\.json$/', '$1', $file);
            $json = json_decode((string) @file_get_contents($file), true);
            if (! is_array($json)) { $json = []; }
            try {
                $svc->save($idEmpresa, 'users', $json);
                $migrated++;
                CLI::write("users migrado para empresa {$idEmpresa}", 'green');
            } catch (\Throwable $e) {
                $errors++;
                CLI::write("Falha ao migrar users da empresa {$idEmpresa}: {$e->getMessage()}", 'red');
            }
        }

        CLI::write("Migração concluída. Registros migrados: {$migrated}. Erros: {$errors}.", $errors ? 'yellow' : 'green');
    }
}


