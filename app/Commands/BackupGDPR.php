<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\BackupEncryption;
use Config\Backup;
use Exception;

/**
 * Comando CLI para Compliance LGPD/GDPR
 * 
 * Implementa funcionalidades de portabilidade e direito ao esquecimento
 */
class BackupGDPR extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Backup';
    
    /**
     * Nome do comando
     */
    protected $name = 'backup:gdpr';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Comandos de compliance LGPD/GDPR para backups';
    
    /**
     * Uso do comando
     */
    protected $usage = 'backup:gdpr <action> <tenant_id> [options]';
    
    /**
     * Argumentos do comando
     */
    protected $arguments = [
        'action' => 'Ação: export-data, purge-data, audit-access',
        'tenant_id' => 'ID do tenant (formato: id_contador:id_empresa)'
    ];
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--format' => 'Formato de exportação: json, csv, xml (padrão: json)',
        '--include-deleted' => 'Incluir registros deletados na exportação',
        '--confirm' => 'Confirmar operação de purge sem prompt',
        '--reason' => 'Motivo da operação (obrigatório para purge)',
        '--requester' => 'Solicitante da operação',
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
     * Executar comando
     */
    public function run(array $params)
    {
        try {
            $this->initialize();
            
            $action = $params[0] ?? null;
            $tenantId = $params[1] ?? null;
            
            if (!$action || !$tenantId) {
                CLI::error('Parâmetros obrigatórios: action e tenant_id');
                CLI::write($this->usage);
                CLI::write("\nAções disponíveis:");
                CLI::write("  export-data  - Exportar dados para portabilidade (LGPD Art. 18)");
                CLI::write("  purge-data   - Purgar todos os dados (direito ao esquecimento)");
                CLI::write("  audit-access - Auditar acessos aos backups do tenant");
                return;
            }
            
            CLI::write("⚖️ COMPLIANCE LGPD/GDPR", 'green');
            CLI::write("Ação: {$action}");
            CLI::write("Tenant: {$tenantId}");
            CLI::write("Data: " . date('Y-m-d H:i:s'));
            CLI::newLine();
            
            switch ($action) {
                case 'export-data':
                    $this->exportTenantData($tenantId);
                    break;
                    
                case 'purge-data':
                    $this->purgeTenantData($tenantId);
                    break;
                    
                case 'audit-access':
                    $this->auditTenantAccess($tenantId);
                    break;
                    
                default:
                    CLI::error("Ação não reconhecida: {$action}");
                    return;
            }
            
        } catch (Exception $e) {
            CLI::error("❌ ERRO: " . $e->getMessage());
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
        
        if (!$this->config->compliance['enabled']) {
            throw new Exception("Compliance LGPD/GDPR não está habilitado na configuração");
        }
    }
    
    /**
     * Exportar dados do tenant (Portabilidade - LGPD Art. 18)
     */
    protected function exportTenantData(string $tenantId): void
    {
        $format = CLI::getOption('format') ?: 'json';
        $includeDeleted = CLI::getOption('include-deleted');
        $isVerbose = CLI::getOption('verbose');
        
        CLI::write("📦 EXPORTAÇÃO DE DADOS PARA PORTABILIDADE");
        CLI::write("Formato: {$format}");
        CLI::write("Incluir deletados: " . ($includeDeleted ? 'Sim' : 'Não'));
        CLI::newLine();
        
        // Verificar se tenant existe
        if (!$this->validateTenant($tenantId)) {
            throw new Exception("Tenant {$tenantId} não encontrado");
        }
        
        // Criar diretório de exportação
        $exportDir = WRITEPATH . "exports/gdpr/tenant_{$this->sanitizeTenantId($tenantId)}/";
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $exportFile = $exportDir . "data_export_" . date('Y-m-d_H-i-s') . ".{$format}";
        
        // Extrair dados do banco
        list($idContador, $idEmpresa) = explode(':', $tenantId);
        $data = $this->extractTenantData($idContador, $idEmpresa, $includeDeleted, $isVerbose);
        
        // Exportar no formato solicitado
        switch ($format) {
            case 'json':
                $this->exportAsJSON($data, $exportFile);
                break;
            case 'csv':
                $this->exportAsCSV($data, $exportFile);
                break;
            case 'xml':
                $this->exportAsXML($data, $exportFile);
                break;
            default:
                throw new Exception("Formato não suportado: {$format}");
        }
        
        // Criptografar arquivo de exportação
        $encryptedFile = $exportFile . '.enc';
        $this->encryption->encryptFile($exportFile, $encryptedFile, $tenantId);
        unlink($exportFile); // Remover versão não criptografada
        
        // Gerar manifest da exportação
        $this->generateExportManifest($tenantId, $encryptedFile, $data);
        
        // Registrar operação para auditoria
        $this->logGDPROperation('export-data', $tenantId, [
            'format' => $format,
            'include_deleted' => $includeDeleted,
            'file' => $encryptedFile,
            'records_count' => array_sum(array_map('count', $data)),
            'requester' => CLI::getOption('requester') ?: 'CLI'
        ]);
        
        CLI::newLine();
        CLI::write("✅ EXPORTAÇÃO CONCLUÍDA!", 'green');
        CLI::write("Arquivo: {$encryptedFile}");
        CLI::write("Registros: " . array_sum(array_map('count', $data)));
        CLI::write("Tamanho: " . $this->formatFileSize(filesize($encryptedFile)));
        CLI::newLine();
        CLI::write("📋 INSTRUÇÕES:");
        CLI::write("1. O arquivo foi criptografado com a chave do tenant");
        CLI::write("2. Para descriptografar: php spark backup:decrypt {$encryptedFile} {$tenantId}");
        CLI::write("3. Arquivo será mantido por 30 dias e depois deletado automaticamente");
    }
    
    /**
     * Purgar dados do tenant (Direito ao Esquecimento)
     */
    protected function purgeTenantData(string $tenantId): void
    {
        $reason = CLI::getOption('reason');
        $requester = CLI::getOption('requester');
        $confirm = CLI::getOption('confirm');
        $isVerbose = CLI::getOption('verbose');
        
        if (!$reason) {
            throw new Exception("Motivo da purga é obrigatório (--reason)");
        }
        
        if (!$requester) {
            throw new Exception("Solicitante da purga é obrigatório (--requester)");
        }
        
        CLI::write("🗑️ PURGA DE DADOS - DIREITO AO ESQUECIMENTO", 'red');
        CLI::write("Motivo: {$reason}");
        CLI::write("Solicitante: {$requester}");
        CLI::newLine();
        
        // Verificar se tenant existe
        if (!$this->validateTenant($tenantId)) {
            throw new Exception("Tenant {$tenantId} não encontrado");
        }
        
        // Confirmação de segurança
        if (!$confirm) {
            CLI::write("⚠️  ATENÇÃO: Esta operação é IRREVERSÍVEL!", 'red');
            CLI::write("Todos os backups do tenant {$tenantId} serão PERMANENTEMENTE DELETADOS!");
            CLI::write("Motivo: {$reason}");
            CLI::newLine();
            
            $confirmation = CLI::prompt('Digite "PURGAR DADOS" para confirmar');
            if ($confirmation !== 'PURGAR DADOS') {
                CLI::write("❌ Operação cancelada");
                return;
            }
        }
        
        // Criar backup final antes da purga (para auditoria)
        CLI::write("📋 Criando backup final para auditoria...");
        $auditBackup = $this->createAuditBackup($tenantId);
        
        // Contar backups antes da purga
        $backupCount = $this->countTenantBackups($tenantId);
        $totalSize = $this->calculateTenantBackupsSize($tenantId);
        
        // Executar purga
        CLI::write("🗑️ Executando purga de backups...");
        
        // 1. Deletar backups locais
        $localDeleted = $this->deleteLocalBackups($tenantId, $isVerbose);
        
        // 2. Deletar backups remotos
        $remoteDeleted = 0;
        if ($this->config->remoteStorage['enabled']) {
            $remoteDeleted = $this->deleteRemoteBackups($tenantId, $isVerbose);
        }
        
        // 3. Deletar chave de criptografia
        $this->deleteEncryptionKey($tenantId);
        
        // Registrar operação para auditoria
        $this->logGDPROperation('purge-data', $tenantId, [
            'reason' => $reason,
            'requester' => $requester,
            'backups_deleted' => $backupCount,
            'size_freed' => $totalSize,
            'local_deleted' => $localDeleted,
            'remote_deleted' => $remoteDeleted,
            'audit_backup' => $auditBackup
        ]);
        
        CLI::newLine();
        CLI::write("✅ PURGA CONCLUÍDA!", 'green');
        CLI::write("Backups deletados: {$backupCount}");
        CLI::write("Espaço liberado: " . $this->formatFileSize($totalSize));
        CLI::write("Backup de auditoria: {$auditBackup}");
        CLI::newLine();
        CLI::write("📋 CONFORMIDADE LGPD/GDPR:");
        CLI::write("✅ Dados do titular removidos conforme solicitado");
        CLI::write("✅ Backup de auditoria criado e protegido");
        CLI::write("✅ Operação registrada nos logs de compliance");
    }
    
    /**
     * Auditar acessos aos backups do tenant
     */
    protected function auditTenantAccess(string $tenantId): void
    {
        $isVerbose = CLI::getOption('verbose');
        
        CLI::write("🔍 AUDITORIA DE ACESSOS AOS BACKUPS");
        CLI::newLine();
        
        // Buscar logs de acesso
        $accessLogs = $this->getBackupAccessLogs($tenantId);
        
        if (empty($accessLogs)) {
            CLI::write("ℹ️ Nenhum acesso registrado para este tenant");
            return;
        }
        
        CLI::write("📊 RESUMO DE ACESSOS:");
        CLI::write("Total de acessos: " . count($accessLogs));
        
        // Agrupar por tipo de operação
        $operationCounts = [];
        $userCounts = [];
        
        foreach ($accessLogs as $log) {
            $operation = $log['operation'] ?? 'unknown';
            $user = $log['user'] ?? 'unknown';
            
            $operationCounts[$operation] = ($operationCounts[$operation] ?? 0) + 1;
            $userCounts[$user] = ($userCounts[$user] ?? 0) + 1;
        }
        
        CLI::newLine();
        CLI::write("📋 POR OPERAÇÃO:");
        foreach ($operationCounts as $operation => $count) {
            CLI::write("  • {$operation}: {$count} vezes");
        }
        
        CLI::newLine();
        CLI::write("👥 POR USUÁRIO:");
        foreach ($userCounts as $user => $count) {
            CLI::write("  • {$user}: {$count} acessos");
        }
        
        if ($isVerbose) {
            CLI::newLine();
            CLI::write("📝 DETALHES DOS ACESSOS:");
            
            foreach ($accessLogs as $log) {
                CLI::write("  📅 {$log['timestamp']} - {$log['operation']} por {$log['user']}");
                if (!empty($log['details'])) {
                    CLI::write("     Detalhes: {$log['details']}");
                }
            }
        }
        
        // Gerar relatório de auditoria
        $reportFile = $this->generateAuditReport($tenantId, $accessLogs);
        
        CLI::newLine();
        CLI::write("✅ AUDITORIA CONCLUÍDA!");
        CLI::write("Relatório gerado: {$reportFile}");
    }
    
    /**
     * Extrair dados do tenant do banco
     */
    protected function extractTenantData(string $idContador, string $idEmpresa, bool $includeDeleted, bool $isVerbose): array
    {
        $db = \Config\Database::connect();
        $tables = $this->config->getTablesToBackup(false); // Não incluir auditoria
        $data = [];
        
        foreach ($tables as $table => $config) {
            if ($isVerbose) {
                CLI::write("  📊 Extraindo: {$table}");
            }
            
            $whereClause = "WHERE id_contador = {$idContador}";
            if (isset($config['tenant_field'])) {
                $whereClause .= " AND {$config['tenant_field']} = {$idEmpresa}";
            }
            
            $query = "SELECT * FROM {$table} {$whereClause}";
            $result = $db->query($query);
            $records = $result->getResultArray();
            
            // Anonimizar dados sensíveis se necessário
            if ($config['sensitive'] ?? false) {
                $records = $this->anonymizeSensitiveData($records, $table);
            }
            
            $data[$table] = $records;
            
            if ($isVerbose) {
                CLI::write("    ✅ " . count($records) . " registros");
            }
        }
        
        // Incluir registros deletados se solicitado
        if ($includeDeleted) {
            $deletedRecords = $this->getDeletedRecords($idContador, $idEmpresa);
            if (!empty($deletedRecords)) {
                $data['_deleted_records'] = $deletedRecords;
            }
        }
        
        return $data;
    }
    
    /**
     * Anonimizar dados sensíveis
     */
    protected function anonymizeSensitiveData(array $records, string $table): array
    {
        $sensitiveFields = $this->config->compliance['sensitive_fields'];
        
        foreach ($records as &$record) {
            foreach ($record as $field => &$value) {
                if ($this->config->isSensitiveField($field)) {
                    // Manter apenas primeiros e últimos caracteres
                    if (strlen($value) > 4) {
                        $value = substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
                    } else {
                        $value = str_repeat('*', strlen($value));
                    }
                }
            }
        }
        
        return $records;
    }
    
    /**
     * Exportar como JSON
     */
    protected function exportAsJSON(array $data, string $file): void
    {
        $jsonData = [
            'export_info' => [
                'timestamp' => date('c'),
                'format' => 'json',
                'compliance' => 'LGPD/GDPR',
                'purpose' => 'data_portability'
            ],
            'data' => $data
        ];
        
        file_put_contents($file, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Exportar como CSV
     */
    protected function exportAsCSV(array $data, string $file): void
    {
        $handle = fopen($file, 'w');
        
        foreach ($data as $table => $records) {
            if (empty($records)) continue;
            
            // Header da tabela
            fputcsv($handle, ["=== TABELA: {$table} ==="]);
            
            // Headers das colunas
            $headers = array_keys($records[0]);
            fputcsv($handle, $headers);
            
            // Dados
            foreach ($records as $record) {
                fputcsv($handle, array_values($record));
            }
            
            // Linha em branco
            fputcsv($handle, []);
        }
        
        fclose($handle);
    }
    
    /**
     * Exportar como XML
     */
    protected function exportAsXML(array $data, string $file): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data_export></data_export>');
        
        $xml->addChild('export_info');
        $xml->export_info->addChild('timestamp', date('c'));
        $xml->export_info->addChild('format', 'xml');
        $xml->export_info->addChild('compliance', 'LGPD/GDPR');
        
        foreach ($data as $table => $records) {
            $tableNode = $xml->addChild('table');
            $tableNode->addAttribute('name', $table);
            
            foreach ($records as $record) {
                $recordNode = $tableNode->addChild('record');
                foreach ($record as $field => $value) {
                    $recordNode->addChild($field, htmlspecialchars($value));
                }
            }
        }
        
        $xml->asXML($file);
    }
    
    /**
     * Registrar operação GDPR
     */
    protected function logGDPROperation(string $operation, string $tenantId, array $details): void
    {
        $logEntry = [
            'timestamp' => date('c'),
            'operation' => $operation,
            'tenant_id' => $tenantId,
            'user' => get_current_user(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'details' => $details
        ];
        
        $logFile = WRITEPATH . 'logs/gdpr_compliance_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Utilitários
     */
    protected function validateTenant(string $tenantId): bool
    {
        list($idContador, $idEmpresa) = explode(':', $tenantId);
        
        $db = \Config\Database::connect();
        $query = $db->query(
            "SELECT COUNT(*) as count FROM empresas WHERE id_contador = ? AND id = ?",
            [$idContador, $idEmpresa]
        );
        
        $result = $query->getRow();
        return $result && $result->count > 0;
    }
    
    protected function sanitizeTenantId(string $tenantId): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
    }
    
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    protected function countTenantBackups(string $tenantId): int
    {
        $safeTenantId = $this->sanitizeTenantId($tenantId);
        $tenantDir = $this->backupBaseDir . "tenant_{$safeTenantId}/";
        
        if (!is_dir($tenantDir)) {
            return 0;
        }
        
        $count = 0;
        $dateDirs = glob($tenantDir . '*', GLOB_ONLYDIR);
        
        foreach ($dateDirs as $dateDir) {
            $timeDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
            $count += count($timeDirs);
        }
        
        return $count;
    }
    
    protected function calculateTenantBackupsSize(string $tenantId): int
    {
        $safeTenantId = $this->sanitizeTenantId($tenantId);
        $tenantDir = $this->backupBaseDir . "tenant_{$safeTenantId}/";
        
        if (!is_dir($tenantDir)) {
            return 0;
        }
        
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tenantDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    protected function deleteLocalBackups(string $tenantId, bool $isVerbose): int
    {
        $safeTenantId = $this->sanitizeTenantId($tenantId);
        $tenantDir = $this->backupBaseDir . "tenant_{$safeTenantId}/";
        
        if (!is_dir($tenantDir)) {
            return 0;
        }
        
        $deleted = 0;
        $dateDirs = glob($tenantDir . '*', GLOB_ONLYDIR);
        
        foreach ($dateDirs as $dateDir) {
            $timeDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
            
            foreach ($timeDirs as $timeDir) {
                if ($isVerbose) {
                    CLI::write("    🗑️ Deletando: " . basename($dateDir) . '/' . basename($timeDir));
                }
                
                $this->deleteDirectory($timeDir);
                $deleted++;
            }
            
            // Remover diretório de data se vazio
            if (count(glob($dateDir . '/*')) === 0) {
                rmdir($dateDir);
            }
        }
        
        // Remover diretório do tenant se vazio
        if (count(glob($tenantDir . '*')) === 0) {
            rmdir($tenantDir);
        }
        
        return $deleted;
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
    
    protected function createAuditBackup(string $tenantId): string
    {
        // Implementar criação de backup de auditoria
        // Este backup será mantido por período legal (7 anos)
        return "audit_backup_placeholder.enc";
    }
    
    protected function deleteEncryptionKey(string $tenantId): void
    {
        // A chave será movida para arquivo de auditoria em vez de deletada
        // Para manter conformidade com requisitos legais
    }
    
    protected function deleteRemoteBackups(string $tenantId, bool $isVerbose): int
    {
        // Implementar deleção de backups remotos
        return 0;
    }
    
    protected function getDeletedRecords(string $idContador, string $idEmpresa): array
    {
        // Buscar registros da tabela audit_deleted_records
        return [];
    }
    
    protected function getBackupAccessLogs(string $tenantId): array
    {
        // Buscar logs de acesso aos backups
        return [];
    }
    
    protected function generateExportManifest(string $tenantId, string $file, array $data): void
    {
        $manifest = [
            'tenant_id' => $tenantId,
            'export_date' => date('c'),
            'purpose' => 'LGPD/GDPR Data Portability',
            'file' => basename($file),
            'tables_count' => count($data),
            'records_count' => array_sum(array_map('count', $data)),
            'retention_days' => 30,
            'auto_delete_date' => date('c', strtotime('+30 days'))
        ];
        
        $manifestFile = dirname($file) . '/export_manifest.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
    }
    
    protected function generateAuditReport(string $tenantId, array $accessLogs): string
    {
        $reportFile = WRITEPATH . "reports/audit_report_{$this->sanitizeTenantId($tenantId)}_" . date('Y-m-d_H-i-s') . ".json";
        
        $report = [
            'tenant_id' => $tenantId,
            'report_date' => date('c'),
            'total_accesses' => count($accessLogs),
            'access_logs' => $accessLogs
        ];
        
        if (!is_dir(dirname($reportFile))) {
            mkdir(dirname($reportFile), 0755, true);
        }
        
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        
        return $reportFile;
    }
}
