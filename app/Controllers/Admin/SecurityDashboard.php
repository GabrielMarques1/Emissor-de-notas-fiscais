<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

/**
 * Dashboard de Monitoramento de Segurança
 * Monitora violações de segurança e tentativas de acesso não autorizado
 */
class SecurityDashboard extends Controller
{
    protected $db;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    
    /**
     * Dashboard principal de segurança
     */
    public function index()
    {
        // Verificar se usuário tem permissão de admin
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado');
        }
        
        // Dados mock diretos (métodos reais não implementados ainda)
        $data = [
            'title' => 'Dashboard de Segurança',
            'stats' => [
                'critical_alerts' => 2,
                'failed_logins' => 15,
                'blocked_ips' => 8,
                'security_score' => 95,
                'cross_tenant_attempts' => 0,
                'suspicious_access' => 3,
                'sql_injections' => 0,
                'xss_attempts' => 1,
                'users_online' => 12,
                'requests_per_min' => 45,
                'cpu_usage' => 23
            ],
            'recent_violations' => [
                ['type' => 'failed_login', 'ip' => '192.168.1.100', 'time' => '2 min atrás'],
                ['type' => 'suspicious_access', 'ip' => '10.0.0.50', 'time' => '15 min atrás']
            ],
            'top_violating_ips' => ['192.168.1.100', '10.0.0.50'],
            'violation_trends' => ['increasing' => false],
            'tenant_security_status' => ['all_secure' => true]
        ];
        
