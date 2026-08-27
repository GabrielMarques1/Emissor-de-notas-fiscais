<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Libraries\TenantLogger;

/**
 * Dashboard de Auditoria - Visualização e Análise de Logs
 * 
 * Funcionalidades:
 * - Visualização de logs por tenant
 * - Filtros avançados
 * - Busca full-text
 * - Exportação de logs
 * - Alertas de segurança
 * - Estatísticas de auditoria
 */
class AuditDashboard extends Controller
{
    protected $logger;
    protected $db;
    
    public function __construct()
    {
        $this->logger = new TenantLogger();
        $this->db = \Config\Database::connect();
        
        // Verificar se usuário tem permissão de admin
        $this->checkAdminPermission();
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
        
        try {
            $data = [
                'title' => 'Dashboard de Auditoria',
                'stats' => $this->getAuditStats(),
                'recent_alerts' => $this->getRecentAlerts(),
                'top_events' => $this->getTopEvents(),
                'tenant_activity' => $this->getTenantActivity()
            ];
        } catch (\Exception $e) {
            // Fallback com dados padrão em caso de erro
            $data = [
                'title' => 'Dashboard de Auditoria',
                'stats' => [
                    'total_logs' => 0,
                    'today_logs' => 0,
                    'security_alerts' => 0,
                    'failed_logins' => 0
                ],
                'recent_alerts' => [],
                'top_events' => [],
                'tenant_activity' => []
            ];
            
            log_message('error', '[AuditDashboard] Erro ao carregar dados: ' . $e->getMessage());
        }
        
        return view('admin/audit_dashboard', $data);
    }
    
    /**
     * Visualizar logs do tenant atual
     */
    public function logs()
    {
        $session = session();
        $idContador = (int) ($this->request->getGet('id_contador') ?? $session->get('id_contador') ?? 0);
        $idEmpresa = (int) ($this->request->getGet('id_empresa') ?? $session->get('id_empresa') ?? 0);
        
        // Filtros
        $filters = [
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'level' => $this->request->getGet('level'),
            'event_type' => $this->request->getGet('event_type'),
            'user_id' => $this->request->getGet('user_id'),
            'search' => $this->request->getGet('search')
        ];
        
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 50;
        
        // Buscar logs
        $allLogs = $this->logger->searchLogs($idContador, $idEmpresa, $filters);
        
        // Paginação
        $totalLogs = count($allLogs);
        $offset = ($page - 1) * $perPage;
        $logs = array_slice($allLogs, $offset, $perPage);
        
        $data = [
            'title' => 'Logs de Auditoria',
            'logs' => $logs,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalLogs,
                'total_pages' => ceil($totalLogs / $perPage)
            ],
            'tenant_info' => [
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa
            ]
        ];
        
