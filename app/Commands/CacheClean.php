<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\TenantCache;
use Config\TenantCache as TenantCacheConfig;
use Exception;

/**
 * Comando CLI para Limpeza de Cache Multi-Tenant
 * 
 * Remove cache expirado e otimiza armazenamento
 */
class CacheClean extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Maintenance';
    
    /**
     * Nome do comando
     */
    protected $name = 'cache:clean';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Limpa cache expirado de todos os tenants';
    
    /**
     * Uso do comando
     */
    protected $usage = 'cache:clean [options]';
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--all' => 'Limpar todo o cache (incluindo válido)',
        '--tenant' => 'Limpar apenas um tenant específico (formato: 1:10)',
        '--expired-only' => 'Limpar apenas cache expirado (padrão)',
        '--older-than' => 'Limpar cache mais antigo que X horas (ex: --older-than=24)',
        '--dry-run' => 'Simular limpeza sem deletar arquivos',
        '--stats' => 'Exibir estatísticas detalhadas',
        '--verbose' => 'Saída detalhada'
    ];
    
    /**
     * Configuração de cache
     */
    protected TenantCacheConfig $config;
    
    /**
     * Diretório de cache
     */
    protected string $cacheDir;
    
    /**
     * Estatísticas da limpeza
     */
    protected array $stats = [
        'total_files' => 0,
        'expired_files' => 0,
        'corrupted_files' => 0,
        'deleted_files' => 0,
        'size_freed' => 0,
        'tenants_processed' => 0,
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
            
            $cleanAll = (bool) CLI::getOption('all');
            $specificTenant = CLI::getOption('tenant');
            $expiredOnly = CLI::getOption('expired-only') || (!$cleanAll && !$specificTenant);
            $olderThan = CLI::getOption('older-than');
            $isDryRun = (bool) CLI::getOption('dry-run');
            $showStats = (bool) CLI::getOption('stats');
            $isVerbose = (bool) CLI::getOption('verbose');
            
            CLI::write("🧹 LIMPEZA DE CACHE MULTI-TENANT", 'green');
            CLI::write("Modo: " . ($isDryRun ? 'Simulação' : 'Execução'));
            CLI::write("Data: " . date('Y-m-d H:i:s'));
            CLI::newLine();
            
            if ($specificTenant) {
                CLI::write("🎯 Limpando tenant específico: {$specificTenant}");
                $this->cleanTenantCache($specificTenant, $cleanAll, $olderThan, $isDryRun, $isVerbose);
            } else {
                CLI::write("🌐 Limpando cache de todos os tenants");
                $this->cleanAllTenantsCache($cleanAll, $olderThan, $isDryRun, $isVerbose);
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            
            // Exibir relatório final
            $this->showCleanupReport($duration, $isDryRun, $showStats);
            
        } catch (Exception $e) {
            CLI::error("❌ ERRO NA LIMPEZA: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializar configurações
     */
    protected function initialize(): void
    {
        $this->config = new TenantCacheConfig();
        $this->cacheDir = WRITEPATH . 'cache/';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Limpar cache de todos os tenants
     */
    protected function cleanAllTenantsCache(bool $cleanAll, ?string $olderThan, bool $isDryRun, bool $isVerbose): void
    {
        $tenants = $this->getAllTenants();
        
        if (empty($tenants)) {
            CLI::write("ℹ️ Nenhum tenant com cache encontrado");
            return;
        }
        
        CLI::write("📊 Processando " . count($tenants) . " tenant(s)");
        CLI::newLine();
        
        foreach ($tenants as $tenant) {
            if ($isVerbose) {
                CLI::write("🔄 Processando tenant: {$tenant}");
            }
            
            try {
                $this->cleanTenantCache($tenant, $cleanAll, $olderThan, $isDryRun, $isVerbose);
                $this->stats['tenants_processed']++;
                
            } catch (Exception $e) {
                CLI::error("  ❌ Erro no tenant {$tenant}: " . $e->getMessage());
                $this->stats['errors'][] = "Tenant {$tenant}: " . $e->getMessage();
            }
        }
    }
    
    /**
     * Limpar cache de um tenant específico
     */
    protected function cleanTenantCache(string $tenantId, bool $cleanAll, ?string $olderThan, bool $isDryRun, bool $isVerbose): void
    {
        $sanitizedTenant = $this->sanitizeTenantId($tenantId);
        $pattern = $this->cacheDir . $sanitizedTenant . '_*.cache';
        $files = glob($pattern);
        
        if (empty($files)) {
            if ($isVerbose) {
                CLI::write("  ℹ️ Nenhum arquivo de cache encontrado");
            }
            return;
        }
        
        $this->stats['total_files'] += count($files);
        
        if ($isVerbose) {
            CLI::write("  📁 Encontrados: " . count($files) . " arquivos");
        }
        
        foreach ($files as $file) {
            $this->processCacheFile($file, $tenantId, $cleanAll, $olderThan, $isDryRun, $isVerbose);
        }
    }
    
    /**
     * Processar arquivo de cache individual
     */
    protected function processCacheFile(string $file, string $tenantId, bool $cleanAll, ?string $olderThan, bool $isDryRun, bool $isVerbose): void
    {
        $filename = basename($file);
        $fileSize = filesize($file);
        $shouldDelete = false;
        $reason = '';
        
        try {
            // Verificar idade do arquivo
            if ($olderThan) {
                $maxAge = (int)$olderThan * 3600; // Converter horas para segundos
                if (time() - filemtime($file) > $maxAge) {
                    $shouldDelete = true;
                    $reason = "mais antigo que {$olderThan}h";
                }
            }
            
            // Se não deve deletar por idade, verificar conteúdo
            if (!$shouldDelete) {
                $serializedData = file_get_contents($file);
                
                if ($serializedData === false) {
                    $shouldDelete = true;
                    $reason = 'arquivo corrompido (não legível)';
                    $this->stats['corrupted_files']++;
                } else {
                    $cacheData = @unserialize($serializedData);
                    
                    if ($cacheData === false) {
                        $shouldDelete = true;
                        $reason = 'dados corrompidos (não deserializável)';
                        $this->stats['corrupted_files']++;
                    } else {
                        // Validar estrutura
                        if (!$this->validateCacheStructure($cacheData, $tenantId)) {
                            $shouldDelete = true;
                            $reason = 'estrutura inválida ou tenant incorreto';
                            $this->stats['corrupted_files']++;
                        } elseif ($this->isCacheExpired($cacheData)) {
                            $shouldDelete = true;
                            $reason = 'cache expirado';
                            $this->stats['expired_files']++;
                        } elseif ($cleanAll) {
                            $shouldDelete = true;
                            $reason = 'limpeza completa solicitada';
                        }
                    }
                }
            }
            
            // Deletar se necessário
            if ($shouldDelete) {
                if ($isVerbose) {
                    CLI::write("    🗑️ Deletando: {$filename} ({$reason})");
                }
                
                if (!$isDryRun) {
                    if (unlink($file)) {
                        $this->stats['deleted_files']++;
                        $this->stats['size_freed'] += $fileSize;
                    }
                } else {
                    $this->stats['deleted_files']++;
                    $this->stats['size_freed'] += $fileSize;
                }
            }
            
        } catch (Exception $e) {
            CLI::error("    ❌ Erro ao processar {$filename}: " . $e->getMessage());
            $this->stats['errors'][] = "Arquivo {$filename}: " . $e->getMessage();
        }
    }
    
    /**
     * Obter todos os tenants com cache
     */
    protected function getAllTenants(): array
    {
        $tenants = [];
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            $filename = basename($file, '.cache');
            
            // Extrair tenant ID do nome do arquivo (formato: tenant_id_key)
            if (preg_match('/^(\d+_\d+)_/', $filename, $matches)) {
                $tenantId = str_replace('_', ':', $matches[1]);
                if (!in_array($tenantId, $tenants)) {
                    $tenants[] = $tenantId;
                }
            }
        }
        
        return $tenants;
    }
    
    /**
     * Validar estrutura do cache
     */
    protected function validateCacheStructure($cacheData, string $expectedTenantId): bool
    {
        if (!is_array($cacheData)) {
            return false;
        }
        
        $requiredFields = ['_tenant_id', '_cached_at', '_expires_at', 'value'];
        foreach ($requiredFields as $field) {
            if (!isset($cacheData[$field])) {
                return false;
            }
        }
        
        // Validar tenant ID
        if ($cacheData['_tenant_id'] !== $expectedTenantId) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar se cache expirou
     */
    protected function isCacheExpired($cacheData): bool
    {
        return time() > $cacheData['_expires_at'];
    }
    
    /**
     * Sanitizar tenant ID para nome de arquivo
     */
    protected function sanitizeTenantId(string $tenantId): string
    {
        return str_replace([':', '/', '\\', ' '], '_', $tenantId);
    }
    
    /**
     * Exibir relatório de limpeza
     */
    protected function showCleanupReport(float $duration, bool $isDryRun, bool $showStats): void
    {
        CLI::newLine();
        CLI::write("📊 RELATÓRIO DE LIMPEZA:", 'green');
        
        if ($isDryRun) {
            CLI::write("🔍 SIMULAÇÃO - Nenhum arquivo foi deletado");
        }
        
        CLI::write("⏱️ Duração: {$duration}s");
        CLI::write("🏢 Tenants processados: " . $this->stats['tenants_processed']);
        CLI::write("📁 Total de arquivos: " . $this->stats['total_files']);
        CLI::write("⏰ Arquivos expirados: " . $this->stats['expired_files']);
        CLI::write("💥 Arquivos corrompidos: " . $this->stats['corrupted_files']);
        CLI::write("🗑️ Arquivos deletados: " . $this->stats['deleted_files']);
        CLI::write("💾 Espaço liberado: " . $this->formatFileSize($this->stats['size_freed']));
        
        if ($showStats) {
            CLI::newLine();
            CLI::write("📈 ESTATÍSTICAS DETALHADAS:", 'yellow');
            
            $totalFiles = $this->stats['total_files'];
            if ($totalFiles > 0) {
                $expiredPercent = round(($this->stats['expired_files'] / $totalFiles) * 100, 1);
                $corruptedPercent = round(($this->stats['corrupted_files'] / $totalFiles) * 100, 1);
                $deletedPercent = round(($this->stats['deleted_files'] / $totalFiles) * 100, 1);
                
                CLI::write("📊 Taxa de expiração: {$expiredPercent}%");
                CLI::write("📊 Taxa de corrupção: {$corruptedPercent}%");
                CLI::write("📊 Taxa de limpeza: {$deletedPercent}%");
                
                $avgFileSize = $totalFiles > 0 ? $this->stats['size_freed'] / $this->stats['deleted_files'] : 0;
                if ($avgFileSize > 0) {
                    CLI::write("📊 Tamanho médio por arquivo: " . $this->formatFileSize($avgFileSize));
                }
            }
        }
        
        if (!empty($this->stats['errors'])) {
            CLI::newLine();
            CLI::write("❌ ERROS ENCONTRADOS:", 'red');
            foreach ($this->stats['errors'] as $error) {
                CLI::write("  • {$error}");
            }
        }
        
        CLI::newLine();
        if ($this->stats['deleted_files'] > 0) {
            CLI::write("✅ LIMPEZA CONCLUÍDA: {$this->stats['deleted_files']} arquivos removidos, " . 
                      $this->formatFileSize($this->stats['size_freed']) . " liberados", 'green');
        } else {
            CLI::write("ℹ️ NENHUMA LIMPEZA NECESSÁRIA: Cache está limpo", 'yellow');
        }
        
        // Recomendações
        if ($this->stats['corrupted_files'] > 0) {
            CLI::newLine();
            CLI::write("⚠️ RECOMENDAÇÃO: Foram encontrados arquivos corrompidos.", 'yellow');
            CLI::write("   Considere investigar possíveis problemas de disco ou memória.");
        }
        
        if ($this->stats['total_files'] > 1000) {
            CLI::newLine();
            CLI::write("💡 DICA: Cache com muitos arquivos pode impactar performance.", 'blue');
            CLI::write("   Considere reduzir TTLs ou executar limpeza mais frequente.");
        }
    }
    
    /**
     * Formatar tamanho de arquivo
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
