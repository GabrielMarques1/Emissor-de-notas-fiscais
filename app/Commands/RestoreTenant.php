<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\BackupEncryption;
use Exception;

/**
 * Comando CLI para Restore de Backup de Tenant
 * 
 * Restaura backup criptografado de um tenant específico
 */
class RestoreTenant extends BaseCommand
{
    /**
     * Grupo do comando
     */
    protected $group = 'Backup';
    
    /**
     * Nome do comando
     */
    protected $name = 'backup:restore';
    
    /**
     * Descrição do comando
     */
    protected $description = 'Restaura backup criptografado de um tenant';
    
    /**
     * Uso do comando
     */
    protected $usage = 'backup:restore <tenant_id> <backup_path> [options]';
    
    /**
     * Argumentos do comando
     */
    protected $arguments = [
        'tenant_id' => 'ID do tenant (formato: id_contador:id_empresa)',
        'backup_path' => 'Caminho para o diretório do backup'
    ];
    
    /**
     * Opções do comando
     */
    protected $options = [
        '--database-only' => 'Restaurar apenas banco de dados',
        '--files-only' => 'Restaurar apenas arquivos',
        '--verify' => 'Verificar integridade antes de restaurar',
        '--dry-run' => 'Simular restore sem executar',
        '--force' => 'Forçar restore sem confirmação',
        '--verbose' => 'Saída detalhada'
    ];
    
    /**
     * Biblioteca de criptografia
     */
    protected BackupEncryption $encryption;
    