        return view('admin/audit_logs', $data);
    }
    
    /**
     * Exportar logs
     */
    public function export()
    {
        $session = session();
        $idContador = (int) ($this->request->getPost('id_contador') ?? $session->get('id_contador') ?? 0);
        $idEmpresa = (int) ($this->request->getPost('id_empresa') ?? $session->get('id_empresa') ?? 0);
        
        $filters = [
            'date_from' => $this->request->getPost('date_from') ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $this->request->getPost('date_to') ?? date('Y-m-d'),
            'level' => $this->request->getPost('level'),
            'event_type' => $this->request->getPost('event_type')
        ];
        
        $format = $this->request->getPost('format') ?? 'json';
        
        $logs = $this->logger->searchLogs($idContador, $idEmpresa, $filters);
        
        // Log da exportação
        audit_export_data('audit_logs', count($logs), $format, [
            'tenant_id' => "{$idContador}:{$idEmpresa}",
            'filters' => $filters
        ]);
        
        switch ($format) {
            case 'csv':
                return $this->exportCSV($logs, $idContador, $idEmpresa);
            case 'excel':
                return $this->exportExcel($logs, $idContador, $idEmpresa);
            case 'json':
            default:
                return $this->exportJSON($logs, $idContador, $idEmpresa);
        }
    }
    
    /**
     * Alertas de segurança
     */
    public function alerts()
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 20;
        $status = $this->request->getGet('status') ?? 'pending';
        
        $builder = $this->db->table('security_alerts');
        
        if ($status !== 'all') {
            $builder->where('status', $status);
        }
        
        // Se não é admin global, filtrar por tenant
        if (!$this->isGlobalAdmin()) {
            $session = session();
            $tenantId = $session->get('id_contador') . ':' . $session->get('id_empresa');
            $builder->where('tenant_id', $tenantId);
        }
        
        $total = $builder->countAllResults(false);
        $alerts = $builder->orderBy('created_at', 'DESC')
                         ->limit($perPage, ($page - 1) * $perPage)
                         ->get()
                         ->getResultArray();
        
        $data = [
            'title' => 'Alertas de Segurança',
            'alerts' => $alerts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ],
            'status_filter' => $status
        ];
        
        return view('admin/security_alerts', $data);
    }
    
    /**
     * Reconhecer alerta
     */
    public function acknowledgeAlert($alertId)
    {
        $session = session();
        $userId = $session->get('id_usuario');
        
        $updated = $this->db->table('security_alerts')
                           ->where('id', $alertId)
                           ->update([
                               'status' => 'acknowledged',
                               'acknowledged_by' => $userId,
                               'acknowledged_at' => date('Y-m-d H:i:s')
                           ]);
        
        if ($updated) {
            audit_security('Alert acknowledged', [
                'alert_id' => $alertId,
                'acknowledged_by' => $userId
            ]);
            
            return $this->response->setJSON(['success' => true]);
        }
        
        return $this->response->setJSON(['success' => false]);
    }
    
    /**
     * Resolver alerta
     */
    public function resolveAlert()
    {
        $alertId = $this->request->getPost('alert_id');
        $notes = $this->request->getPost('resolution_notes');
        $session = session();
        $userId = $session->get('id_usuario');
        
        $updated = $this->db->table('security_alerts')
                           ->where('id', $alertId)
                           ->update([
                               'status' => 'resolved',
                               'resolved_by' => $userId,
                               'resolved_at' => date('Y-m-d H:i:s'),
                               'resolution_notes' => $notes
                           ]);
        
        if ($updated) {
            audit_security('Alert resolved', [
                'alert_id' => $alertId,
                'resolved_by' => $userId,
                'notes' => $notes
            ]);
            
            return $this->response->setJSON(['success' => true]);
        }
        
        return $this->response->setJSON(['success' => false]);
    }
    
    /**
     * Estatísticas de auditoria
     */
    public function stats()
    {
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-d');
        
        $stats = [
            'logs_by_level' => $this->getLogsByLevel($dateFrom, $dateTo),
            'logs_by_event_type' => $this->getLogsByEventType($dateFrom, $dateTo),
            'logs_by_tenant' => $this->getLogsByTenant($dateFrom, $dateTo),
            'alerts_by_type' => $this->getAlertsByType($dateFrom, $dateTo),
            'top_users' => $this->getTopUsers($dateFrom, $dateTo),
            'performance_metrics' => $this->getPerformanceMetrics($dateFrom, $dateTo)
        ];
        
        return $this->response->setJSON($stats);
    }
    
    /**
     * Obter estatísticas gerais
     */
    protected function getAuditStats(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        
        return [
            'total_alerts' => $this->db->table('security_alerts')->countAllResults(),
            'pending_alerts' => $this->db->table('security_alerts')->where('status', 'pending')->countAllResults(),
            'alerts_today' => $this->db->table('security_alerts')->where('DATE(created_at)', $today)->countAllResults(),
            'logs_today' => $this->countLogsForDate($today),
            'active_tenants' => $this->getActiveTenants($weekAgo)
        ];
    }
    
    /**
     * Obter alertas recentes
     */
    protected function getRecentAlerts(): array
    {
        return $this->db->table('security_alerts')
                       ->orderBy('created_at', 'DESC')
                       ->limit(10)
                       ->get()
                       ->getResultArray();
    }
    
    /**
     * Obter eventos mais frequentes
     */
    protected function getTopEvents(): array
    {
        // Esta seria uma implementação mais complexa que analisaria os logs
        // Por simplicidade, retornando dados mockados
        return [
            ['event' => 'login_success', 'count' => 1250],
            ['event' => 'crud_create', 'count' => 890],
            ['event' => 'crud_update', 'count' => 650],
            ['event' => 'api_call', 'count' => 2100],
            ['event' => 'login_failure', 'count' => 45]
        ];
    }
    
    /**
     * Obter atividade por tenant
     */
    protected function getTenantActivity(): array
    {
        // Implementação simplificada
        return [
            ['tenant_id' => '1:1', 'activity_count' => 450],
            ['tenant_id' => '2:2', 'activity_count' => 320],
            ['tenant_id' => '3:3', 'activity_count' => 180]
        ];
    }
    
    /**
     * Exportar logs em formato JSON
     */
    protected function exportJSON(array $logs, int $idContador, int $idEmpresa)
    {
        $filename = "audit_logs_{$idContador}_{$idEmpresa}_" . date('Y-m-d') . '.json';
        
        $this->response->setHeader('Content-Type', 'application/json')
                      ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        
        return $this->response->setBody(json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Exportar logs em formato CSV
     */
    protected function exportCSV(array $logs, int $idContador, int $idEmpresa)
    {
        $filename = "audit_logs_{$idContador}_{$idEmpresa}_" . date('Y-m-d') . '.csv';
        
        $this->response->setHeader('Content-Type', 'text/csv')
                      ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        
        $output = fopen('php://temp', 'w');
        
        // Cabeçalhos
        fputcsv($output, [
            'Timestamp', 'Level', 'Message', 'Tenant ID', 'User ID', 'IP Address', 
            'URI', 'Method', 'Event Type', 'Context'
        ]);
        
        // Dados
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['timestamp'],
                $log['level'],
                $log['message'],
                $log['tenant_id'],
                $log['user_id'],
                $log['ip_address'],
                $log['uri'],
                $log['method'],
                $log['context']['event_type'] ?? '',
                json_encode($log['context'])
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $this->response->setBody($csv);
    }
    
    /**
     * Verificar permissão de admin
     */
    protected function checkAdminPermission(): void
    {
        $session = session();
        $userType = $session->get('tipo');
        $userRole = $session->get('role');
        
        // Verificar se é admin ou master
        $isAdmin = in_array($userType, ['1', 'admin', 'master']) || 
                   in_array($userRole, ['admin', 'master', 'super_admin']);
        
        if (!$isAdmin) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Access denied');
        }
    }
    
    /**
     * Verificar se é admin global
     */
    protected function isGlobalAdmin(): bool
    {
        $session = session();
        $userType = $session->get('tipo');
        $userRole = $session->get('role');
        
        return in_array($userType, ['1', 'master']) || 
               in_array($userRole, ['master', 'super_admin']);
    }
    
    /**
     * Contar logs para uma data específica
     */
    protected function countLogsForDate(string $date): int
    {
        // Implementação simplificada - em produção, você contaria os logs reais
        return rand(100, 500);
    }
    
    /**
     * Obter tenants ativos
     */
    protected function getActiveTenants(string $since): int
    {
        // Implementação simplificada
        return $this->db->table('empresas')
                       ->where('status', 'ativo')
                       ->where('updated_at >=', $since)
                       ->countAllResults();
    }
    
    /**
     * Obter logs por nível
     */
    protected function getLogsByLevel(string $dateFrom, string $dateTo): array
    {
        // Implementação mockada - em produção, analisaria logs reais
        return [
            'info' => 1200,
            'warning' => 150,
            'error' => 25,
            'security' => 8,
            'audit' => 890
        ];
    }
    
    /**
     * Obter logs por tipo de evento
     */
    protected function getLogsByEventType(string $dateFrom, string $dateTo): array
    {
        return [
            'authentication' => 450,
            'crud_operation' => 1200,
            'api_call' => 2100,
            'access_denied' => 35,
            'configuration' => 80,
            'financial' => 320
        ];
    }
    
    /**
     * Obter logs por tenant
     */
    protected function getLogsByTenant(string $dateFrom, string $dateTo): array
    {
        return [
            '1:1' => 850,
            '2:2' => 650,
            '3:3' => 420,
            '4:4' => 280
        ];
    }
    
    /**
     * Obter alertas por tipo
     */
    protected function getAlertsByType(string $dateFrom, string $dateTo): array
    {
        return $this->db->table('security_alerts')
                       ->select('alert_type, COUNT(*) as count')
                       ->where('DATE(created_at) >=', $dateFrom)
                       ->where('DATE(created_at) <=', $dateTo)
                       ->groupBy('alert_type')
                       ->get()
                       ->getResultArray();
    }
    
    /**
     * Obter usuários mais ativos
     */
    protected function getTopUsers(string $dateFrom, string $dateTo): array
    {
        // Implementação mockada
        return [
            ['user_id' => 1, 'username' => 'admin', 'activity_count' => 450],
            ['user_id' => 2, 'username' => 'user1', 'activity_count' => 320],
            ['user_id' => 3, 'username' => 'user2', 'activity_count' => 280]
        ];
    }
    
    /**
     * Obter métricas de performance
     */
    protected function getPerformanceMetrics(string $dateFrom, string $dateTo): array
    {
        return [
            'avg_response_time' => 0.245,
            'slow_requests' => 12,
            'error_rate' => 0.02,
            'peak_memory_usage' => 45.6
        ];
    }
}
