<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class CashRegisters extends ResourceController
{
    protected $format = 'json';
    protected $modelName = 'App\\Models\\CashRegisterModel';

    public function index()
    {
        try {
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $perPage = (int) ($this->request->getGet('per_page') ?? 20);
            $perPage = $perPage > 0 && $perPage <= 200 ? $perPage : 20;
            $status = (string) ($this->request->getGet('status') ?? '');
            $q = trim((string) ($this->request->getGet('q') ?? ''));

            $builder = $this->model;

            // Multi-tenant por sessão, quando disponível
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            if ($idContador) { $builder = $builder->where('id_contador', $idContador); }
            if ($idEmpresa)  { $builder = $builder->where('id_empresa',  $idEmpresa); }

            if ($status !== '') {
                $builder = $builder->where('status', $status);
            }
            if ($q !== '') {
                $builder = $builder->groupStart()
                                   ->like('name', $q)
                                   ->orLike('location', $q)
                                   ->groupEnd();
            }

            // Clonar para contar
            $countBuilder = clone $builder;
            $total = (int) $countBuilder->countAllResults();

            $offset = ($page - 1) * $perPage;
            $items = $builder->orderBy('id_cash_register', 'DESC')
                             ->findAll($perPage, $offset);

            return $this->respond([
                'data' => $items,
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'has_next' => ($offset + $perPage) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respond(['data' => [], 'meta' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'has_next' => false]]);
        }
    }

    public function create()
    {
        $session = session();
        $data = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? ($this->request->getJSON(true) ?? $this->request->getPost())
            : [];

        $payload = [
            'name' => (string) ($data['name'] ?? 'Caixa 1'),
            'location' => (string) ($data['location'] ?? 'Loja'),
            'status' => (string) ($data['status'] ?? 'closed'),
        ];
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        if ($idContador) $payload['id_contador'] = $idContador;
        if ($idEmpresa)  $payload['id_empresa']  = $idEmpresa;

        if (! $this->model->insert($payload)) {
            return $this->failValidationErrors($this->model->errors());
        }
        $id = $this->model->getInsertID();
        // Evita problemas de serialização de entidades em JSON
        $created = $this->model->asArray()->find($id);
        return $this->respondCreated($created);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) {
            return $this->failNotFound('Registro não encontrado');
        }
        return $this->respond($data);
    }
}


