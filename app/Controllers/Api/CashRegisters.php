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
            if ($session->get('id_contador')) {
                $builder = $builder->where('id_contador', (int) $session->get('id_contador'));
            }
            if ($session->get('id_empresa')) {
                $builder = $builder->where('id_empresa', (int) $session->get('id_empresa'));
            }

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

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) {
            return $this->failNotFound('Registro não encontrado');
        }
        return $this->respond($data);
    }
}