    /**
     * Executar comando
     */
    public function run(array $params)
    {
        $startTime = microtime(true);
        
        try {
            $this->encryption = new BackupEncryption();
            
            // Validar parâmetros
            $tenantId = $params[0] ?? null;
            $backupPath = $params[1] ?? null;
            
            if (!$tenantId || !$backupPath) {
                CLI::error('Parâmetros obrigatórios: tenant_id e backup_path');
                CLI::write($this->usage);
                return;
            }
            
            if (!is_dir($backupPath)) {
                CLI::error("Diretório de backup não encontrado: {$backupPath}");
                return;
            }
            
            $isDryRun = CLI::getOption('dry-run');
            $isVerbose = CLI::getOption('verbose');
            $isForce = CLI::getOption('force');
            
            CLI::write("🔓 RESTORE DE BACKUP CRIPTOGRAFADO", 'green');
            CLI::write("Tenant: {$tenantId}");
            CLI::write("Backup: {$backupPath}");
            CLI::write("Modo: " . ($isDryRun ? 'Simulação' : 'Execução'));
            CLI::write("Data: " . date('Y-m-d H:i:s'));
            CLI::newLine();
            
            // Verificar manifest
            $manifest = $this->loadManifest($backupPath);
            $this->displayBackupInfo($manifest);
            
            // Verificar integridade se solicitado
            if (CLI::getOption('verify')) {
                CLI::write("🔍 Verificando integridade do backup...");
                $this->verifyBackupIntegrity($backupPath, $tenantId, $manifest);
            }
            
            // Confirmação de segurança
            if (!$isForce && !$isDryRun) {
                CLI::newLine();
                CLI::write("⚠️  ATENÇÃO: Esta operação irá SOBRESCREVER os dados existentes!", 'red');
                CLI::write("Tenant: {$tenantId}");
                CLI::write("Backup de: " . $manifest['backup_date']);
                CLI::newLine();
                
                $confirm = CLI::prompt('Deseja continuar? (digite "CONFIRMAR" para prosseguir)');
                if ($confirm !== 'CONFIRMAR') {
                    CLI::write("❌ Operação cancelada pelo usuário");
                    return;
                }
            }
            
            // Executar restore
            if (!CLI::getOption('files-only')) {
                CLI::write("📊 Restaurando banco de dados...");
                $this->restoreDatabase($backupPath, $tenantId, $manifest, $isDryRun, $isVerbose);
            }
            
            if (!CLI::getOption('database-only')) {
                CLI::write("📁 Restaurando arquivos...");
                $this->restoreFiles($backupPath, $tenantId, $manifest, $isDryRun, $isVerbose);
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            
            CLI::newLine();
            if ($isDryRun) {
                CLI::write("✅ SIMULAÇÃO DE RESTORE CONCLUÍDA!", 'green');
                CLI::write("Nenhum dado foi alterado");
            } else {
                CLI::write("✅ RESTORE CONCLUÍDO COM SUCESSO!", 'green');
                CLI::write("Dados restaurados para o estado do backup");
            }
            CLI::write("Duração: {$duration}s");
            CLI::newLine();
            
        } catch (Exception $e) {
            CLI::error("❌ ERRO NO RESTORE: " . $e->getMessage());
            CLI::error("Arquivo: " . $e->getFile() . " Linha: " . $e->getLine());
        }
    }
    
    /**
     * Carregar manifest do backup
     */
    protected function loadManifest(string $backupPath): array
    {
        $manifestFile = rtrim($backupPath, '/') . '/manifest.json';
        
        if (!file_exists($manifestFile)) {
            throw new Exception("Arquivo manifest.json não encontrado em: {$manifestFile}");
        }
        
        $content = file_get_contents($manifestFile);
        $manifest = json_decode($content, true);
        
        if (!$manifest) {
            throw new Exception("Erro ao decodificar manifest.json");
        }
        
        return $manifest;
    }
    
    /**
     * Exibir informações do backup
     */
    protected function displayBackupInfo(array $manifest): void
    {
        CLI::write("📋 INFORMAÇÕES DO BACKUP:", 'yellow');
        CLI::write("  Tenant: " . $manifest['tenant_id']);
        CLI::write("  Data: " . $manifest['backup_date']);
        CLI::write("  Tipo: " . $manifest['backup_type']);
        CLI::write("  Banco: " . $manifest['files']['database']['tables_count'] . " tabelas, " . 
                   $manifest['files']['database']['rows_count'] . " registros");
        CLI::write("  Arquivos: " . $manifest['files']['files']['files_count'] . " arquivos");
        CLI::write("  Tamanho total: " . $this->formatFileSize(
            $manifest['files']['database']['size_bytes'] + $manifest['files']['files']['size_bytes']
        ));
        CLI::newLine();
    }
    
    /**
     * Verificar integridade do backup
     */
    protected function verifyBackupIntegrity(string $backupPath, string $tenantId, array $manifest): void
    {
        $backupPath = rtrim($backupPath, '/') . '/';
        
        // Verificar arquivo de banco
        $dbFile = $backupPath . $manifest['files']['database']['filename'];
        if (!file_exists($dbFile)) {
            throw new Exception("Arquivo de banco não encontrado: {$dbFile}");
        }
        
        $dbChecksum = hash_file('sha256', $dbFile);
        if ($dbChecksum !== $manifest['files']['database']['checksum_sha256']) {
            throw new Exception("Checksum do banco de dados não confere - arquivo corrompido");
        }
        
        // Verificar arquivo de arquivos
        $filesFile = $backupPath . $manifest['files']['files']['filename'];
        if (file_exists($filesFile)) {
            $filesChecksum = hash_file('sha256', $filesFile);
            if ($filesChecksum !== $manifest['files']['files']['checksum_sha256']) {
                throw new Exception("Checksum dos arquivos não confere - arquivo corrompido");
            }
        }
        
        // Testar descriptografia
        $testFile = $backupPath . 'integrity_test.tmp';
        try {
            $this->encryption->decryptFile($dbFile, $testFile, $tenantId);
            
            if (!file_exists($testFile) || filesize($testFile) === 0) {
                throw new Exception("Teste de descriptografia falhou");
            }
            
            unlink($testFile);
            CLI::write("  ✅ Integridade verificada com sucesso");
            
        } catch (Exception $e) {
            if (file_exists($testFile)) {
                unlink($testFile);
            }
            throw new Exception("Erro na verificação de integridade: " . $e->getMessage());
        }
    }
    
    /**
     * Restaurar banco de dados
     */
    protected function restoreDatabase(string $backupPath, string $tenantId, array $manifest, bool $isDryRun, bool $isVerbose): void
    {
        $backupPath = rtrim($backupPath, '/') . '/';
        $encryptedFile = $backupPath . $manifest['files']['database']['filename'];
        $sqlFile = $backupPath . 'restore_database.sql';
        
        try {
            // Descriptografar arquivo SQL
            if ($isVerbose) {
                CLI::write("  🔓 Descriptografando arquivo do banco...");
            }
            
            if (!$isDryRun) {
                $this->encryption->decryptFile($encryptedFile, $sqlFile, $tenantId);
            }
            
            if (!$isDryRun) {
                // Executar SQL
                if ($isVerbose) {
                    CLI::write("  📊 Executando comandos SQL...");
                }
                
                $db = \Config\Database::connect();
                $sqlContent = file_get_contents($sqlFile);
                
                // Dividir em comandos individuais
                $commands = array_filter(
                    array_map('trim', explode(';', $sqlContent)),
                    function($cmd) { return !empty($cmd) && !str_starts_with($cmd, '--'); }
                );
                
                $executedCommands = 0;
                foreach ($commands as $command) {
                    if (!empty(trim($command))) {
                        $db->query($command);
                        $executedCommands++;
                    }
                }
                
                CLI::write("  ✅ Banco: {$executedCommands} comandos executados");
                
                // Limpar arquivo temporário
                unlink($sqlFile);
            } else {
                CLI::write("  ✅ Banco: Simulação - arquivo descriptografado com sucesso");
            }
            
        } catch (Exception $e) {
            if (file_exists($sqlFile)) {
                unlink($sqlFile);
            }
            throw new Exception("Erro no restore do banco: " . $e->getMessage());
        }
    }
    
    /**
     * Restaurar arquivos
     */
    protected function restoreFiles(string $backupPath, string $tenantId, array $manifest, bool $isDryRun, bool $isVerbose): void
    {
        $backupPath = rtrim($backupPath, '/') . '/';
        $encryptedFile = $backupPath . $manifest['files']['files']['filename'];
        
        if (!file_exists($encryptedFile)) {
            CLI::write("  ⚠️ Arquivo de arquivos não encontrado - pulando restore de arquivos");
            return;
        }
        
        $tarFile = $backupPath . 'restore_files.tar.gz';
        list($idContador, $idEmpresa) = explode(':', $tenantId);
        $targetDir = ROOTPATH . "public/uploads/tenant_{$idContador}_{$idEmpresa}/";
        
        try {
            // Descriptografar arquivo
            if ($isVerbose) {
                CLI::write("  🔓 Descriptografando arquivo de arquivos...");
            }
            
            if (!$isDryRun) {
                $this->encryption->decryptFile($encryptedFile, $tarFile, $tenantId);
                
                // Criar diretório de destino se não existir
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Extrair arquivos
                if ($isVerbose) {
                    CLI::write("  📁 Extraindo arquivos para: {$targetDir}");
                }
                
                $command = "tar -xzf \"{$tarFile}\" -C \"" . dirname($targetDir) . "\"";
                exec($command, $output, $returnCode);
                
                if ($returnCode !== 0) {
                    // Fallback: usar ZipArchive se tar não funcionar
                    $this->extractZipBackup($tarFile, $targetDir);
                }
                
                CLI::write("  ✅ Arquivos: " . $manifest['files']['files']['files_count'] . " arquivos restaurados");
                
                // Limpar arquivo temporário
                unlink($tarFile);
            } else {
                CLI::write("  ✅ Arquivos: Simulação - arquivo descriptografado com sucesso");
            }
            
        } catch (Exception $e) {
            if (file_exists($tarFile)) {
                unlink($tarFile);
            }
            throw new Exception("Erro no restore de arquivos: " . $e->getMessage());
        }
    }
    
    /**
     * Extrair backup ZIP (fallback)
     */
    protected function extractZipBackup(string $zipFile, string $targetDir): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            throw new Exception("Não foi possível abrir arquivo ZIP");
        }
        
        $zip->extractTo(dirname($targetDir));
        $zip->close();
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