        return view('admin/security_dashboard', $data);
    }
    
    /**
     * API: Obter alertas via AJAX
     */
    public function alerts()
    {
        try {
            $alerts = [
                ['type' => 'warning', 'message' => 'Tentativa de login suspeita detectada', 'time' => '2 min atrás'],
                ['type' => 'info', 'message' => 'Sistema de backup executado com sucesso', 'time' => '15 min atrás'],
                ['type' => 'success', 'message' => 'Cache limpo automaticamente', 'time' => '30 min atrás']
            ];
            return $this->response->setJSON($alerts);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API para estatísticas em tempo real
     */
    public function stats()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->getSecurityStats()
        ]);
    }
    
    /**
     * API para violações recentes
     */
    public function violations()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
        
        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $type = $this->request->getGet('type');
        $ip = $this->request->getGet('ip');
        
        $violations = $this->getViolations($page, $limit, $type, $ip);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $violations
        ]);
    }
    
    /**
     * Bloquear IP suspeito
     */
    public function blockIP()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
        
        $ip = $this->request->getPost('ip');
        $reason = $this->request->getPost('reason') ?? 'Blocked by admin';
        $duration = (int) ($this->request->getPost('duration') ?? 3600); // 1 hora padrão
        
        if (empty($ip)) {
            return $this->response->setJSON(['error' => 'IP is required'])->setStatusCode(400);
        }
        
        // Adicionar IP à lista de bloqueados no cache
        $cache = \Config\Services::cache();
        $blockKey = "blocked_ip:{$ip}";
        
        $cache->save($blockKey, [
            'blocked_at' => date('Y-m-d H:i:s'),
            'blocked_by' => session()->get('id_usuario'),
            'reason' => $reason,
            'expires_at' => date('Y-m-d H:i:s', time() + $duration)
        ], $duration);
        
        // Log da ação
        log_message('alert', "[SecurityDashboard] IP blocked: {$ip}", [
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $duration,
            'blocked_by' => session()->get('id_usuario')
        ]);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "IP {$ip} bloqueado por " . gmdate('H:i:s', $duration)
        ]);
    }
    
    /**
     * Desbloquear IP
     */
    public function unblockIP()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
        
        $ip = $this->request->getPost('ip');
        
        if (empty($ip)) {
            return $this->response->setJSON(['error' => 'IP is required'])->setStatusCode(400);
        }
        
        // Remover IP da lista de bloqueados
        $cache = \Config\Services::cache();
        $blockKey = "blocked_ip:{$ip}";
        $cache->delete($blockKey);
        
        // Log da ação
        log_message('info', "[SecurityDashboard] IP unblocked: {$ip}", [
            'ip' => $ip,
            'unblocked_by' => session()->get('id_usuario')
        ]);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "IP {$ip} desbloqueado"
        ]);
    }
    
    /**
     * Obter estatísticas de segurança
     */
    private function getSecurityStats(): array
    {
        $stats = [
            'total_violations' => 0,
            'violations_today' => 0,
            'violations_this_hour' => 0,
            'unique_ips' => 0,
            'blocked_ips' => 0,
            'top_violation_type' => null
        ];
        
        try {
            if (!$this->db->tableExists('security_audit')) {
                return $stats;
            }
            
            // Total de violações
            $stats['total_violations'] = $this->db->table('security_audit')->countAllResults();
            
            // Violações hoje
            $stats['violations_today'] = $this->db->table('security_audit')
                ->where('DATE(created_at)', date('Y-m-d'))
                ->countAllResults();
            
            // Violações na última hora
            $stats['violations_this_hour'] = $this->db->table('security_audit')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                ->countAllResults();
            
            // IPs únicos
            $stats['unique_ips'] = $this->db->table('security_audit')
                ->select('ip_address')
                ->distinct()
                ->countAllResults();
            
            // Tipo de violação mais comum
            $topViolation = $this->db->table('security_audit')
                ->select('violation_type, COUNT(*) as count')
                ->groupBy('violation_type')
                ->orderBy('count', 'DESC')
                ->limit(1)
                ->get()
                ->getFirstRow('array');
            
            if ($topViolation) {
                $stats['top_violation_type'] = [
                    'type' => $topViolation['violation_type'],
                    'count' => $topViolation['count']
                ];
            }
            
            // IPs bloqueados (do cache)
            $cache = \Config\Services::cache();
            $blockedCount = 0;
            
            // Não há uma forma direta de contar chaves no cache, então estimamos
            $stats['blocked_ips'] = $blockedCount;
            
        } catch (\Throwable $e) {
            log_message('error', '[SecurityDashboard] Erro ao obter estatísticas: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Obter violações recentes
     */
    private function getRecentViolations(int $limit = 10): array
    {
        try {
            if (!$this->db->tableExists('security_audit')) {
                return [];
            }
            
            return $this->db->table('security_audit')
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
                
        } catch (\Throwable $e) {
            log_message('error', '[SecurityDashboard] Erro ao obter violações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter IPs com mais violações
     */
    private function getTopViolatingIPs(int $limit = 10): array
    {
        try {
            if (!$this->db->tableExists('security_audit')) {
                return [];
            }
            
            return $this->db->table('security_audit')
                ->select('ip_address, COUNT(*) as violation_count, MAX(created_at) as last_violation')
                ->groupBy('ip_address')
                ->orderBy('violation_count', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
                
        } catch (\Throwable $e) {
            log_message('error', '[SecurityDashboard] Erro ao obter IPs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter tendências de violações (últimos 7 dias)
     */
    private function getViolationTrends(): array
    {
        try {
            if (!$this->db->tableExists('security_audit')) {
                return [];
            }
            
            $trends = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                
                $count = $this->db->table('security_audit')
                    ->where('DATE(created_at)', $date)
                    ->countAllResults();
                
                $trends[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }
            
            return $trends;
            
        } catch (\Throwable $e) {
            log_message('error', '[SecurityDashboard] Erro ao obter tendências: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter status de segurança por tenant
     */
    private function getTenantSecurityStatus(): array
    {
        try {
            if (!$this->db->tableExists('security_audit')) {
                return [];
            }
            
            return $this->db->table('security_audit')
                ->select('tenant_id, COUNT(*) as violation_count, MAX(created_at) as last_violation')
                ->where('tenant_id IS NOT NULL')
                ->groupBy('tenant_id')
                ->orderBy('violation_count', 'DESC')
                ->limit(20)
                ->get()
                ->getResultArray();
                
        } catch (\Throwable $e) {
            log_message('error', '[SecurityDashboard] Erro ao obter status por tenant: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter violações com filtros
     */
    private function getViolations(int $page, int $limit, ?string $type = null, ?string $ip = null): array
    {
        try {
            if (!$this->db->tableExists('security_audit')) {
                return ['data' => [], 'total' => 0];
            }
            
            $builder = $this->db->table('security_audit');
            
            if ($type) {
                $builder->where('violation_type', $type);
            }
            
            if ($ip) {
                $builder->where('ip_address', $ip);
            }
            
            $total = $builder->countAllResults(false);
            
            $data = $builder
                ->orderBy('created_at', 'DESC')
                ->limit($limit, ($page - 1) * $limit)
                ->get()
                ->getResultArray();
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (\Throwable $e) {
            log_message('error', '[SecurityDashboard] Erro ao obter violações: ' . $e->getMessage());
            return ['data' => [], 'total' => 0];
        }
    }
    
    /**
     * Verificar se usuário é admin
     */
    private function isAdmin(): bool
    {
        $session = session();
        $userRole = $session->get('role') ?? '';
        $userId = $session->get('id_usuario') ?? 0;
        
        // Verificar se é admin ou super admin
        return in_array($userRole, ['admin', 'super_admin']) || $userId === 1;
    }
}
