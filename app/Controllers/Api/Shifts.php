<?php

namespace App\Controllers\Api;

use App\Models\ShiftModel;
use App\Models\CaixaSessaoModel;
use App\Models\CashRegisterModel;
use CodeIgniter\RESTful\ResourceController;

class Shifts extends ResourceController
{
    protected $format = 'json';
    protected $modelName = 'App\\Models\\ShiftModel';

    public function index()
    {
        try {
            $session = session();
            $builder = $this->model;
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            if ($idContador) { $builder = $builder->where('id_contador', $idContador); }
            if ($idEmpresa)  { $builder = $builder->where('id_empresa',  $idEmpresa); }
            $items = $builder->orderBy('id_shift', 'DESC')->findAll(50);
            return $this->respond($items);
        } catch (\Throwable $e) {
            return $this->respond([]);
        }
    }

    // Cash Registers - consolidado aqui (listar)
    public function cashRegistersIndex()
    {
        try {
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $perPage = (int) ($this->request->getGet('per_page') ?? 20);
            $perPage = $perPage > 0 && $perPage <= 200 ? $perPage : 20;
            $status = (string) ($this->request->getGet('status') ?? '');
            $q = trim((string) ($this->request->getGet('q') ?? ''));

            $model = new CashRegisterModel();
            $builder = $model;

            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            if ($idContador) { $builder = $builder->where('id_contador', $idContador); }
            if ($idEmpresa)  { $builder = $builder->where('id_empresa',  $idEmpresa); }

            if ($status !== '') { $builder = $builder->where('status', $status); }
            if ($q !== '') { $builder = $builder->groupStart()->like('name', $q)->orLike('location', $q)->groupEnd(); }

            $countBuilder = clone $builder;
            $total = (int) $countBuilder->countAllResults();
            $offset = ($page - 1) * $perPage;
            $items = $builder->orderBy('id_cash_register', 'DESC')->findAll($perPage, $offset);
            return $this->respond(['data' => $items, 'meta' => [ 'page'=>$page, 'per_page'=>$perPage, 'total'=>$total, 'has_next' => ($offset + $perPage) < $total ]]);
        } catch (\Throwable $e) {
            return $this->respond(['data' => [], 'meta' => ['page'=>1,'per_page'=>20,'total'=>0,'has_next'=>false]]);
        }
    }

    // Cash Registers - criar
    public function cashRegistersCreate()
    {
        $data = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? ($this->request->getJSON(true) ?? $this->request->getPost())
            : [];
        $payload = [
            'name' => (string) ($data['name'] ?? 'Caixa 1'),
            'location' => (string) ($data['location'] ?? 'Loja'),
            'status' => (string) ($data['status'] ?? 'closed'),
        ];
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        if ($idContador) $payload['id_contador'] = $idContador;
        if ($idEmpresa)  $payload['id_empresa']  = $idEmpresa;

        $model = new CashRegisterModel();
        if (! $model->insert($payload)) {
            return $this->failValidationErrors($model->errors());
        }
        $id = $model->getInsertID();
        $created = $model->asArray()->find($id);
        return $this->respondCreated($created);
    }

    // Cash Registers - obter 1
    public function cashRegistersShow($id = null)
    {
        $model = new CashRegisterModel();
        $data = $model->find($id);
        if (! $data) {
            return $this->failNotFound('Registro não encontrado');
        }
        return $this->respond($data);
    }

    public function open()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        if (!isset($payload['id_cash_register'])) {
            return $this->failValidationErrors('id_cash_register é obrigatório');
        }
        $data = [
            'id_cash_register' => (int) $payload['id_cash_register'],
            'opened_by' => (string) ($payload['opened_by'] ?? 'system'),
            'opened_at' => date('Y-m-d H:i:s'),
            'opening_amount' => (float) ($payload['opening_amount'] ?? 0),
            'status' => 'open',
        ];
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        
        // Para APIs sem sessão, usar valores padrão para teste
        if ($idContador === 0) $idContador = 1;
        if ($idEmpresa === 0)  $idEmpresa  = 5; // ou usar o último ID de empresa válido
        
