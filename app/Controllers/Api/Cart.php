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
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
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
        if ($idContador === 0 || $idEmpresa === 0) {
            if (function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }
        }

        $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? ($this->request->getJSON(true) ?? $this->request->getPost())
            : [];
        if (! $payload) return $this->failValidationErrors('Payload vazio');

        $required = ['nome','unidade','quantidade','valor_unitario','CFOP_NFCe','NCM','CSOSN'];
        foreach ($required as $f) { if (!isset($payload[$f])) return $this->failValidationErrors("Campo obrigatório: {$f}"); }

        $payload['id_contador'] = $idContador;
        $payload['id_empresa']  = $idEmpresa;

        // Preencher dados fiscais a partir do produto do ERP quando id_produto informado
        if (isset($payload['id_produto'])) {
            $payload['id_produto'] = (int) $payload['id_produto'];
            if ($payload['id_produto'] > 0) {
                try {
                    $prod = (new \App\Models\ProdutoModel())->find($payload['id_produto']);
                    if ($prod) {
                        $payload['nome'] = $payload['nome'] ?? ($prod['nome'] ?? $payload['nome']);
                        $payload['codigo_de_barras'] = $payload['codigo_de_barras'] ?? ($prod['codigo_de_barras'] ?? 'SEM GTIN');
                        $payload['valor_unitario'] = $payload['valor_unitario'] ?? ($prod['valor_unitario'] ?? 0);
                        $payload['CFOP_NFCe'] = $payload['CFOP_NFCe'] ?? ($prod['CFOP_NFCe'] ?? '5102');
                        $payload['CFOP_NFe'] = $payload['CFOP_NFe'] ?? ($prod['CFOP_NFe'] ?? '5102');
                        $payload['CFOP_Externo'] = $payload['CFOP_Externo'] ?? ($prod['CFOP_Externo'] ?? '6102');
                        $payload['NCM'] = $payload['NCM'] ?? ($prod['NCM'] ?? '00000000');
                        $payload['CSOSN'] = $payload['CSOSN'] ?? ($prod['CSOSN'] ?? '102');
                    }
                } catch (\Throwable $e) { /* continua com payload */ }
            }
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
        if ($idContador === 0 || $idEmpresa === 0) {
            if (function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }
        }
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
        if ($idContador === 0 || $idEmpresa === 0) {
            if (function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }
        }
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


