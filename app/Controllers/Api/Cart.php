<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProdutoProvisorioModel;

class Cart extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            $model = new ProdutoProvisorioModel();
            $items = $model->where('id_contador', $idContador)
                           ->where('id_empresa', $idEmpresa)
                           ->findAll();
            return $this->respond($items);
        } catch (\Throwable $e) {
            return $this->respond([]);
        }
    }

    public function create()
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);

        $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? ($this->request->getJSON(true) ?? $this->request->getPost())
            : [];
        if (! $payload) return $this->failValidationErrors('Payload vazio');

        $required = ['nome','unidade','quantidade','valor_unitario','CFOP_NFCe','NCM','CSOSN'];
        foreach ($required as $f) { if (!isset($payload[$f])) return $this->failValidationErrors("Campo obrigatório: {$f}"); }

        $payload['id_contador'] = $idContador;
        $payload['id_empresa']  = $idEmpresa;

        // Garantir ligação com produto do catálogo, se fornecido
        if (isset($payload['id_produto'])) {
            $payload['id_produto'] = (int) $payload['id_produto'];
        }

        $model = new ProdutoProvisorioModel();
        if (! $model->insert($payload)) {
            return $this->failValidationErrors($model->errors());
        }
        return $this->respondCreated($model->find($model->getInsertID()));
    }

    public function update($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID obrigatório');
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? ($this->request->getJSON(true) ?? $this->request->getRawInput())
            : [];
        if (! $payload) return $this->failValidationErrors('Payload vazio');

        $allowed = [];
        if (isset($payload['quantidade'])) $allowed['quantidade'] = max(1, (float) $payload['quantidade']);
        if (isset($payload['desconto'])) $allowed['desconto'] = max(0, (float) $payload['desconto']);
        if (isset($payload['observacao'])) $allowed['observacao'] = (string) $payload['observacao'];
        if (empty($allowed)) return $this->failValidationErrors('Nada para atualizar');

        $model = new ProdutoProvisorioModel();
        $ok = $model->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->where('id_produto_provisorio', (int) $id)
                    ->set($allowed)
                    ->update();
        if (! $ok) return $this->failValidationErrors($model->errors());
        return $this->respond($model->find((int) $id));
    }

    public function delete($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID obrigatório');
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        $model = new ProdutoProvisorioModel();
        $model->where('id_contador', $idContador)
              ->where('id_empresa', $idEmpresa)
              ->where('id_produto_provisorio', (int) $id)
              ->delete();
        return $this->respondDeleted(['id' => (int) $id]);
    }

    public function clear()
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        $model = new ProdutoProvisorioModel();
        $model->where('id_contador', $idContador)
              ->where('id_empresa', $idEmpresa)
              ->delete();
        return $this->respond(['cleared' => true]);
    }
}


