<?php

namespace App\Controllers\Api;

use App\Models\ShiftModel;
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
        if ($idContador) $data['id_contador'] = $idContador;
        if ($idEmpresa)  $data['id_empresa']  = $idEmpresa;

        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }
        $id = $this->model->getInsertID();
        return $this->respondCreated($this->model->find($id));
    }

    public function close($id = null)
    {
        if (!$id) return $this->failValidationErrors('ID obrigatório');
        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $data = [
            'closed_by' => (string) ($payload['closed_by'] ?? 'system'),
            'closed_at' => date('Y-m-d H:i:s'),
            'closing_amount' => (float) ($payload['closing_amount'] ?? 0),
            'status' => 'closed',
        ];
        try {
            if (!$this->model->update($id, $data)) {
                return $this->failValidationErrors($this->model->errors());
            }
            return $this->respond($this->model->find($id));
        } catch (\Throwable $e) {
            return $this->failValidationErrors($this->model->errors());
        }
    }

    // Relatório simples do turno: totais por forma de pagamento e total geral
    public function report($id = null)
    {
        if (!$id) return $this->failValidationErrors('ID obrigatório');
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


