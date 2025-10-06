<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\BackupEncryption;
use Exception;

/**
 * Comando CLI para Backup de Tenant Individual
 * 
 * Executa backup isolado por tenant com criptografia AES-256
 * e armazenamento seguro com manifest JSON
 */
class BackupTenant extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Backup';
    
    /**
     * Nome do comando
     */
    protected $name = 'backup:tenant';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Executa backup criptografado de um tenant específico';
    
    /**
     * Uso do comando
     */
    protected $usage = 'backup:tenant <id_contador> <id_empresa> [options]';
    
    /**
     * Argumentos do comando
     */
    protected $arguments = [
        'id_contador' => 'ID do contador (tenant)',
        'id_empresa' => 'ID da empresa dentro do tenant'
    ];
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--full' => 'Backup completo (padrão)',
        '--incremental' => 'Backup incremental (últimas 24h)',
        '--upload' => 'Upload para FTP/SFTP após backup',
        '--test-restore' => 'Testar restore após backup',
        '--compress' => 'Comprimir arquivos antes da criptografia',
        '--verbose' => 'Saída detalhada'
    ];
    
    /**
     * Biblioteca de criptografia
     */
    protected BackupEncryption $encryption;
    
    /**
     * Configurações de backup
     */
    protected array $config;
    
    /**
     * Diretório base de backups
     */
    protected string $backupBaseDir;
    
    /**
     * Log do backup atual
     */
    protected array $backupLog = [];
    
    /**
     * Executar comando
     */
    public function run(array $params)
    {
        $startTime = microtime(true);
        
        try {
            // Inicializar
            $this->initialize();
            
            // Validar parâmetros
            $idContador = $params[0] ?? null;
            $idEmpresa = $params[1] ?? null;
            
            if (!$idContador || !$idEmpresa) {
                CLI::error('Parâmetros obrigatórios: id_contador e id_empresa');
                CLI::write($this->usage);
                return;
            }
            
            $tenantId = "{$idContador}:{$idEmpresa}";
            $isIncremental = (bool) CLI::getOption('incremental');
            $isVerbose = (bool) CLI::getOption('verbose');
            
            CLI::write("🔐 BACKUP CRIPTOGRAFADO DE TENANT", 'green');
            CLI::write("Tenant: {$tenantId}");
            CLI::write("Tipo: " . ($isIncremental ? 'Incremental' : 'Completo'));
            CLI::write("Data: " . date('Y-m-d H:i:s'));
            CLI::newLine();
            
            // Verificar se tenant existe
            if (!$this->validateTenant($idContador, $idEmpresa)) {
                CLI::error("Tenant {$tenantId} não encontrado no banco de dados");
                return;
            }
            
            // Criar estrutura de diretórios
            $backupDir = $this->createBackupDirectory($tenantId);
            
            // Inicializar log
            $this->initializeBackupLog($tenantId, $isIncremental ? 'incremental' : 'full');
            
            // Gerar/verificar chave de criptografia
            $this->ensureEncryptionKey($tenantId);
            
            // Executar backup do banco de dados
            CLI::write("📊 Executando backup do banco de dados...");
            
            // Para --dry-run, apenas simular
            if (CLI::getOption('dry-run')) {
                CLI::write("  🔍 SIMULAÇÃO: Backup do banco seria executado");
                $databaseBackup = [
                    'filename' => 'database.sql.enc', 
                    'size' => 1024, 
                    'encrypted_size' => 1200,
                    'tables' => 3,
                    'checksum' => 'abc123'
                ];
            } else {
                $databaseBackup = $this->backupDatabase($tenantId, $backupDir, $isIncremental, $isVerbose);
            }
            
            // Executar backup de arquivos
            CLI::write("📁 Executando backup de arquivos...");
            
            // Para --dry-run, apenas simular
            if (CLI::getOption('dry-run')) {
                CLI::write("  🔍 SIMULAÇÃO: Backup de arquivos seria executado");
                $filesBackup = [
                    'filename' => 'files.tar.enc', 
                    'size' => 2048, 
                    'encrypted_size' => 2200,
                    'files_count' => 150,
                    'checksum' => 'def456'
                ];
            } else {
                $filesBackup = $this->backupFiles($tenantId, $backupDir, $isVerbose);
            }
            
            // Gerar manifest
            CLI::write("📋 Gerando manifest...");
            
            // Para --dry-run, apenas simular
            if (CLI::getOption('dry-run')) {
                CLI::write("  🔍 SIMULAÇÃO: Manifest seria gerado");
                CLI::write("✅ BACKUP SIMULADO CONCLUÍDO COM SUCESSO!");
                CLI::write("📊 Resumo da simulação:");
                CLI::write("  - Banco: {$databaseBackup['filename']} ({$databaseBackup['size']} bytes)");
                CLI::write("  - Arquivos: {$filesBackup['filename']} ({$filesBackup['size']} bytes)");
                CLI::write("  - Total: " . ($databaseBackup['size'] + $filesBackup['size']) . " bytes");
                return;
            }
            
            $manifest = $this->generateManifest($tenantId, $databaseBackup, $filesBackup);
            $this->saveManifest($backupDir, $manifest);
            
            // Salvar log
            $this->saveBackupLog($backupDir);
            
            // Teste de restore se solicitado
            if (CLI::getOption('test-restore')) {
                CLI::write("🧪 Testando restore...");
                $this->testRestore($backupDir, $tenantId);
            }
            
            // Upload se solicitado
            if (CLI::getOption('upload')) {
                CLI::write("☁️ Fazendo upload...");
                $this->uploadBackup($backupDir);
            }
            
            // Limpeza de backups antigos
            $this->cleanupOldBackups($tenantId);
            
            $duration = round(microtime(true) - $startTime, 2);
            
            CLI::newLine();
            CLI::write("✅ BACKUP CONCLUÍDO COM SUCESSO!", 'green');
            CLI::write("Diretório: {$backupDir}");
            CLI::write("Duração: {$duration}s");
            CLI::write("Banco: " . $this->formatFileSize($databaseBackup['encrypted_size']));
            CLI::write("Arquivos: " . $this->formatFileSize($filesBackup['encrypted_size']));
            CLI::newLine();
            
        } catch (Exception $e) {
            CLI::error("❌ ERRO NO BACKUP: " . $e->getMessage());
            CLI::error("Arquivo: " . $e->getFile() . " Linha: " . $e->getLine());
            
            // Log do erro
            $this->logError($e);
        }
    }
    
    /**
     * Inicializar configurações
     */
    protected function initialize(): void
    {
        $this->encryption = new BackupEncryption();
        $this->backupBaseDir = WRITEPATH . 'backups/';
        
        // Criar diretório base se não existir
        if (!is_dir($this->backupBaseDir)) {
            mkdir($this->backupBaseDir, 0755, true);
        }
        
        // Configurações padrão
        $this->config = [
            'retention_days' => 30,
            'max_backups_per_tenant' => 10,
            'compression_level' => 6,
            'chunk_size' => 1024 * 1024, // 1MB
            'timeout' => 3600, // 1 hora
        ];
    }
    
    /**
     * Validar se tenant existe
     */
    protected function validateTenant(string $idContador, string $idEmpresa): bool
    {
        $db = \Config\Database::connect();
        
        $query = $db->query(
            "SELECT COUNT(*) as count FROM empresas WHERE id_contador = ? AND id_empresa = ?",
            [$idContador, $idEmpresa]
        );
        
        $result = $query->getRow();
        return $result && $result->count > 0;
    }
    
    /**
     * Criar diretório de backup
     */
    protected function createBackupDirectory(string $tenantId): string
    {
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
        $date = date('Y-m-d');
        $time = date('H-i-s');
        
        $backupDir = $this->backupBaseDir . "tenant_{$safeTenantId}/{$date}/{$time}/";
        
        if (!mkdir($backupDir, 0755, true)) {
            throw new Exception("Não foi possível criar diretório de backup: {$backupDir}");
        }
        
        return $backupDir;
    }
    
    /**
     * Garantir chave de criptografia
     */
    protected function ensureEncryptionKey(string $tenantId): void
    {
        try {
            // Tentar obter chave existente
            $this->encryption->getTenantKey($tenantId);
            CLI::write("🔑 Usando chave existente para tenant {$tenantId}");
        } catch (Exception $e) {
            // Gerar nova chave
            $this->encryption->generateTenantKey($tenantId);
            CLI::write("🔑 Nova chave gerada para tenant {$tenantId}");
        }
        
        // Verificar integridade da chave
        if (!$this->encryption->verifyTenantKey($tenantId)) {
            throw new Exception("Chave de criptografia inválida para tenant {$tenantId}");
        }
    }
    
    /**
     * Backup do banco de dados
     */
    protected function backupDatabase(string $tenantId, string $backupDir, bool $incremental, bool $verbose): array
    {
        list($idContador, $idEmpresa) = explode(':', $tenantId);
        
        $db = \Config\Database::connect();
        $sqlFile = $backupDir . 'database.sql';
        $encryptedFile = $backupDir . 'database.sql.enc';
        
        // Tabelas para backup (versão simplificada para teste)
        $tables = ['empresas', 'produtos', 'vendas'];
        
        $handle = fopen($sqlFile, 'w');
        if (!$handle) {
            throw new Exception("Não foi possível criar arquivo SQL");
        }
        
        // Header do SQL
        fwrite($handle, "-- Backup do Tenant {$tenantId}\n");
        fwrite($handle, "-- Data: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Tipo: " . ($incremental ? 'Incremental' : 'Completo') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        
        $totalRows = 0;
        $totalTables = 0;
        
        foreach ($tables as $table => $config) {
            if ($verbose) {
                CLI::write("  📊 Processando tabela: {$table}");
            }
            
            // Construir WHERE clause
            $whereClause = "WHERE id_contador = {$idContador}";
            if (isset($config['empresa_field'])) {
                $whereClause .= " AND {$config['empresa_field']} = {$idEmpresa}";
            }
            
            // Filtro incremental
            if ($incremental && isset($config['date_field'])) {
                $whereClause .= " AND {$config['date_field']} >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            }
            
            // Obter estrutura da tabela
            if (!$incremental) {
                $createQuery = $db->query("SHOW CREATE TABLE {$table}");
                $createResult = $createQuery->getRow();
                if ($createResult) {
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                    fwrite($handle, $createResult->{'Create Table'} . ";\n\n");
                }
            }
            
            // Obter dados
            $dataQuery = $db->query("SELECT * FROM {$table} {$whereClause}");
            $rows = $dataQuery->getResult();
            
            if (!empty($rows)) {
                fwrite($handle, "-- Dados da tabela {$table}\n");
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $db->escapeString($value) . "'";
                        }
                    }
                    
                    $columns = implode('`, `', array_keys((array)$row));
                    $valuesStr = implode(', ', $values);
                    
                    fwrite($handle, "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$valuesStr});\n");
                    $totalRows++;
                }
                
                fwrite($handle, "\n");
            }
            
            $totalTables++;
        }
        
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
        
        // Criptografar arquivo SQL
        $encryptionResult = $this->encryption->encryptFile($sqlFile, $encryptedFile, $tenantId);
        
        // Remover arquivo não criptografado
        unlink($sqlFile);
        
        CLI::write("  ✅ Banco: {$totalTables} tabelas, {$totalRows} registros");
        
        return array_merge($encryptionResult, [
            'tables_count' => $totalTables,
            'rows_count' => $totalRows,
            'filename' => 'database.sql.enc'
        ]);
    }
    
    /**
     * Backup de arquivos
     */
    protected function backupFiles(string $tenantId, string $backupDir, bool $verbose): array
    {
        list($idContador, $idEmpresa) = explode(':', $tenantId);
        
        $filesDir = ROOTPATH . "public/uploads/tenant_{$idContador}_{$idEmpresa}/";
        $tarFile = $backupDir . 'files.tar.gz';
        $encryptedFile = $backupDir . 'files.tar.gz.enc';
        
        $filesCount = 0;
        $totalSize = 0;
        
        if (is_dir($filesDir)) {
            // Criar arquivo tar.gz
            $command = "tar -czf \"{$tarFile}\" -C \"" . dirname($filesDir) . "\" \"" . basename($filesDir) . "\"";
            
            if ($verbose) {
                CLI::write("  📁 Comprimindo arquivos...");
            }
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                // Fallback: usar ZipArchive se tar não estiver disponível
                $this->createZipBackup($filesDir, $tarFile);
            }
            
            if (file_exists($tarFile)) {
                $totalSize = filesize($tarFile);
                $filesCount = $this->countFilesInDirectory($filesDir);
                
                // Criptografar arquivo
                $encryptionResult = $this->encryption->encryptFile($tarFile, $encryptedFile, $tenantId);
                
                // Remover arquivo não criptografado
                unlink($tarFile);
                
                CLI::write("  ✅ Arquivos: {$filesCount} arquivos");
            } else {
                throw new Exception("Erro ao criar arquivo de backup dos arquivos");
            }
        } else {
            CLI::write("  ⚠️ Diretório de arquivos não encontrado: {$filesDir}");
            
            // Criar arquivo vazio criptografado
            file_put_contents($tarFile, '');
            $encryptionResult = $this->encryption->encryptFile($tarFile, $encryptedFile, $tenantId);
            unlink($tarFile);
        }
        
        return array_merge($encryptionResult ?? [], [
            'files_count' => $filesCount,
            'original_directory' => $filesDir,
            'filename' => 'files.tar.gz.enc'
        ]);
    }
    
    /**
     * Gerar manifest JSON
     */
    protected function generateManifest(string $tenantId, array $databaseBackup, array $filesBackup): array
    {
        return [
            'tenant_id' => $tenantId,
            'backup_date' => date('c'),
            'backup_type' => CLI::getOption('incremental') ? 'incremental' : 'full',
            'database_version' => $this->getDatabaseVersion(),
            'php_version' => PHP_VERSION,
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'files' => [
                'database' => [
                    'filename' => $databaseBackup['filename'],
                    'size_bytes' => $databaseBackup['encrypted_size'],
                    'original_size_bytes' => $databaseBackup['original_size'],
                    'checksum_sha256' => $databaseBackup['checksum_sha256'],
                    'tables_count' => $databaseBackup['tables_count'],
                    'rows_count' => $databaseBackup['rows_count'],
                    'encryption_time' => $databaseBackup['encryption_time']
                ],
                'files' => [
                    'filename' => $filesBackup['filename'],
                    'size_bytes' => $filesBackup['encrypted_size'] ?? 0,
                    'original_size_bytes' => $filesBackup['original_size'] ?? 0,
                    'checksum_sha256' => $filesBackup['checksum_sha256'] ?? '',
                    'files_count' => $filesBackup['files_count'],
                    'encryption_time' => $filesBackup['encryption_time'] ?? 0
                ]
            ],
            'encryption' => [
                'algorithm' => BackupEncryption::CIPHER_METHOD,
                'key_file' => "keys/tenant_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId) . ".key"
            ],
            'created_by' => 'BackupTenant CLI Command',
            'hostname' => gethostname(),
            'backup_log' => $this->backupLog
        ];
    }
    
    /**
     * Salvar manifest
     */
    protected function saveManifest(string $backupDir, array $manifest): void
    {
        $manifestFile = $backupDir . 'manifest.json';
        
        if (file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Erro ao salvar manifest");
        }
    }
    
    /**
     * Obter tabelas para backup
     */
    protected function getTablesToBackup(): array
    {
        return [
            'pos_sales' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ],
            'pos_sale_items' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'created_at'
            ],
            'produtos' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ],
            'clientes' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ],
            'fornecedores' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ],
            'estoque' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ],
            'movimentacoes_estoque' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'data_movimentacao'
            ],
            'pagamentos' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'created_at'
            ],
            'caixa_movimentos' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'data_movimento'
            ],
            'usuarios' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ],
            'configuracoes' => [
                'empresa_field' => 'id_empresa',
                'date_field' => 'updated_at'
            ]
        ];
    }
    
    /**
     * Inicializar log de backup
     */
    protected function initializeBackupLog(string $tenantId, string $type): void
    {
        $this->backupLog = [
            'start_time' => date('c'),
            'tenant_id' => $tenantId,
            'backup_type' => $type,
            'steps' => []
        ];
    }
    
    /**
     * Adicionar entrada ao log
     */
    protected function addLogEntry(string $step, string $status, array $details = []): void
    {
        $this->backupLog['steps'][] = [
            'step' => $step,
            'status' => $status,
            'timestamp' => date('c'),
            'details' => $details
        ];
    }
    
    /**
     * Salvar log de backup
     */
    protected function saveBackupLog(string $backupDir): void
    {
        $this->backupLog['end_time'] = date('c');
        $this->backupLog['duration'] = strtotime($this->backupLog['end_time']) - strtotime($this->backupLog['start_time']);
        
        $logFile = $backupDir . 'backup.log';
        file_put_contents($logFile, json_encode($this->backupLog, JSON_PRETTY_PRINT));
    }
    
    /**
     * Testar restore
     */
    protected function testRestore(string $backupDir, string $tenantId): void
    {
        // Implementar teste básico de descriptografia
        $databaseFile = $backupDir . 'database.sql.enc';
        $testFile = $backupDir . 'test_restore.sql';
        
        try {
            $this->encryption->decryptFile($databaseFile, $testFile, $tenantId);
            
            // Verificar se arquivo foi descriptografado corretamente
            if (file_exists($testFile) && filesize($testFile) > 0) {
                CLI::write("  ✅ Teste de restore: OK");
                unlink($testFile);
            } else {
                throw new Exception("Arquivo descriptografado está vazio");
            }
        } catch (Exception $e) {
            CLI::error("  ❌ Teste de restore falhou: " . $e->getMessage());
        }
    }
    
    /**
     * Upload do backup
     */
    protected function uploadBackup(string $backupDir): void
    {
        CLI::write("  ⚠️ Upload não implementado nesta versão");
        // TODO: Implementar upload FTP/SFTP
    }
    
    /**
     * Limpeza de backups antigos
     */
    protected function cleanupOldBackups(string $tenantId): void
    {
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
        $tenantBackupDir = $this->backupBaseDir . "tenant_{$safeTenantId}/";
        
        if (!is_dir($tenantBackupDir)) {
            return;
        }
        
        $retentionDays = $this->config['retention_days'];
        $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
        
        $directories = glob($tenantBackupDir . '*', GLOB_ONLYDIR);
        $deletedCount = 0;
        
        foreach ($directories as $dir) {
            $dirDate = basename($dir);
            
            if ($dirDate < $cutoffDate) {
                $this->deleteDirectory($dir);
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            CLI::write("🧹 Removidos {$deletedCount} backups antigos");
        }
    }
    
    /**
     * Utilitários
     */
    protected function getDatabaseVersion(): string
    {
        $db = \Config\Database::connect();
        $query = $db->query("SELECT VERSION() as version");
        $result = $query->getRow();
        return $result ? $result->version : 'Unknown';
    }
    
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    protected function countFilesInDirectory(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        return iterator_count($iterator);
    }
    
    protected function createZipBackup(string $sourceDir, string $zipFile): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Não foi possível criar arquivo ZIP");
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($sourceDir, '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
        
        $zip->close();
    }
    
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    protected function logError(Exception $e): void
    {
        $errorLog = [
            'timestamp' => date('c'),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        
        $logFile = WRITEPATH . 'logs/backup_errors_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($errorLog) . "\n", FILE_APPEND | LOCK_EX);
    }
}
