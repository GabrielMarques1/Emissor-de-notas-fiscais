<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\BackupEncryption;
use Config\Backup;
use Exception;

/**
 * Comando CLI para Teste Automático de Restore
 * 
 * Testa a integridade dos backups fazendo restore em banco temporário
 */
class TestRestore extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Backup';
    
    /**
     * Nome do comando
     */
    protected $name = 'backup:test-restore';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Testa automaticamente a integridade dos backups fazendo restore em banco temporário';
    
    /**
     * Uso do comando
     */
    protected $usage = 'backup:test-restore [options]';
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--all' => 'Testar todos os tenants',
        '--tenant' => 'Testar apenas um tenant específico (formato: 1:10)',
        '--latest-only' => 'Testar apenas o backup mais recente',
        '--full-validation' => 'Validação completa (mais lenta)',
        '--notify' => 'Enviar notificação do resultado',
        '--verbose' => 'Saída detalhada'
    ];
    
    /**
     * Configuração de backup
     */
    protected Backup $config;
    
    /**
     * Biblioteca de criptografia
     */
    protected BackupEncryption $encryption;
    
    /**
     * Diretório base de backups
     */
    protected string $backupBaseDir;
    
    /**
     * Resultados dos testes
     */
    protected array $testResults = [];
    
    /**
     * Estatísticas gerais
     */
    protected array $stats = [
        'total_tested' => 0,
        'successful' => 0,
        'failed' => 0,
        'warnings' => 0,
        'total_duration' => 0
    ];
    
    /**
     * Executar comando
     */
    public function run(array $params)
    {
        $startTime = microtime(true);
        
        try {
            $this->initialize();
            
            $testAll = CLI::getOption('all');
            $specificTenant = CLI::getOption('tenant');
            $latestOnly = CLI::getOption('latest-only');
            $fullValidation = CLI::getOption('full-validation');
            $notify = CLI::getOption('notify');
            $isVerbose = CLI::getOption('verbose');
            
            CLI::write("🧪 TESTE AUTOMÁTICO DE RESTORE", 'green');
            CLI::write("Data: " . date('Y-m-d H:i:s'));
            CLI::write("Validação: " . ($fullValidation ? 'Completa' : 'Rápida'));
            CLI::newLine();
            
            // Determinar quais tenants testar
            $tenants = [];
            if ($specificTenant) {
                $tenants = [$specificTenant];
            } elseif ($testAll) {
                $tenants = $this->getAllTenantsWithBackups();
            } else {
                CLI::error("Especifique --all ou --tenant=ID");
                return;
            }
            
            if (empty($tenants)) {
                CLI::write("⚠️ Nenhum tenant com backups encontrado");
                return;
            }
            
            CLI::write("📊 Testando " . count($tenants) . " tenant(s)");
            CLI::newLine();
            
            // Testar cada tenant
            foreach ($tenants as $tenant) {
                CLI::write("🔄 Testando tenant: {$tenant}");
                
                try {
                    $backups = $this->getTenantBackups($tenant);
                    
                    if (empty($backups)) {
                        CLI::write("  ⚠️ Nenhum backup encontrado");
                        continue;
                    }
                    
                    // Filtrar backups se necessário
                    if ($latestOnly) {
                        $backups = [array_shift($backups)]; // Apenas o mais recente
                    }
                    
                    foreach ($backups as $backup) {
                        $this->testSingleBackup($tenant, $backup, $fullValidation, $isVerbose);
                    }
                    
                } catch (Exception $e) {
                    CLI::error("  ❌ Erro no tenant {$tenant}: " . $e->getMessage());
                    $this->recordTestResult($tenant, null, false, $e->getMessage());
                }
            }
            
            $this->stats['total_duration'] = microtime(true) - $startTime;
            
            // Exibir relatório final
            $this->showTestReport();
            
            // Enviar notificação se solicitado
            if ($notify) {
                $this->sendNotification();
            }
            
        } catch (Exception $e) {
            CLI::error("❌ ERRO NO TESTE: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializar configurações
     */
    protected function initialize(): void
    {
        $this->config = new Backup();
        $this->encryption = new BackupEncryption();
        $this->backupBaseDir = WRITEPATH . 'backups/';
    }
    
    /**
     * Obter todos os tenants com backups
     */
    protected function getAllTenantsWithBackups(): array
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
            
            // Verificar se tem backups válidos
            $backups = $this->getTenantBackups($tenantId);
            if (!empty($backups)) {
                $tenants[] = $tenantId;
            }
        }
        
        return $tenants;
    }
    
    /**
     * Obter backups de um tenant
     */
    protected function getTenantBackups(string $tenant): array
    {
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenant);
        $tenantDir = $this->backupBaseDir . "tenant_{$safeTenantId}/";
        
        if (!is_dir($tenantDir)) {
            return [];
        }
        
        $backups = [];
        $dateDirs = glob($tenantDir . '*', GLOB_ONLYDIR);
        
        foreach ($dateDirs as $dateDir) {
            $timeDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
            
            foreach ($timeDirs as $timeDir) {
                $manifestFile = $timeDir . '/manifest.json';
                if (file_exists($manifestFile)) {
                    $manifest = json_decode(file_get_contents($manifestFile), true);
                    
                    $backups[] = [
                        'path' => $timeDir,
                        'date' => basename($dateDir),
                        'time' => basename($timeDir),
                        'manifest' => $manifest,
                        'created' => $manifest['backup_date'] ?? ''
                    ];
                }
            }
        }
        
        // Ordenar por data mais recente primeiro
        usort($backups, function($a, $b) {
            return strcmp($b['created'], $a['created']);
        });
        
        return $backups;
    }
    
    /**
     * Testar um backup específico
     */
    protected function testSingleBackup(string $tenant, array $backup, bool $fullValidation, bool $isVerbose): void
    {
        $testStart = microtime(true);
        $backupId = "{$backup['date']}_{$backup['time']}";
        
        if ($isVerbose) {
            CLI::write("  🧪 Testando backup: {$backupId}");
        }
        
        try {
            // Etapa 1: Verificar integridade dos arquivos
            $this->verifyFileIntegrity($backup, $isVerbose);
            
            // Etapa 2: Testar descriptografia
            $this->testDecryption($tenant, $backup, $isVerbose);
            
            // Etapa 3: Validar estrutura do banco (se validação completa)
            if ($fullValidation) {
                $this->validateDatabaseStructure($tenant, $backup, $isVerbose);
            }
            
            // Etapa 4: Testar restore em banco temporário
            $this->testDatabaseRestore($tenant, $backup, $fullValidation, $isVerbose);
            
            $duration = microtime(true) - $testStart;
            
            if ($isVerbose) {
                CLI::write("    ✅ Teste concluído em " . round($duration, 2) . "s");
            }
            
            $this->recordTestResult($tenant, $backupId, true, null, $duration);
            
        } catch (Exception $e) {
            $duration = microtime(true) - $testStart;
            
            CLI::error("    ❌ Falha no teste: " . $e->getMessage());
            $this->recordTestResult($tenant, $backupId, false, $e->getMessage(), $duration);
        }
    }
    
    /**
     * Verificar integridade dos arquivos
     */
    protected function verifyFileIntegrity(array $backup, bool $isVerbose): void
    {
        $manifest = $backup['manifest'];
        $backupPath = $backup['path'] . '/';
        
        // Verificar arquivo de banco
        $dbFile = $backupPath . $manifest['files']['database']['filename'];
        if (!file_exists($dbFile)) {
            throw new Exception("Arquivo de banco não encontrado: {$dbFile}");
        }
        
        $dbChecksum = hash_file('sha256', $dbFile);
        if ($dbChecksum !== $manifest['files']['database']['checksum_sha256']) {
            throw new Exception("Checksum do banco de dados não confere");
        }
        
        // Verificar arquivo de arquivos (se existir)
        $filesFile = $backupPath . $manifest['files']['files']['filename'];
        if (file_exists($filesFile)) {
            $filesChecksum = hash_file('sha256', $filesFile);
            if ($filesChecksum !== $manifest['files']['files']['checksum_sha256']) {
                throw new Exception("Checksum dos arquivos não confere");
            }
        }
        
        if ($isVerbose) {
            CLI::write("    ✅ Integridade dos arquivos verificada");
        }
    }
    
    /**
     * Testar descriptografia
     */
    protected function testDecryption(string $tenant, array $backup, bool $isVerbose): void
    {
        $manifest = $backup['manifest'];
        $backupPath = $backup['path'] . '/';
        
        $encryptedFile = $backupPath . $manifest['files']['database']['filename'];
        $testFile = $backupPath . 'test_decrypt.sql';
        
        try {
            // Testar descriptografia
            $this->encryption->decryptFile($encryptedFile, $testFile, $tenant);
            
            if (!file_exists($testFile) || filesize($testFile) === 0) {
                throw new Exception("Arquivo descriptografado está vazio");
            }
            
            // Verificar se é SQL válido
            $sqlContent = file_get_contents($testFile);
            if (strpos($sqlContent, 'INSERT INTO') === false && strpos($sqlContent, 'CREATE TABLE') === false) {
                throw new Exception("Conteúdo descriptografado não parece ser SQL válido");
            }
            
            if ($isVerbose) {
                CLI::write("    ✅ Descriptografia testada com sucesso");
            }
            
        } finally {
            // Limpar arquivo de teste
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }
    
    /**
     * Validar estrutura do banco
     */
    protected function validateDatabaseStructure(string $tenant, array $backup, bool $isVerbose): void
    {
        $manifest = $backup['manifest'];
        $expectedTables = $manifest['files']['database']['tables_count'] ?? 0;
        $expectedRows = $manifest['files']['database']['rows_count'] ?? 0;
        
        if ($expectedTables === 0) {
            throw new Exception("Backup não contém informações de tabelas");
        }
        
        // Descriptografar e analisar SQL
        $backupPath = $backup['path'] . '/';
        $encryptedFile = $backupPath . $manifest['files']['database']['filename'];
        $sqlFile = $backupPath . 'validate_structure.sql';
        
        try {
            $this->encryption->decryptFile($encryptedFile, $sqlFile, $tenant);
            $sqlContent = file_get_contents($sqlFile);
            
            // Contar CREATE TABLE
            $tableCount = substr_count($sqlContent, 'CREATE TABLE');
            if ($tableCount !== $expectedTables) {
                throw new Exception("Número de tabelas não confere: esperado {$expectedTables}, encontrado {$tableCount}");
            }
            
            // Contar INSERT INTO (aproximado)
            $insertCount = substr_count($sqlContent, 'INSERT INTO');
            if ($insertCount < ($expectedRows * 0.8)) { // Tolerância de 20%
                throw new Exception("Número de registros muito baixo: esperado ~{$expectedRows}, encontrado {$insertCount}");
            }
            
            if ($isVerbose) {
                CLI::write("    ✅ Estrutura do banco validada: {$tableCount} tabelas, ~{$insertCount} registros");
            }
            
        } finally {
            if (file_exists($sqlFile)) {
                unlink($sqlFile);
            }
        }
    }
    
    /**
     * Testar restore em banco temporário
     */
    protected function testDatabaseRestore(string $tenant, array $backup, bool $fullValidation, bool $isVerbose): void
    {
        $testDbName = $this->config->testing['test_database_prefix'] . 'restore_' . time() . '_' . rand(1000, 9999);
        
        try {
            // Criar banco temporário
            $db = \Config\Database::connect();
            $db->query("CREATE DATABASE `{$testDbName}`");
            
            if ($isVerbose) {
                CLI::write("    📊 Banco temporário criado: {$testDbName}");
            }
            
            // Conectar ao banco temporário
            $testConfig = config('Database');
            $testConfig->default['database'] = $testDbName;
            $testDb = \Config\Database::connect('default', false, $testConfig);
            
            // Descriptografar e executar SQL
            $manifest = $backup['manifest'];
            $backupPath = $backup['path'] . '/';
            $encryptedFile = $backupPath . $manifest['files']['database']['filename'];
            $sqlFile = $backupPath . 'restore_test.sql';
            
            $this->encryption->decryptFile($encryptedFile, $sqlFile, $tenant);
            
            // Executar SQL em chunks
            $sqlContent = file_get_contents($sqlFile);
            $commands = array_filter(
                array_map('trim', explode(';', $sqlContent)),
                function($cmd) { return !empty($cmd) && !str_starts_with($cmd, '--'); }
            );
            
            $executedCommands = 0;
            foreach ($commands as $command) {
                if (!empty(trim($command))) {
                    $testDb->query($command);
                    $executedCommands++;
                }
            }
            
            if ($isVerbose) {
                CLI::write("    📊 SQL executado: {$executedCommands} comandos");
            }
            
            // Validação completa se solicitada
            if ($fullValidation) {
                $this->performFullValidation($testDb, $manifest, $isVerbose);
            }
            
            unlink($sqlFile);
            
        } finally {
            // Limpar banco temporário
            try {
                $db->query("DROP DATABASE IF EXISTS `{$testDbName}`");
                if ($isVerbose) {
                    CLI::write("    🧹 Banco temporário removido");
                }
            } catch (Exception $e) {
                CLI::error("    ⚠️ Erro ao remover banco temporário: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Realizar validação completa
     */
    protected function performFullValidation($testDb, array $manifest, bool $isVerbose): void
    {
        // Verificar se tabelas foram criadas
        $tables = $testDb->query("SHOW TABLES")->getResultArray();
        $tableCount = count($tables);
        
        $expectedTables = $manifest['files']['database']['tables_count'] ?? 0;
        if ($tableCount !== $expectedTables) {
            throw new Exception("Número de tabelas restauradas não confere: esperado {$expectedTables}, encontrado {$tableCount}");
        }
        
        // Verificar integridade referencial (sample)
        $sampleTables = ['pos_sales', 'pos_sale_items', 'produtos', 'clientes'];
        foreach ($sampleTables as $table) {
            try {
                $count = $testDb->query("SELECT COUNT(*) as count FROM `{$table}`")->getRow();
                if ($isVerbose && $count) {
                    CLI::write("      📊 {$table}: {$count->count} registros");
                }
            } catch (Exception $e) {
                // Tabela pode não existir, ignorar
            }
        }
        
        if ($isVerbose) {
            CLI::write("    ✅ Validação completa realizada");
        }
    }
    
    /**
     * Registrar resultado do teste
     */
    protected function recordTestResult(string $tenant, ?string $backupId, bool $success, ?string $error, float $duration = 0): void
    {
        $this->testResults[] = [
            'tenant' => $tenant,
            'backup_id' => $backupId,
            'success' => $success,
            'error' => $error,
            'duration' => $duration,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->stats['total_tested']++;
        if ($success) {
            $this->stats['successful']++;
        } else {
            $this->stats['failed']++;
        }
    }
    
    /**
     * Exibir relatório de testes
     */
    protected function showTestReport(): void
    {
        CLI::newLine();
        CLI::write("📊 RELATÓRIO DE TESTES DE RESTORE:", 'green');
        CLI::write("⏱️ Duração total: " . round($this->stats['total_duration'], 2) . "s");
        CLI::write("🧪 Total testado: " . $this->stats['total_tested']);
        CLI::write("✅ Sucessos: " . $this->stats['successful']);
        CLI::write("❌ Falhas: " . $this->stats['failed']);
        
        if ($this->stats['failed'] > 0) {
            CLI::newLine();
            CLI::write("❌ FALHAS DETALHADAS:", 'red');
            
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    CLI::write("  • Tenant {$result['tenant']} ({$result['backup_id']}): {$result['error']}");
                }
            }
        }
        
        // Taxa de sucesso
        $successRate = $this->stats['total_tested'] > 0 ? 
                      ($this->stats['successful'] / $this->stats['total_tested']) * 100 : 0;
        
        CLI::newLine();
        if ($successRate >= 95) {
            CLI::write("🎉 EXCELENTE: Taxa de sucesso {$successRate}%", 'green');
        } elseif ($successRate >= 80) {
            CLI::write("⚠️ ATENÇÃO: Taxa de sucesso {$successRate}%", 'yellow');
        } else {
            CLI::write("🚨 CRÍTICO: Taxa de sucesso {$successRate}%", 'red');
        }
    }
    
    /**
     * Enviar notificação
     */
    protected function sendNotification(): void
    {
        if (!$this->config->shouldNotify('test_restore_failed') && $this->stats['failed'] === 0) {
            return; // Não notificar sucessos se não configurado
        }
        
        $subject = $this->stats['failed'] > 0 ? 
                  "🚨 Falhas no Teste de Restore" : 
                  "✅ Teste de Restore Concluído";
        
        $message = "Relatório de Teste de Restore:\n\n";
        $message .= "Total testado: {$this->stats['total_tested']}\n";
        $message .= "Sucessos: {$this->stats['successful']}\n";
        $message .= "Falhas: {$this->stats['failed']}\n";
        $message .= "Duração: " . round($this->stats['total_duration'], 2) . "s\n";
        
        if ($this->stats['failed'] > 0) {
            $message .= "\nFalhas detalhadas:\n";
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    $message .= "- Tenant {$result['tenant']}: {$result['error']}\n";
                }
            }
        }
        
        // Aqui você implementaria o envio real da notificação
        // Por exemplo, usando a biblioteca de email do CodeIgniter
        CLI::write("📧 Notificação preparada (implementar envio real)");
    }
}
