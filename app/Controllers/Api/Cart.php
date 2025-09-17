<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProdutoProvisorioModel;

class Cart extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        $model = new ProdutoProvisorioModel();
        $items = $model->where('id_contador', $idContador)
                       ->where('id_empresa', $idEmpresa)
                       ->findAll();
        return $this->respond($items);
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

        $model = new ProdutoProvisorioModel();
        if (! $model->insert($payload)) {
            return $this->failValidationErrors($model->errors());
        }
        return $this->respondCreated($model->find($model->getInsertID()));
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


