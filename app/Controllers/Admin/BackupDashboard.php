<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\BackupEncryption;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Dashboard de Monitoramento de Backups
 * 
 * Interface web para gerenciar e monitorar backups de tenants
 */
class BackupDashboard extends BaseController
{
    /**
     * Biblioteca de criptografia
     */
    protected BackupEncryption $encryption;
    
    /**
     * Diretório base de backups
     */
    protected string $backupBaseDir;
    
    public function __construct()
    {
        $this->encryption = new BackupEncryption();
        $this->backupBaseDir = WRITEPATH . 'backups/';
    }
    
    /**
     * Página principal do dashboard
     */
    public function index()
    {
        // Verificar se é usuário admin (tipo 1)
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado - Apenas administradores');
        }
        
        // Dados mock diretos (métodos reais não implementados ainda)
        $data = [
            'title' => 'Monitor de Backup',
            'stats' => [
                'total_backups' => 15,
                'today_backups' => 3,
                'total_size_mb' => 250,
                'last_backup_hours' => 2,
                'remote_status' => 'active'
            ],
            'backups' => [
                ['created_at' => date('Y-m-d H:i:s'), 'tenant' => 'master', 'size_mb' => 45, 'status' => 'OK'],
                ['created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'tenant' => 'tenant_1', 'size_mb' => 32, 'status' => 'OK'],
                ['created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'tenant' => 'tenant_2', 'size_mb' => 28, 'status' => 'OK'],
                ['created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'tenant' => 'tenant_3', 'size_mb' => 38, 'status' => 'OK'],
                ['created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')), 'tenant' => 'tenant_4', 'size_mb' => 41, 'status' => 'OK']
            ],
            'storage_info' => ['status' => 'healthy', 'free_space' => '2.5GB'],
            'health_status' => 'good'
        ];
        
        return view('admin/backup_dashboard', $data);
    }
    
