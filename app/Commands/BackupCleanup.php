<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\RemoteStorage;
use Config\Backup;
use Exception;

/**
 * Comando CLI para Limpeza Automática de Backups
 * 
 * Implementa política de rotação e limpeza de backups antigos
 */
class BackupCleanup extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Backup';
    
    /**
     * Nome do comando
     */
    protected $name = 'backup:cleanup';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Executa limpeza automática de backups antigos conforme política de rotação';
    
    /**
     * Uso do comando
     */
    protected $usage = 'backup:cleanup [options]';
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--local-only' => 'Limpar apenas backups locais',
        '--remote-only' => 'Limpar apenas backups remotos',
        '--dry-run' => 'Simular limpeza sem deletar arquivos',
        '--force' => 'Forçar limpeza sem confirmação',
        '--tenant' => 'Limpar apenas um tenant específico',
        '--verbose' => 'Saída detalhada'
    ];
    
    /**
     * Configuração de backup
     */
    protected Backup $config;
    
    /**
     * Storage remoto
     */
    protected RemoteStorage $remoteStorage;
    
    /**
     * Diretório base de backups
     */
    protected string $backupBaseDir;
    
    /**
     * Estatísticas da limpeza
     */
    protected array $stats = [
        'local_deleted' => 0,
        'remote_deleted' => 0,
        'local_size_freed' => 0,
        'remote_size_freed' => 0,
        'errors' => []
    ];
    
    /**
     * Executar comando
     */
    public function run(array $params)
    {
        $startTime = microtime(true);
        
        try {
            $this->initialize();
            
            $isDryRun = CLI::getOption('dry-run');
            $isVerbose = CLI::getOption('verbose');
            $isForce = CLI::getOption('force');
            $localOnly = CLI::getOption('local-only');
            $remoteOnly = CLI::getOption('remote-only');
            $specificTenant = CLI::getOption('tenant');
            
            CLI::write("🧹 LIMPEZA AUTOMÁTICA DE BACKUPS", 'green');
            CLI::write("Modo: " . ($isDryRun ? 'Simulação' : 'Execução'));
            CLI::write("Data: " . date('Y-m-d H:i:s'));
            CLI::newLine();
            
            // Confirmação de segurança
            if (!$isDryRun && !$isForce) {
                CLI::write("⚠️  Esta operação irá deletar backups antigos permanentemente!", 'yellow');
                $confirm = CLI::prompt('Deseja continuar? (s/N)', 'n');
                if (strtolower($confirm) !== 's') {
                    CLI::write("❌ Operação cancelada pelo usuário");
                    return;
                }
            }
            
            // Obter lista de tenants
            $tenants = $specificTenant ? [$specificTenant] : $this->getAllTenants();
            
            CLI::write("📊 Processando " . count($tenants) . " tenant(s)");
            CLI::newLine();
            
            foreach ($tenants as $tenant) {
                CLI::write("🔄 Processando tenant: {$tenant}");
                
                try {
                    // Limpeza local
                    if (!$remoteOnly) {
                        $this->cleanupLocalBackups($tenant, $isDryRun, $isVerbose);
                    }
                    
                    // Limpeza remota
                    if (!$localOnly && $this->config->remoteStorage['enabled']) {
                        $this->cleanupRemoteBackups($tenant, $isDryRun, $isVerbose);
                    }
                    
                } catch (Exception $e) {
                    CLI::error("  ❌ Erro no tenant {$tenant}: " . $e->getMessage());
                    $this->stats['errors'][] = "Tenant {$tenant}: " . $e->getMessage();
                }
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            
            // Relatório final
            $this->showCleanupReport($duration, $isDryRun);
            
        } catch (Exception $e) {
            CLI::error("❌ ERRO NA LIMPEZA: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializar configurações
     */
    protected function initialize(): void
    {
        $this->config = new Backup();
        $this->remoteStorage = new RemoteStorage();
        $this->backupBaseDir = WRITEPATH . 'backups/';
    }
    
    /**
     * Obter todos os tenants com backups
     */
    protected function getAllTenants(): array
    {
        $tenants = [];
        
        if (!is_dir($this->backupBaseDir)) {
            return $tenants;
        }
        
        $tenantDirs = glob($this->backupBaseDir . 'tenant_*', GLOB_ONLYDIR);
        
        foreach ($tenantDirs as $tenantDir) {
            $tenantName = basename($tenantDir);
            $tenantId = str_replace('tenant_', '', $tenantName);
            $tenantId = str_replace('_', ':', $tenantId);
            $tenants[] = $tenantId;
        }
        
        return $tenants;
    }
    
    /**
     * Limpeza de backups locais
     */
    protected function cleanupLocalBackups(string $tenant, bool $isDryRun, bool $isVerbose): void
    {
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenant);
        $tenantDir = $this->backupBaseDir . "tenant_{$safeTenantId}/";
        
        if (!is_dir($tenantDir)) {
            return;
        }
        
        $policy = $this->config->getRetentionPolicy('local');
        $backups = $this->getBackupsByType($tenantDir);
        
        if ($isVerbose) {
            CLI::write("  📁 Local - Encontrados: " . array_sum(array_map('count', $backups)) . " backups");
        }
        
        // Aplicar política de retenção
        foreach ($backups as $type => $backupList) {
            $toDelete = $this->selectBackupsToDelete($backupList, $policy, $type);
            
            foreach ($toDelete as $backup) {
                $size = $this->getDirectorySize($backup['path']);
                
                if ($isVerbose) {
                    CLI::write("    🗑️ Deletando: {$backup['date']} ({$this->formatFileSize($size)})");
                }
                
                if (!$isDryRun) {
                    $this->deleteDirectory($backup['path']);
                }
                
                $this->stats['local_deleted']++;
                $this->stats['local_size_freed'] += $size;
            }
        }
    }
    
    /**
     * Limpeza de backups remotos
     */
    protected function cleanupRemoteBackups(string $tenant, bool $isDryRun, bool $isVerbose): void
    {
        try {
            $policy = $this->config->getRetentionPolicy('remote');
            $remoteBackups = $this->getRemoteBackups($tenant);
            
            if ($isVerbose) {
                CLI::write("  ☁️ Remoto - Encontrados: " . count($remoteBackups) . " backups");
            }
            
            $backupsByType = $this->categorizeBackupsByAge($remoteBackups);
            
            foreach ($backupsByType as $type => $backupList) {
                $toDelete = $this->selectBackupsToDelete($backupList, $policy, $type);
                
                foreach ($toDelete as $backup) {
                    if ($isVerbose) {
                        CLI::write("    🗑️ Deletando remoto: {$backup['name']}");
                    }
                    
                    if (!$isDryRun) {
                        $this->remoteStorage->delete($backup['path']);
                    }
                    
                    $this->stats['remote_deleted']++;
                    $this->stats['remote_size_freed'] += $backup['size'];
                }
            }
            
        } catch (Exception $e) {
            CLI::error("  ❌ Erro na limpeza remota: " . $e->getMessage());
            $this->stats['errors'][] = "Limpeza remota do tenant {$tenant}: " . $e->getMessage();
        }
    }
    
    /**
     * Obter backups organizados por tipo (diário, semanal, mensal)
     */
    protected function getBackupsByType(string $tenantDir): array
    {
        $backups = [
            'daily' => [],
            'weekly' => [],
            'monthly' => [],
            'yearly' => []
        ];
        
        $dateDirs = glob($tenantDir . '*', GLOB_ONLYDIR);
        
        foreach ($dateDirs as $dateDir) {
            $date = basename($dateDir);
            $timeDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
            
            foreach ($timeDirs as $timeDir) {
                $manifestFile = $timeDir . '/manifest.json';
                if (!file_exists($manifestFile)) {
                    continue;
                }
                
                $manifest = json_decode(file_get_contents($manifestFile), true);
                $backupDate = new \DateTime($manifest['backup_date']);
                $age = $this->getBackupAge($backupDate);
                
                $backup = [
                    'path' => $timeDir,
                    'date' => $date,
                    'time' => basename($timeDir),
                    'datetime' => $backupDate,
                    'age_days' => $age,
                    'type' => $manifest['backup_type'] ?? 'full',
                    'size' => $this->getDirectorySize($timeDir)
                ];
                
                // Categorizar por idade
                if ($age <= 7) {
                    $backups['daily'][] = $backup;
                } elseif ($age <= 30) {
                    $backups['weekly'][] = $backup;
                } elseif ($age <= 365) {
                    $backups['monthly'][] = $backup;
                } else {
                    $backups['yearly'][] = $backup;
                }
            }
        }
        
        // Ordenar por data (mais recente primeiro)
        foreach ($backups as &$backupList) {
            usort($backupList, function($a, $b) {
                return $b['datetime'] <=> $a['datetime'];
            });
        }
        
        return $backups;
    }
    
    /**
     * Obter backups remotos
     */
    protected function getRemoteBackups(string $tenant): array
    {
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenant);
        $remotePath = "tenant_{$safeTenantId}/";
        
        try {
            $files = $this->remoteStorage->listFiles($remotePath);
            $backups = [];
            
            foreach ($files as $file) {
                if (strpos($file, '.zip') !== false || strpos($file, '.tar.gz') !== false) {
                    $backups[] = [
                        'name' => $file,
                        'path' => $remotePath . $file,
                        'size' => $this->remoteStorage->getSize($remotePath . $file),
                        'date' => $this->extractDateFromFilename($file)
                    ];
                }
            }
            
            return $backups;
            
        } catch (Exception $e) {
            CLI::error("  ⚠️ Erro ao listar backups remotos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Categorizar backups remotos por idade
     */
    protected function categorizeBackupsByAge(array $backups): array
    {
        $categorized = [
            'daily' => [],
            'weekly' => [],
            'monthly' => [],
            'yearly' => []
        ];
        
        foreach ($backups as $backup) {
            $date = new \DateTime($backup['date']);
            $age = $this->getBackupAge($date);
            
            $backup['datetime'] = $date;
            $backup['age_days'] = $age;
            
            if ($age <= 7) {
                $categorized['daily'][] = $backup;
            } elseif ($age <= 30) {
                $categorized['weekly'][] = $backup;
            } elseif ($age <= 365) {
                $categorized['monthly'][] = $backup;
            } else {
                $categorized['yearly'][] = $backup;
            }
        }
        
        return $categorized;
    }
    
    /**
     * Selecionar backups para deletar baseado na política
     */
    protected function selectBackupsToDelete(array $backups, array $policy, string $type): array
    {
        $keepCount = $policy[$type . '_backups'] ?? 0;
        $minKeep = $this->config->retention['min_backups_to_keep'];
        
        // Sempre manter pelo menos o mínimo configurado
        $keepCount = max($keepCount, $minKeep);
        
        if (count($backups) <= $keepCount) {
            return []; // Não deletar nada
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return $b['datetime'] <=> $a['datetime'];
        });
        
        // Retornar os mais antigos para deletar
        return array_slice($backups, $keepCount);
    }
    
    /**
     * Calcular idade do backup em dias
     */
    protected function getBackupAge(\DateTime $backupDate): int
    {
        $now = new \DateTime();
        $diff = $now->diff($backupDate);
        return $diff->days;
    }
    
    /**
     * Extrair data do nome do arquivo
     */
    protected function extractDateFromFilename(string $filename): string
    {
        // Tentar extrair data no formato YYYY-MM-DD do nome do arquivo
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
            return $matches[1];
        }
        
        // Fallback para data atual se não conseguir extrair
        return date('Y-m-d');
    }
    
    /**
     * Exibir relatório de limpeza
     */
    protected function showCleanupReport(float $duration, bool $isDryRun): void
    {
        CLI::newLine();
        CLI::write("📊 RELATÓRIO DE LIMPEZA:", 'green');
        
        if ($isDryRun) {
            CLI::write("🔍 SIMULAÇÃO - Nenhum arquivo foi deletado");
        }
        
        CLI::write("⏱️ Duração: {$duration}s");
        CLI::write("🗑️ Backups locais deletados: " . $this->stats['local_deleted']);
        CLI::write("☁️ Backups remotos deletados: " . $this->stats['remote_deleted']);
        CLI::write("💾 Espaço local liberado: " . $this->formatFileSize($this->stats['local_size_freed']));
        CLI::write("☁️ Espaço remoto liberado: " . $this->formatFileSize($this->stats['remote_size_freed']));
        
        if (!empty($this->stats['errors'])) {
            CLI::newLine();
            CLI::write("❌ ERROS ENCONTRADOS:", 'red');
            foreach ($this->stats['errors'] as $error) {
                CLI::write("  • {$error}");
            }
        }
        
        $totalDeleted = $this->stats['local_deleted'] + $this->stats['remote_deleted'];
        $totalFreed = $this->stats['local_size_freed'] + $this->stats['remote_size_freed'];
        
        CLI::newLine();
        if ($totalDeleted > 0) {
            CLI::write("✅ LIMPEZA CONCLUÍDA: {$totalDeleted} backups removidos, " . 
                      $this->formatFileSize($totalFreed) . " liberados", 'green');
        } else {
            CLI::write("ℹ️ NENHUMA LIMPEZA NECESSÁRIA: Todos os backups estão dentro da política de retenção", 'yellow');
        }
    }
    
    /**
     * Utilitários
     */
    protected function getDirectorySize(string $directory): int
    {
        $size = 0;
        
        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }
    
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
