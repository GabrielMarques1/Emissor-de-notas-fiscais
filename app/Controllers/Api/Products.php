<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProdutoModel;

class Products extends ResourceController
{
    protected $format = 'json';

    // GET /api/products?q=...&all=1
    public function index()
    {
        try {
            $q = trim((string) ($this->request->getGet('q') ?? ''));
            $all = (string) ($this->request->getGet('all') ?? '0');
            $limit = max(1, min(50, (int) ($this->request->getGet('limit') ?? 20)));

            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);

            $model = new ProdutoModel();
            $builder = $model->builder();
            if ($all !== '1') {
                if ($idContador) { $builder->where('id_contador', $idContador); }
                if ($idEmpresa)  { $builder->where('id_empresa',  $idEmpresa); }
            }
            if ($q !== '') {
                $builder->groupStart()
                        ->like('nome', $q)
                        ->orLike('codigo_de_barras', $q)
                        ->groupEnd();
            }
            $builder->orderBy('id_produto', 'DESC')->limit($limit);
            $rows = $builder->get()->getResultArray();
            return $this->respond($rows ?: []);
        } catch (\Throwable $e) {
            return $this->respond([]);
        }
    }

    // GET /api/products/barcode/{ean}
    public function barcode($ean)
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            $ean = trim((string) $ean);

            $model = new ProdutoModel();
            // 1) empresa + contador exato
            $prod = $model->where('id_contador', $idContador)
                          ->where('id_empresa', $idEmpresa)
                          ->where('codigo_de_barras', $ean)
                          ->first();
            // 2) empresa + contador LIKE (prefixo)
            if (! $prod) {
                $prod = (new ProdutoModel())
                    ->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->like('codigo_de_barras', $ean)
                    ->orderBy('id_produto', 'DESC')->first();
            }
            // 3) somente contador
            if (! $prod && $idContador) {
                $prod = (new ProdutoModel())
                    ->where('id_contador', $idContador)
                    ->where('codigo_de_barras', $ean)
                    ->first();
            }
            // 4) global (fallback controlado)
            if (! $prod) {
                $prod = (new ProdutoModel())
                    ->where('codigo_de_barras', $ean)
                    ->first();
            }
            if (! $prod) {
                return $this->failNotFound('Produto não encontrado');
            }
            return $this->respond($prod);
        } catch (\Throwable $e) {
            return $this->failServerError('Erro ao buscar produto');
        }
    }

    // GET /api/products/search?q=termo
    public function search()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if ($q === '') {
            return $this->failValidationErrors('Parâmetro q é obrigatório');
        }

        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);

        // Estratégia: usar novas instâncias para evitar acúmulo de WHEREs
        $prodModel = new ProdutoModel();
        $byBarcode = $prodModel->where('id_contador', $idContador)
                               ->where('id_empresa', $idEmpresa)
                               ->where('codigo_de_barras', $q)
                               ->first();
        if ($byBarcode) return $this->respond([$byBarcode]);

        if (ctype_digit($q)) {
            $byId = (new ProdutoModel())
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->where('id_produto', (int) $q)
                ->first();
            if ($byId) return $this->respond([$byId]);
        }

        // 1) empresa + contador com OR por nome/codigo
        $like = (new ProdutoModel())
            ->where('id_contador', $idContador)
            ->where('id_empresa', $idEmpresa)
            ->groupStart()
                ->like('nome', $q)
                ->orLike('codigo_de_barras', $q)
            ->groupEnd()
            ->orderBy('id_produto', 'DESC')
            ->findAll(10);
        if (!empty($like)) return $this->respond($like);

        // 2) somente contador
        if ($idContador) {
            $like = (new ProdutoModel())
                ->where('id_contador', $idContador)
                ->groupStart()
                    ->like('nome', $q)
                    ->orLike('codigo_de_barras', $q)
                ->groupEnd()
                ->orderBy('id_produto', 'DESC')
                ->findAll(10);
            if (!empty($like)) return $this->respond($like);
        }

        // 3) global fallback
        $like = (new ProdutoModel())
            ->groupStart()
                ->like('nome', $q)
                ->orLike('codigo_de_barras', $q)
            ->groupEnd()
            ->orderBy('id_produto', 'DESC')
            ->findAll(10);
        return $this->respond($like ?: []);
    }
}


