<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CashMovementModel;
use App\Models\ShiftModel;

/**
 * API de Sangria e Suprimento de Caixa
 * 
 * Endpoints:
 * - POST /api/cash-movements/withdrawal (sangria)
 * - POST /api/cash-movements/supply (suprimento)
 * - GET /api/cash-movements (listar movimentos)
 * - GET /api/cash-movements/{id} (detalhes)
 */
class CashMovements extends ResourceController
{
    protected $format = 'json';
    protected $modelName = 'App\\Models\\CashMovementModel';
    
    /**
     * Registrar sangria (retirada de dinheiro do caixa)
     * POST /api/cash-movements/withdrawal
     */
    public function withdrawal()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        
        if (!$payload) {
            return $this->failValidationErrors('Payload vazio');
        }
        
        // Validações
        $required = ['id_shift', 'amount', 'reason'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                return $this->failValidationErrors("Campo obrigatório: {$field}");
            }
        }
        
        $amount = (float) $payload['amount'];
        
        if ($amount <= 0) {
            return $this->failValidationErrors('Valor deve ser maior que zero');
        }
        
        // Buscar turno e validar que está aberto
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->find((int) $payload['id_shift']);
        
        if (!$shift) {
            return $this->failNotFound('Turno não encontrado');
        }
        
        if ($shift['status'] !== 'open') {
            return $this->fail('Turno não está aberto', 409);
        }
        
        // Obter IDs de tenant e operador
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        $performedBy = (int) ($session->get('id_login') ?? 0);
        
        if (!$idContador || !$idEmpresa || !$performedBy) {
            return $this->fail('Sessão inválida', 401);
        }
        
        // Registrar sangria
        $data = [
            'id_shift' => (int) $payload['id_shift'],
            'id_cash_register' => (int) $shift['id_cash_register'],
            'type' => 'withdrawal',
            'amount' => $amount,
            'reason' => (string) $payload['reason'],
            'notes' => (string) ($payload['notes'] ?? ''),
            'performed_by' => $performedBy,
            'authorized_by' => isset($payload['authorized_by']) ? (int) $payload['authorized_by'] : null,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ];
        
        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }
        
        $id = $this->model->getInsertID();
        $movement = $this->model->find($id);
        
        log_message('info', '[CashMovement] Sangria registrada', [
            'id_movement' => $id,
            'id_shift' => $data['id_shift'],
            'amount' => $amount,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return $this->respondCreated([
            'success' => true,
            'message' => 'Sangria registrada com sucesso',
            'movement' => $movement,
        ]);
    }
    
    /**
     * Registrar suprimento (adição de dinheiro ao caixa)
     * POST /api/cash-movements/supply
     */
    public function supply()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        
        if (!$payload) {
            return $this->failValidationErrors('Payload vazio');
        }
        
        // Validações
        $required = ['id_shift', 'amount', 'reason'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                return $this->failValidationErrors("Campo obrigatório: {$field}");
            }
        }
        
        $amount = (float) $payload['amount'];
        
        if ($amount <= 0) {
            return $this->failValidationErrors('Valor deve ser maior que zero');
        }
        
        // Buscar turno e validar que está aberto
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->find((int) $payload['id_shift']);
        
        if (!$shift) {
            return $this->failNotFound('Turno não encontrado');
        }
        
        if ($shift['status'] !== 'open') {
            return $this->fail('Turno não está aberto', 409);
        }
        
        // Obter IDs de tenant e operador
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        $performedBy = (int) ($session->get('id_login') ?? 0);
        
        if (!$idContador || !$idEmpresa || !$performedBy) {
            return $this->fail('Sessão inválida', 401);
        }
        
        // Registrar suprimento
        $data = [
            'id_shift' => (int) $payload['id_shift'],
            'id_cash_register' => (int) $shift['id_cash_register'],
            'type' => 'supply',
            'amount' => $amount,
            'reason' => (string) $payload['reason'],
            'notes' => (string) ($payload['notes'] ?? ''),
            'performed_by' => $performedBy,
            'authorized_by' => isset($payload['authorized_by']) ? (int) $payload['authorized_by'] : null,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ];
        
        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }
        
        $id = $this->model->getInsertID();
        $movement = $this->model->find($id);
        
        log_message('info', '[CashMovement] Suprimento registrado', [
            'id_movement' => $id,
            'id_shift' => $data['id_shift'],
            'amount' => $amount,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return $this->respondCreated([
            'success' => true,
            'message' => 'Suprimento registrado com sucesso',
            'movement' => $movement,
        ]);
    }
    
    /**
     * Listar movimentos de caixa
     * GET /api/cash-movements?id_shift={id}
     */
    public function index()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            
            if (!$idContador || !$idEmpresa) {
                return $this->fail('Sessão inválida', 401);
            }
            
            $builder = $this->model->builder();
            
            // Filtro de tenant (automático via BaseAppModel, mas explicitado para segurança)
            $builder->where('cash_movements.id_contador', $idContador);
            $builder->where('cash_movements.id_empresa', $idEmpresa);
            
            // Filtros opcionais
            $idShift = $this->request->getGet('id_shift');
            if ($idShift) {
                $builder->where('cash_movements.id_shift', (int) $idShift);
            }
            
            $type = $this->request->getGet('type');
            if ($type && in_array($type, ['withdrawal', 'supply'])) {
                $builder->where('cash_movements.type', $type);
            }
            
            $dateFrom = $this->request->getGet('date_from');
            if ($dateFrom) {
                $builder->where('cash_movements.created_at >=', $dateFrom . ' 00:00:00');
            }
            
            $dateTo = $this->request->getGet('date_to');
            if ($dateTo) {
                $builder->where('cash_movements.created_at <=', $dateTo . ' 23:59:59');
            }
            
            // Join com operadores
            $builder->select('cash_movements.*, l1.usuario as operador, l2.usuario as autorizador');
            $builder->join('logins l1', 'l1.id_login = cash_movements.performed_by', 'left');
            $builder->join('logins l2', 'l2.id_login = cash_movements.authorized_by', 'left');
            
            $movements = $builder->orderBy('cash_movements.created_at', 'DESC')->get()->getResultArray();
            
            return $this->respond([
                'success' => true,
                'count' => count($movements),
                'movements' => $movements,
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[CashMovement] Erro ao listar', [
                'error' => $e->getMessage(),
            ]);
            
            return $this->fail('Erro ao listar movimentos: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Detalhes de um movimento
     * GET /api/cash-movements/{id}
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('ID é obrigatório');
        }
        
        $movement = $this->model->find($id);
        
        if (!$movement) {
            return $this->failNotFound('Movimento não encontrado');
        }
        
        return $this->respond([
            'success' => true,
            'movement' => $movement,
        ]);
    }
}