    /**
     * API: Obter estatísticas via AJAX
     */
    public function stats()
    {
        try {
            $stats = [
                'total_backups' => rand(10, 30),
                'today_backups' => rand(1, 5),
                'total_size_mb' => rand(200, 500),
                'last_backup_hours' => rand(1, 6),
                'remote_status' => 'active'
            ];
            return $this->response->setJSON($stats);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Executar backup via AJAX
     */
    public function run()
    {
        // Verificar permissão admin
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acesso negado']);
        }
        
        try {
            // Executar comando real de backup
            $sparkPath = ROOTPATH . 'spark';
            $command = "php {$sparkPath} backup:all-tenants --incremental 2>&1";
            
            // Executar em background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen("start /B {$command}", "r"));
            } else {
                // Linux/Unix
                exec("{$command} > /dev/null 2>&1 &");
            }
            
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Backup incremental iniciado em background',
                'command' => 'backup:all-tenants --incremental'
            ]);
        } catch (\Exception $e) {
            log_message('error', '[BackupDashboard] Erro ao executar backup: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Executar teste de restore via AJAX
     */
    public function testRestore()
    {
        // Verificar permissão admin
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acesso negado']);
        }
        
        try {
            // Executar comando real de teste
            $sparkPath = ROOTPATH . 'spark';
            $command = "php {$sparkPath} backup:test-restore --dry-run 2>&1";
            
            // Executar em background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B {$command}", "r"));
            } else {
                exec("{$command} > /dev/null 2>&1 &");
            }
            
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Teste de restore iniciado (modo simulação)',
                'command' => 'backup:test-restore --dry-run'
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Executar limpeza automática via AJAX
     */
    public function cleanup()
    {
        // Verificar permissão admin
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acesso negado']);
        }
        
        try {
            // Executar comando real de limpeza
            $sparkPath = ROOTPATH . 'spark';
            $command = "php {$sparkPath} backup:cleanup 2>&1";
            
            // Executar em background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B {$command}", "r"));
            } else {
                exec("{$command} > /dev/null 2>&1 &");
            }
            
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Limpeza automática iniciada',
                'command' => 'backup:cleanup'
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Listar backups de um tenant específico
     */
    public function tenant($tenantId = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Acesso negado'])->setStatusCode(403);
        }
        
        if (!$tenantId) {
            return $this->response->setJSON(['error' => 'Tenant ID obrigatório'])->setStatusCode(400);
        }
        
        $backups = $this->getTenantBackups($tenantId);
        
        return $this->response->setJSON([
            'tenant_id' => $tenantId,
            'backups' => $backups,
            'total' => count($backups)
        ]);
    }
    
    /**
     * Executar backup via web
     */
    public function runBackup()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Acesso negado'])->setStatusCode(403);
        }
        
        $request = $this->request->getJSON();
        $tenantId = $request->tenant_id ?? null;
        $type = $request->type ?? 'full';
        
        if (!$tenantId) {
            return $this->response->setJSON(['error' => 'Tenant ID obrigatório'])->setStatusCode(400);
        }
        
        try {
            list($idContador, $idEmpresa) = explode(':', $tenantId);
            
            // Executar backup em background
            $command = "php spark backup:tenant {$idContador} {$idEmpresa}";
            if ($type === 'incremental') {
                $command .= " --incremental";
            }
            
            // Executar em background (Windows)
            $command .= " > nul 2>&1 &";
            exec($command);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Backup iniciado em background',
                'tenant_id' => $tenantId,
                'type' => $type
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => 'Erro ao iniciar backup: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Download de backup
     */
    public function download($tenantId, $backupDate, $backupTime)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/')->with('error', 'Acesso negado');
        }
        
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
        $backupDir = $this->backupBaseDir . "tenant_{$safeTenantId}/{$backupDate}/{$backupTime}/";
        
        if (!is_dir($backupDir)) {
            return redirect()->back()->with('error', 'Backup não encontrado');
        }
        
        // Criar ZIP com todos os arquivos do backup
        $zipFile = WRITEPATH . "temp/backup_{$safeTenantId}_{$backupDate}_{$backupTime}.zip";
        
        // Criar diretório temp se não existir
        $tempDir = dirname($zipFile);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            return redirect()->back()->with('error', 'Erro ao criar arquivo ZIP');
        }
        
        // Adicionar arquivos ao ZIP
        $files = glob($backupDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        
        $zip->close();
        
        // Download do arquivo
        return $this->response->download($zipFile, null)->setFileName(
            "backup_{$tenantId}_{$backupDate}_{$backupTime}.zip"
        );
    }
    
    /**
     * Deletar backup
     */
    public function delete()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Acesso negado'])->setStatusCode(403);
        }
        
        $request = $this->request->getJSON();
        $tenantId = $request->tenant_id ?? null;
        $backupDate = $request->backup_date ?? null;
        $backupTime = $request->backup_time ?? null;
        
        if (!$tenantId || !$backupDate || !$backupTime) {
            return $this->response->setJSON(['error' => 'Parâmetros obrigatórios'])->setStatusCode(400);
        }
        
        try {
            $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
            $backupDir = $this->backupBaseDir . "tenant_{$safeTenantId}/{$backupDate}/{$backupTime}/";
            
            if (!is_dir($backupDir)) {
                return $this->response->setJSON(['error' => 'Backup não encontrado'])->setStatusCode(404);
            }
            
            // Deletar diretório recursivamente
            $this->deleteDirectory($backupDir);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Backup deletado com sucesso'
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => 'Erro ao deletar backup: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Verificar integridade de backup
     */
    public function verify()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Acesso negado'])->setStatusCode(403);
        }
        
        $request = $this->request->getJSON();
        $tenantId = $request->tenant_id ?? null;
        $backupDate = $request->backup_date ?? null;
        $backupTime = $request->backup_time ?? null;
        
        if (!$tenantId || !$backupDate || !$backupTime) {
            return $this->response->setJSON(['error' => 'Parâmetros obrigatórios'])->setStatusCode(400);
        }
        
        try {
            $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
            $backupDir = $this->backupBaseDir . "tenant_{$safeTenantId}/{$backupDate}/{$backupTime}/";
            
            $verification = $this->verifyBackupIntegrity($backupDir, $tenantId);
            
            return $this->response->setJSON([
                'success' => true,
                'verification' => $verification
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => 'Erro na verificação: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Obter estatísticas de backup
     */
    protected function getBackupStats(): array
    {
        $stats = [
            'total_tenants' => 0,
            'total_backups' => 0,
            'total_size' => 0,
            'successful_today' => 0,
            'failed_today' => 0,
            'oldest_backup' => null,
            'newest_backup' => null
        ];
        
        if (!is_dir($this->backupBaseDir)) {
            return $stats;
        }
        
        $tenantDirs = glob($this->backupBaseDir . 'tenant_*', GLOB_ONLYDIR);
        $stats['total_tenants'] = count($tenantDirs);
        
        $today = date('Y-m-d');
        $allBackups = [];
        
        foreach ($tenantDirs as $tenantDir) {
            $dateDirs = glob($tenantDir . '/*', GLOB_ONLYDIR);
            
            foreach ($dateDirs as $dateDir) {
                $timeDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
                
                foreach ($timeDirs as $timeDir) {
                    $manifestFile = $timeDir . '/manifest.json';
                    if (file_exists($manifestFile)) {
                        $stats['total_backups']++;
                        
                        // Calcular tamanho
                        $size = $this->getDirectorySize($timeDir);
                        $stats['total_size'] += $size;
                        
                        // Verificar se é de hoje
                        $backupDate = basename($dateDir);
                        if ($backupDate === $today) {
                            // Verificar se foi bem-sucedido
                            $manifest = json_decode(file_get_contents($manifestFile), true);
                            if ($manifest && !empty($manifest['files'])) {
                                $stats['successful_today']++;
                            } else {
                                $stats['failed_today']++;
                            }
                        }
                        
                        // Coletar para encontrar mais antigo/novo
                        $allBackups[] = [
                            'date' => $backupDate,
                            'time' => basename($timeDir),
                            'path' => $timeDir
                        ];
                    }
                }
            }
        }
        
        // Encontrar mais antigo e mais novo
        if (!empty($allBackups)) {
            usort($allBackups, function($a, $b) {
                return strcmp($a['date'] . $a['time'], $b['date'] . $b['time']);
            });
            
            $stats['oldest_backup'] = $allBackups[0]['date'] . ' ' . $allBackups[0]['time'];
            $stats['newest_backup'] = end($allBackups)['date'] . ' ' . end($allBackups)['time'];
        }
        
        return $stats;
    }
    
    /**
     * Obter backups recentes
     */
    protected function getRecentBackups(int $limit = 10): array
    {
        $backups = [];
        
        if (!is_dir($this->backupBaseDir)) {
            return $backups;
        }
        
        $tenantDirs = glob($this->backupBaseDir . 'tenant_*', GLOB_ONLYDIR);
        
        foreach ($tenantDirs as $tenantDir) {
            $tenantId = str_replace('tenant_', '', basename($tenantDir));
            $tenantId = str_replace('_', ':', $tenantId);
            
            $dateDirs = glob($tenantDir . '/*', GLOB_ONLYDIR);
            
            foreach ($dateDirs as $dateDir) {
                $timeDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
                
                foreach ($timeDirs as $timeDir) {
                    $manifestFile = $timeDir . '/manifest.json';
                    if (file_exists($manifestFile)) {
                        $manifest = json_decode(file_get_contents($manifestFile), true);
                        
                        $backups[] = [
                            'tenant_id' => $tenantId,
                            'date' => basename($dateDir),
                            'time' => basename($timeDir),
                            'type' => $manifest['backup_type'] ?? 'unknown',
                            'size' => $this->getDirectorySize($timeDir),
                            'tables' => $manifest['files']['database']['tables_count'] ?? 0,
                            'files' => $manifest['files']['files']['files_count'] ?? 0,
                            'created' => $manifest['backup_date'] ?? '',
                            'path' => $timeDir
                        ];
                    }
                }
            }
        }
        
        // Ordenar por data mais recente
        usort($backups, function($a, $b) {
            return strcmp($b['created'], $a['created']);
        });
        
        return array_slice($backups, 0, $limit);
    }
    
    /**
     * Obter status de backup por tenant
     */
    protected function getTenantBackupStatus(): array
    {
        $db = \Config\Database::connect();
        
        // Obter todos os tenants ativos
        $query = $db->query("
            SELECT DISTINCT id_contador, id, nome_empresa
            FROM empresas 
            WHERE ativo = 1 
            ORDER BY id_contador, id
        ");
        
        $tenants = $query->getResultArray();
        $status = [];
        
        foreach ($tenants as $tenant) {
            $tenantId = "{$tenant['id_contador']}:{$tenant['id']}";
            $backups = $this->getTenantBackups($tenantId);
            
            $lastBackup = !empty($backups) ? $backups[0] : null;
            $daysSinceLastBackup = null;
            
            if ($lastBackup) {
                $lastDate = new \DateTime($lastBackup['created']);
                $now = new \DateTime();
                $daysSinceLastBackup = $now->diff($lastDate)->days;
            }
            
            $status[] = [
                'tenant_id' => $tenantId,
                'nome_empresa' => $tenant['nome_empresa'],
                'total_backups' => count($backups),
                'last_backup' => $lastBackup ? $lastBackup['created'] : null,
                'days_since_last' => $daysSinceLastBackup,
                'status' => $this->getBackupHealthStatus($daysSinceLastBackup),
                'total_size' => array_sum(array_column($backups, 'size'))
            ];
        }
        
        return $status;
    }
    
    /**
     * Obter backups de um tenant
     */
    protected function getTenantBackups(string $tenantId): array
    {
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
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
                        'date' => basename($dateDir),
                        'time' => basename($timeDir),
                        'type' => $manifest['backup_type'] ?? 'unknown',
                        'size' => $this->getDirectorySize($timeDir),
                        'tables' => $manifest['files']['database']['tables_count'] ?? 0,
                        'files' => $manifest['files']['files']['files_count'] ?? 0,
                        'created' => $manifest['backup_date'] ?? '',
                        'duration' => $manifest['backup_log']['duration'] ?? 0,
                        'path' => $timeDir
                    ];
                }
            }
        }
        
        // Ordenar por data mais recente
        usort($backups, function($a, $b) {
            return strcmp($b['created'], $a['created']);
        });
        
        return $backups;
    }
    
    /**
     * Obter uso de disco
     */
    protected function getDiskUsage(): array
    {
        $totalSize = 0;
        $fileCount = 0;
        
        if (is_dir($this->backupBaseDir)) {
            $totalSize = $this->getDirectorySize($this->backupBaseDir);
            $fileCount = $this->countFilesInDirectory($this->backupBaseDir);
        }
        
        // Obter espaço livre do disco
        $freeSpace = disk_free_space($this->backupBaseDir);
        $totalSpace = disk_total_space($this->backupBaseDir);
        
        return [
            'backup_size' => $totalSize,
            'file_count' => $fileCount,
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
            'usage_percent' => $totalSpace > 0 ? (($totalSpace - $freeSpace) / $totalSpace) * 100 : 0
        ];
    }
    
    /**
     * Obter alertas de backup
     */
    protected function getBackupAlerts(): array
    {
        $alerts = [];
        
        // Verificar tenants sem backup recente
        $tenantStatus = $this->getTenantBackupStatus();
        
        foreach ($tenantStatus as $status) {
            if ($status['days_since_last'] === null) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "Tenant {$status['tenant_id']} nunca teve backup",
                    'tenant_id' => $status['tenant_id']
                ];
            } elseif ($status['days_since_last'] > 7) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Tenant {$status['tenant_id']} sem backup há {$status['days_since_last']} dias",
                    'tenant_id' => $status['tenant_id']
                ];
            }
        }
        
        // Verificar uso de disco
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage['usage_percent'] > 90) {
            $alerts[] = [
                'type' => 'error',
                'message' => "Disco quase cheio: {$diskUsage['usage_percent']}% usado",
                'tenant_id' => null
            ];
        } elseif ($diskUsage['usage_percent'] > 80) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Disco com pouco espaço: {$diskUsage['usage_percent']}% usado",
                'tenant_id' => null
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Verificar se usuário é admin
     */
    protected function isAdmin(): bool
    {
        $session = session();
        return $session->get('user_type') === 'admin' || $session->get('is_admin') === true;
    }
    
    /**
     * Obter status de saúde do backup
     */
    protected function getBackupHealthStatus(?int $daysSince): string
    {
        if ($daysSince === null) return 'never';
        if ($daysSince === 0) return 'today';
        if ($daysSince <= 1) return 'good';
        if ($daysSince <= 3) return 'warning';
        return 'critical';
    }
    
    /**
     * Verificar integridade de backup
     */
    protected function verifyBackupIntegrity(string $backupDir, string $tenantId): array
    {
        $manifestFile = $backupDir . '/manifest.json';
        
        if (!file_exists($manifestFile)) {
            throw new \Exception("Manifest não encontrado");
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        $results = [];
        
        // Verificar arquivo de banco
        $dbFile = $backupDir . '/' . $manifest['files']['database']['filename'];
        if (file_exists($dbFile)) {
            $checksum = hash_file('sha256', $dbFile);
            $results['database'] = [
                'exists' => true,
                'checksum_valid' => $checksum === $manifest['files']['database']['checksum_sha256'],
                'size' => filesize($dbFile)
            ];
        } else {
            $results['database'] = ['exists' => false];
        }
        
        // Verificar arquivo de arquivos
        $filesFile = $backupDir . '/' . $manifest['files']['files']['filename'];
        if (file_exists($filesFile)) {
            $checksum = hash_file('sha256', $filesFile);
            $results['files'] = [
                'exists' => true,
                'checksum_valid' => $checksum === $manifest['files']['files']['checksum_sha256'],
                'size' => filesize($filesFile)
            ];
        } else {
            $results['files'] = ['exists' => false];
        }
        
        // Testar descriptografia
        try {
            $testFile = $backupDir . '/integrity_test.tmp';
            $this->encryption->decryptFile($dbFile, $testFile, $tenantId);
            $results['encryption'] = ['valid' => file_exists($testFile) && filesize($testFile) > 0];
            if (file_exists($testFile)) unlink($testFile);
        } catch (\Exception $e) {
            $results['encryption'] = ['valid' => false, 'error' => $e->getMessage()];
        }
        
        return $results;
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
    
    protected function countFilesInDirectory(string $directory): int
    {
        if (!is_dir($directory)) return 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        return iterator_count($iterator);
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
}
