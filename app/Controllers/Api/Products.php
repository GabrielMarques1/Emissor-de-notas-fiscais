<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProdutoModel;

class Products extends ResourceController
{
    protected $format = 'json';

    // GET /api/products/barcode/{ean}
    public function barcode($ean)
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);

        $model = new ProdutoModel();
        $prod = $model->where('id_contador', $idContador)
                      ->where('id_empresa', $idEmpresa)
                      ->where('codigo_de_barras', $ean)
                      ->first();
        if (! $prod) {
            return $this->failNotFound('Produto não encontrado');
        }
        return $this->respond($prod);
    }
}


