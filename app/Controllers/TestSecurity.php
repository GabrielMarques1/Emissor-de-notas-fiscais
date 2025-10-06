<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Controller de Teste para Validar TenantFilter
 * Testa se o filtro está funcionando corretamente
 */
class TestSecurity extends Controller
{
    /**
     * Teste básico - deve ser bloqueado se não tiver tenant
     */
    public function index()
    {
        $session = session();
        $idContador = $session->get('id_contador');
        $idEmpresa = $session->get('id_empresa');
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'TenantFilter funcionando - acesso autorizado',
            'tenant_id' => "{$idContador}:{$idEmpresa}",
            'session_data' => [
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
                'id_usuario' => $session->get('id_usuario')
            ],
            'request_data' => [
                'tenant_id' => $this->request->tenantId ?? $this->request->tenant_id ?? ($this->request->tenantData['tenant_id'] ?? 'não injetado'),
                'id_contador' => $this->request->idContador ?? $this->request->id_contador ?? ($this->request->tenantData['id_contador'] ?? 'não injetado'),
                'id_empresa' => $this->request->idEmpresa ?? $this->request->id_empresa ?? ($this->request->tenantData['id_empresa'] ?? 'não injetado'),
                'user_id' => $this->request->userId ?? $this->request->user_id ?? ($this->request->tenantData['user_id'] ?? 'não injetado'),
                'is_master' => $this->request->isMaster ?? $this->request->is_master ?? ($this->request->tenantData['is_master'] ?? false),
                'tenant_data_exists' => isset($this->request->tenantData),
                'all_request_properties' => get_object_vars($this->request)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Teste de violação - simula acesso sem tenant
     */
    public function testViolation()
    {
        // Limpar sessão para forçar violação
        session()->destroy();
        
        return $this->response->setJSON([
            'message' => 'Se você está vendo isso, o TenantFilter não está funcionando!',
            'error' => 'Este endpoint deveria ter sido bloqueado'
        ]);
    }
    
    /**
     * Teste de auditoria - verifica se logs estão sendo criados
     */
    public function testAudit()
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists('security_audit')) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Tabela security_audit não existe'
            ]);
        }
        
        // Buscar últimas violações
        $violations = $db->table('security_audit')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Auditoria funcionando',
            'total_violations' => $db->table('security_audit')->countAllResults(),
            'recent_violations' => $violations
        ]);
    }
    
    /**
     * Teste público - não deve ser bloqueado
     */
    public function publicTest()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Endpoint público funcionando',
            'note' => 'Este endpoint deve funcionar sem tenant'
        ]);
    }
    
    /**
     * Verificar dados da sessão atual
     */
    public function sessionInfo()
    {
        $session = session();
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Dados da sessão atual',
            'session_data' => [
                'id_usuario' => $session->get('id_usuario'),
                'id_contador' => $session->get('id_contador'),
                'id_empresa' => $session->get('id_empresa'),
                'tipo' => $session->get('tipo'),
                'role' => $session->get('role'),
                'nome' => $session->get('nome'),
                'email' => $session->get('email'),
                'is_logged_in' => $session->get('logged_in'),
            ],
            'is_master_criteria' => [
                'user_id_in_master_list' => in_array($session->get('id_usuario'), [1, 2]),
                'type_is_master' => in_array($session->get('tipo'), ['master', 'admin', 'super_admin', '1', 1]),
                'role_is_master' => in_array($session->get('role'), ['master', 'super_admin', 'admin']),
                'tipo_1_check' => $session->get('tipo') === '1',
                'has_session' => !empty($session->get('tipo')),
            ],
            'request_data' => [
                'tenant_id' => $this->request->tenantId ?? $this->request->tenant_id ?? ($this->request->tenantData['tenant_id'] ?? 'não definido'),
                'is_master' => $this->request->isMaster ?? $this->request->is_master ?? ($this->request->tenantData['is_master'] ?? false),
                'tenant_data_exists' => isset($this->request->tenantData),
            ]
        ]);
    }
}