        $data['id_contador'] = $idContador;
        $data['id_empresa']  = $idEmpresa;

        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }
        $id = $this->model->getInsertID();

        // Usa model centralizado para abrir sessão de caixa (garante exclusividade)
        try {
            $session = session();
            $idUsuario = (int) ($session->get('id_usuario') ?? 0);
            $modelCaixa = new CaixaSessaoModel();
            $modelCaixa->openSession((int) ($data['id_contador'] ?? 0), (int) ($data['id_empresa'] ?? 0), $idUsuario, (float) ($data['opening_amount'] ?? 0));
            log_message('info', 'Shifts::open - Caixa sessão aberta via model centralizado.');
        } catch (\Throwable $e) {
            log_message('error', 'Shifts::open - Falha ao abrir caixa via model: {err}', ['err' => $e->getMessage()]);
        }

        return $this->respondCreated($this->model->find($id));
    }

    // Alias PT-BR: abrir -> open
    public function abrir()
    {
        return $this->open();
    }

    public function close($id = null)
    {
		if (!$id) return $this->failValidationErrors('ID obrigatório');
		$payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
		log_message('critical', 'ATENÇÃO: Shifts::close() FOI EXECUTADO. id={id}', ['id' => (int) $id]);
		log_message('debug', 'Shifts::close - Início. ID turno={id}, payload={payload}', [
			'id' => (int) $id,
			'payload' => json_encode($payload),
		]);

        // Normaliza valor informado no popup (ex.: "1.234,56" -> 1234.56)
		$closingAmount = $payload['closing_amount'] ?? 0;
        if (is_string($closingAmount)) {
            $normalized = str_replace(['.', ' '], '', $closingAmount);
            $normalized = str_replace(',', '.', $normalized);
			$closingAmount = is_numeric($normalized) ? (float) $normalized : 0.0;
        } else {
            $closingAmount = (float) $closingAmount;
        }
		log_message('debug', 'Shifts::close - closing_amount normalizado={amt}', ['amt' => $closingAmount]);

        // Busca o turno para obter campos obrigatórios exigidos pela validação (ex.: id_cash_register)
		$existing = $this->model->asArray()->find((int) $id);
        if (!$existing) {
            return $this->failNotFound('Turno não encontrado');
        }
		log_message('debug', 'Shifts::close - Turno atual carregado: {existing}', ['existing' => json_encode($existing)]);
        // Opcional: impedir fechamento se já estiver fechado
        if (isset($existing['status']) && strtolower((string) $existing['status']) === 'closed') {
            return $this->fail('Turno já está fechado.', 409);
        }

		// Diagnóstico: totais de vendas por forma de pagamento no turno
		try {
			$db = \Config\Database::connect();
			$det = $db->table('pos_sales')
				->select('payment_type, SUM(total) as valor, COUNT(*) as qtd')
				->where('id_shift', (int) $id)
				->where('status', 'finalized')
				->groupBy('payment_type')
				->get()->getResultArray();
			log_message('debug', 'Shifts::close - Totais por pagamento: {det}', ['det' => json_encode($det)]);
		} catch (\Throwable $e) {
			log_message('debug', 'Shifts::close - Falha ao calcular totais: {err}', ['err' => $e->getMessage()]);
		}

		$data = [
            'closed_by' => (string) ($payload['closed_by'] ?? 'system'),
            'closed_at' => date('Y-m-d H:i:s'),
            'closing_amount' => $closingAmount,
            'status' => 'closed',
            // Garante presença para satisfazer as regras de validação do modelo
            'id_cash_register' => (int) ($existing['id_cash_register'] ?? 0),
        ];
		log_message('debug', 'Shifts::close - Dados para update: {data}', ['data' => json_encode($data)]);
        try {
            if (!$this->model->update($id, $data)) {
				log_message('error', 'Shifts::close - Falha no update. errors={errors}', ['errors' => $this->model->errors()]);
				return $this->failValidationErrors($this->model->errors());
            }
			$updated = $this->model->find($id);
			log_message('debug', 'Shifts::close - Sucesso. Registro atualizado: {updated}', ['updated' => json_encode(is_object($updated)? (array) $updated : $updated)]);

            // Usar model centralizado para fechar sessão de caixa aberta
            try {
                $session = session();
                $idContador = (int) ($session->get('id_contador') ?? 0);
                $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
                if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                    [$idContador,$idEmpresa] = resolve_tenant_ids();
                }
                $idUsuario = (int) ($session->get('id_usuario') ?? 0);
                
                // Se não conseguiu resolver IDs, usar os do shift atual
                if ($idContador === 0 || $idEmpresa === 0) {
                    $idContador = (int) ($existing['id_contador'] ?? 0);
                    $idEmpresa  = (int) ($existing['id_empresa']  ?? 0);
                    log_message('debug', 'Shifts::close - Usando IDs do shift: contador={cont}, empresa={emp}', ['cont' => $idContador, 'emp' => $idEmpresa]);
                }
                
                log_message('debug', 'Shifts::close - Tentando fechar caixa com: contador={cont}, empresa={emp}, usuario={user}, valor={val}', [
                    'cont' => $idContador, 'emp' => $idEmpresa, 'user' => $idUsuario, 'val' => $closingAmount
                ]);
                
                $modelCaixa = new CaixaSessaoModel();
                $closed = $modelCaixa->closeOpenSession($idContador, $idEmpresa, $idUsuario, (float) $closingAmount);
                log_message('info', 'Shifts::close - Caixa sessão fechada via model centralizado. id={id}', ['id' => $closed['id'] ?? null]);
            } catch (\Throwable $e) {
                log_message('error', 'Shifts::close - Falha ao fechar caixa via model: {err}', ['err' => $e->getMessage()]);
            }

			return $this->respond($updated);
        } catch (\Throwable $e) {
			log_message('error', 'Shifts::close - Exceção no update: {err}', ['err' => $e->getMessage()]);
			return $this->failValidationErrors($this->model->errors());
        }
    }

    // Alias PT-BR: fechar -> close do turno ativo (resolve último aberto)
    public function fechar()
    {
        // Fecha o último turno aberto do tenant atual
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        $db = \Config\Database::connect();
        $row = $db->table('shifts')
            ->select('id_shift')
            ->where('status', 'open')
            ->where('id_contador', $idContador ?: 0)
            ->where('id_empresa',  $idEmpresa  ?: 0)
            ->orderBy('id_shift', 'DESC')
            ->get(1)->getFirstRow('array');
        if (! $row) { return $this->failNotFound('Nenhum turno aberto'); }
        return $this->close((int) $row['id_shift']);
    }

    // Status simples: retorna turnos recentes e se há aberto
    public function status()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $builder = $this->model;
            if ($idContador) { $builder = $builder->where('id_contador', $idContador); }
            if ($idEmpresa)  { $builder = $builder->where('id_empresa',  $idEmpresa); }
            $items = $builder->orderBy('id_shift', 'DESC')->findAll(10);
            $hasOpen = false; foreach ($items as $it) { if (strtolower((string) ($it['status'] ?? '')) === 'open') { $hasOpen = true; break; } }
            return $this->respond(['has_open' => $hasOpen, 'items' => $items]);
        } catch (\Throwable $e) {
            return $this->respond(['has_open' => false, 'items' => []]);
        }
    }

    // Relatório simples do turno: totais por forma de pagamento e total geral
    public function report($id = null)
    {
        if (!$id) return $this->failValidationErrors('ID obrigatório');
        
        // CORREÇÃO CRÍTICA: Verificar se o shift pertence ao tenant atual
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        
        // Verificar se o shift existe e pertence ao tenant
        $shift = $this->model->asArray()->find((int) $id);
        if (!$shift) {
            return $this->failNotFound('Turno não encontrado');
        }
        
        // SEGURANÇA MULTI-TENANT: Verificar se o shift pertence ao tenant atual
        if (($idContador && (int) ($shift['id_contador'] ?? 0) !== $idContador) ||
            ($idEmpresa && (int) ($shift['id_empresa'] ?? 0) !== $idEmpresa)) {
            return $this->failForbidden('Acesso negado ao turno de outra empresa');
        }
        
        $db = \Config\Database::connect();
        $rows = $db->table('pos_sales')
                   ->select('payment_type, SUM(total) as valor, COUNT(*) as qtd')
                   ->where('id_shift', (int) $id)
                   ->where('status', 'finalized')
                   ->groupBy('payment_type')
                   ->get()->getResultArray();
        $totalGeral = 0.0;
        foreach ($rows as $r) { $totalGeral += (float) $r['valor']; }
        return $this->respond(['itens' => $rows, 'total' => $totalGeral]);
    }
}


