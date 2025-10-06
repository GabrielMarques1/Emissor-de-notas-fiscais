<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando CLI para Backup de Todos os Tenants
 * 
 * Executa backup de todos os tenants ativos no sistema
 */
class BackupAllTenants extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Backup';
    
    /**
     * Nome do comando
     */
    protected $name = 'backup:all-tenants';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Executa backup de todos os tenants ativos';
    
    /**
     * Uso do comando
     */
    protected $usage = 'backup:all-tenants [options]';
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--incremental' => 'Backup incremental para todos',
        '--parallel' => 'Executar backups em paralelo (máx 3)',
        '--skip-errors' => 'Continuar mesmo se algum backup falhar',
        '--verbose' => 'Saída detalhada'
    ];
    
    /**
     * Executar comando
     */
    public function run(array $params)
    {
        $startTime = microtime(true);
        
        CLI::write("🔐 BACKUP AUTOMÁTICO DE TODOS OS TENANTS", 'green');
        CLI::write("Data: " . date('Y-m-d H:i:s'));
        CLI::newLine();
        
        try {
            // Obter lista de tenants ativos
            $tenants = $this->getActiveTenants();
            
            if (empty($tenants)) {
                CLI::write("⚠️ Nenhum tenant ativo encontrado");
                return;
            }
            
            CLI::write("📊 Encontrados " . count($tenants) . " tenants ativos");
            CLI::newLine();
            
            $successful = 0;
            $failed = 0;
            $errors = [];
            
            $isParallel = CLI::getOption('parallel');
            $skipErrors = CLI::getOption('skip-errors');
            $isIncremental = (bool) CLI::getOption('incremental');
            
            if ($isParallel) {
                $this->runParallelBackups($tenants, $isIncremental, $skipErrors);
            } else {
                foreach ($tenants as $tenant) {
                    CLI::write("🔄 Processando tenant {$tenant['id_contador']}:{$tenant['id_empresa']}...");
                    
                    try {
                        $this->runSingleBackup($tenant, $isIncremental);
                        $successful++;
                        CLI::write("  ✅ Sucesso");
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Tenant {$tenant['id_contador']}:{$tenant['id_empresa']}: " . $e->getMessage();
                        CLI::error("  ❌ Erro: " . $e->getMessage());
                        
                        if (!$skipErrors) {
                            throw $e;
                        }
                    }
                }
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            
            CLI::newLine();
            CLI::write("📊 RESUMO DO BACKUP AUTOMÁTICO:", 'green');
            CLI::write("✅ Sucessos: {$successful}");
            CLI::write("❌ Falhas: {$failed}");
            CLI::write("⏱️ Duração total: {$duration}s");
            
            if (!empty($errors)) {
                CLI::newLine();
                CLI::write("❌ ERROS ENCONTRADOS:", 'red');
                foreach ($errors as $error) {
                    CLI::write("  • {$error}");
                }
            }
            
        } catch (\Exception $e) {
            CLI::error("❌ ERRO CRÍTICO: " . $e->getMessage());
        }
    }
    
    /**
     * Obter tenants ativos
     */
    protected function getActiveTenants(): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT DISTINCT id_contador, id_empresa, xFant as nome_empresa
            FROM empresas 
            ORDER BY id_contador, id_empresa
            LIMIT 10
        ");
        
        return $query->getResultArray();
    }
    
    /**
     * Executar backup de um tenant
     */
    protected function runSingleBackup(array $tenant, bool $incremental): void
    {
        $sparkPath = ROOTPATH . 'spark';
        $command = "php {$sparkPath} backup:tenant {$tenant['id_contador']} {$tenant['id_empresa']}";
        
        if ($incremental) {
            $command .= " --incremental";
        }
        
        // Adicionar --dry-run se estiver no modo simulação
        if (CLI::getOption('dry-run')) {
            $command .= " --dry-run";
        }
        
        // Executar comando
        exec($command . " 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Comando falhou: " . implode("\n", $output));
        }
    }
    
    /**
     * Executar backups em paralelo
     */
    protected function runParallelBackups(array $tenants, bool $incremental, bool $skipErrors): void
    {
        CLI::write("🚀 Executando backups em paralelo (máx 3 simultâneos)...");
        
        $maxParallel = 3;
        $running = [];
        $completed = 0;
        $failed = 0;
        
        foreach ($tenants as $index => $tenant) {
            // Aguardar slot disponível
            while (count($running) >= $maxParallel) {
                $this->checkRunningProcesses($running, $completed, $failed);
                usleep(500000); // 0.5 segundos
            }
            
            // Iniciar novo processo
            $command = "php spark backup:tenant {$tenant['id_contador']} {$tenant['id_empresa']}";
            if ($incremental) {
                $command .= " --incremental";
            }
            
            $process = proc_open(
                $command,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ],
                $pipes
            );
            
            if ($process) {
                $running[] = [
                    'process' => $process,
                    'pipes' => $pipes,
                    'tenant' => $tenant,
                    'start_time' => time()
                ];
                
                CLI::write("  🔄 Iniciado: {$tenant['id_contador']}:{$tenant['id_empresa']}");
            }
        }
        
        // Aguardar conclusão de todos os processos
        while (!empty($running)) {
            $this->checkRunningProcesses($running, $completed, $failed);
            usleep(500000);
        }
        
        CLI::write("🏁 Backups paralelos concluídos: {$completed} sucessos, {$failed} falhas");
    }
    
    /**
     * Verificar processos em execução
     */
    protected function checkRunningProcesses(array &$running, int &$completed, int &$failed): void
    {
        foreach ($running as $key => $processInfo) {
            $status = proc_get_status($processInfo['process']);
            
            if (!$status['running']) {
                $tenant = $processInfo['tenant'];
                $tenantId = "{$tenant['id_contador']}:{$tenant['id_empresa']}";
                
                if ($status['exitcode'] === 0) {
                    CLI::write("  ✅ Concluído: {$tenantId}");
                    $completed++;
                } else {
                    CLI::error("  ❌ Falhou: {$tenantId}");
                    $failed++;
                }
                
                // Fechar pipes e processo
                foreach ($processInfo['pipes'] as $pipe) {
                    fclose($pipe);
                }
                proc_close($processInfo['process']);
                
                // Remover da lista
                unset($running[$key]);
            }
        }
        
        // Reindexar array
        $running = array_values($running);
    }
}
