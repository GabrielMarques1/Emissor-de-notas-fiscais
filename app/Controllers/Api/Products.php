<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProdutoModel;

class Products extends ResourceController
{
    protected $format = 'json';

    private function mapProductRow(array $row): array
    {
        $quantidade = (int) ($row['estoque'] ?? 0);
        $row['quantidade_estoque'] = $quantidade;
        if (!isset($row['id'])) {
            $row['id'] = $row['id_produto'] ?? null;
        }
        if (!isset($row['codigo_barras'])) {
            $row['codigo_barras'] = $row['codigo_de_barras'] ?? null;
        }
        if (!isset($row['preco_unitario'])) {
            $row['preco_unitario'] = number_format((float) ($row['valor_unitario'] ?? 0), 2, '.', '');
        }
        return $row;
    }

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
            if ($idContador === 0 || $idEmpresa === 0) {
                if (function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }
            }

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
            $rows = array_map(fn($r) => $this->mapProductRow((array) $r), $rows ?: []);
            return $this->respond($rows);
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
            if ($idContador === 0 || $idEmpresa === 0) {
                if (function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }
            }
            $ean = trim((string) $ean);
            
            // DEBUG: Log da busca por código de barras
            log_message('debug', 'Products::barcode - Buscando EAN: {ean}, Contador: {contador}, Empresa: {empresa}', [
                'ean' => $ean, 'contador' => $idContador, 'empresa' => $idEmpresa
            ]);

            $model = new ProdutoModel();
            // 1) empresa + contador exato
            $prod = $model->where('id_contador', $idContador)
                          ->where('id_empresa', $idEmpresa)
                          ->where('codigo_de_barras', $ean)
                          ->first();
            log_message('debug', 'Products::barcode - Tentativa 1 (empresa+contador exato): {result}', [
                'result' => $prod ? 'ENCONTRADO ID=' . (is_array($prod) ? ($prod['id_produto'] ?? 'N/A') : ($prod->id_produto ?? 'N/A')) : 'NAO_ENCONTRADO'
            ]);
            if ($prod) {
                log_message('debug', 'Products::barcode - Produto encontrado: {data}', [
                    'data' => json_encode(is_array($prod) ? $prod : (array) $prod)
                ]);
            }
            
            // 2) empresa + contador LIKE (prefixo)
            if (! $prod) {
                $prod = (new ProdutoModel())
                    ->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->like('codigo_de_barras', $ean)
                    ->orderBy('id_produto', 'DESC')->first();
                log_message('debug', 'Products::barcode - Tentativa 2 (empresa+contador LIKE): {result}', [
                    'result' => $prod ? 'ENCONTRADO ID=' . ($prod->id_produto ?? 'N/A') : 'NAO_ENCONTRADO'
                ]);
            }
            
            // 3) somente contador
            if (! $prod && $idContador) {
                $prod = (new ProdutoModel())
                    ->where('id_contador', $idContador)
                    ->where('codigo_de_barras', $ean)
                    ->first();
                log_message('debug', 'Products::barcode - Tentativa 3 (somente contador): {result}', [
                    'result' => $prod ? 'ENCONTRADO ID=' . ($prod->id_produto ?? 'N/A') : 'NAO_ENCONTRADO'
                ]);
            }
            
            // 4) global (fallback controlado)
            if (! $prod) {
                $prod = (new ProdutoModel())
                    ->where('codigo_de_barras', $ean)
                    ->first();
                log_message('debug', 'Products::barcode - Tentativa 4 (global): {result}', [
                    'result' => $prod ? 'ENCONTRADO ID=' . ($prod->id_produto ?? 'N/A') : 'NAO_ENCONTRADO'
                ]);
            }
            
            if (! $prod) {
                log_message('warning', 'Products::barcode - Produto não encontrado para EAN: {ean}', ['ean' => $ean]);
                return $this->failNotFound('Produto não encontrado');
            }
            
            // Converter para array antes de mapear
            $prodArray = is_array($prod) ? $prod : (array) $prod;
            log_message('debug', 'Products::barcode - Dados antes do mapeamento: {data}', [
                'data' => json_encode($prodArray)
            ]);
            
            $mappedProduct = $this->mapProductRow($prodArray);
            log_message('debug', 'Products::barcode - Dados após mapeamento: {data}', [
                'data' => json_encode($mappedProduct)
            ]);
            
            return $this->respond($mappedProduct);
        } catch (\Throwable $e) {
            // Não vazar erro interno para a UI; retornar 404 para fluxo do PDV
            return $this->failNotFound('Produto não encontrado');
        }
    }

    // GET /api/products/search?q=termo
    public function search()
    {
        try {
            $q = trim((string) ($this->request->getGet('q') ?? ''));
            $limit = max(1, min(200, (int) ($this->request->getGet('limit') ?? 50)));
            $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
            $offset = ($page - 1) * $limit;

            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if ($idContador === 0 || $idEmpresa === 0) {
                if (function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }
            }
            
            // DEBUG: Log da busca por termo
            log_message('debug', 'Products::search - Termo: {q}, Contador: {contador}, Empresa: {empresa}, Page: {page}, Limit: {limit}', [
                'q' => $q, 'contador' => $idContador, 'empresa' => $idEmpresa, 'page' => $page, 'limit' => $limit
            ]);

            // Se consulta vazia, listar produtos recentes (consulta rápida no PDV)
            if ($q === '') {
                $builder = (new ProdutoModel())->builder();
                if ($idContador) { $builder->where('id_contador', $idContador); }
                if ($idEmpresa)  { $builder->where('id_empresa',  $idEmpresa); }
                $rows = $builder->orderBy('id_produto', 'DESC')->limit($limit, $offset)->get()->getResultArray();
                $rows = array_map(fn($r) => $this->mapProductRow((array) $r), $rows ?: []);
                return $this->respond($rows);
            }

            // Consulta paginada com LIKE (empresa + contador)
            $builder = (new ProdutoModel())->builder();
            if ($idContador) { $builder->where('id_contador', $idContador); }
            if ($idEmpresa)  { $builder->where('id_empresa',  $idEmpresa); }
            $builder->groupStart()
                    ->like('nome', $q)
                    ->orLike('codigo_de_barras', $q);
                    
            // CORREÇÃO: Permitir busca por ID do produto também
            if (ctype_digit($q)) {
                $builder->orWhere('id_produto', (int) $q);
            }
            
            $builder->groupEnd();
            $rows = $builder->orderBy('id_produto', 'DESC')->limit($limit, $offset)->get()->getResultArray();
            
            log_message('debug', 'Products::search - Resultados encontrados: {count}', [
                'count' => count($rows ?: [])
            ]);
            
            if (!empty($rows)) {
                log_message('debug', 'Products::search - Primeiro resultado: {data}', [
                    'data' => json_encode($rows[0] ?? [])
                ]);
            }
            
            $mappedResults = array_map(fn($r) => $this->mapProductRow((array) $r), $rows ?: []);
            
            if (!empty($mappedResults)) {
                log_message('debug', 'Products::search - Primeiro resultado mapeado: {data}', [
                    'data' => json_encode($mappedResults[0] ?? [])
                ]);
            }
            
            return $this->respond($mappedResults);
        } catch (\Throwable $e) {
            // Não vazar erro interno para a UI; retornar lista vazia
            return $this->respond([]);
        }
    }

    // GET /api/products/inventory-movements?id_produto&de=YYYY-MM-DD&ate=YYYY-MM-DD
    public function inventoryMovements()
    {
        if ($this->request->getMethod() === 'get') {
            $id = (int) ($this->request->getGet('id_produto') ?? 0);
            $de = (string) ($this->request->getGet('de') ?? '');
            $ate = (string) ($this->request->getGet('ate') ?? '');
            if ($id <= 0) return $this->failValidationErrors('id_produto é obrigatório');

            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);

            $mov = new \App\Models\InventoryMovementModel();
            $builder = $mov->where('id_produto', $id);
            if ($idContador) $builder = $builder->where('id_contador', $idContador);
            if ($idEmpresa)  $builder = $builder->where('id_empresa', $idEmpresa);
            if ($de !== '')  $builder = $builder->where('created_at >=', $de . ' 00:00:00');
            if ($ate !== '') $builder = $builder->where('created_at <=', $ate . ' 23:59:59');
            $rows = $builder->orderBy('id_inventory_movement', 'DESC')->findAll(200);
            return $this->respond($rows ?: []);
        }
        // POST: ajuste manual
        $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
        $id = (int) ($payload['id_produto'] ?? 0);
        $tipo = (string) ($payload['tipo'] ?? 'entrada');
        $qtd  = (float) ($payload['quantidade'] ?? 0);
        $motivo = (string) ($payload['motivo'] ?? 'Ajuste manual');
        if ($id <= 0 || $qtd <= 0) return $this->failValidationErrors('Dados inválidos');
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador===0||$idEmpresa===0) && function_exists('resolve_tenant_ids')) { [$idContador,$idEmpresa] = resolve_tenant_ids(); }

        $prodModel = new \App\Models\ProdutoModel();
        $prod = $prodModel->find($id);
        if (! $prod) return $this->failNotFound('Produto não encontrado');
        $movModel = new \App\Models\InventoryMovementModel();
        $db = \Config\Database::connect();
        $db->transStart();
        // registra movimento
        $movModel->insert([
            'id_produto'  => $id,
            'tipo'        => $tipo === 'saida' ? 'saida' : 'entrada',
            'quantidade'  => $qtd,
            'motivo'      => $motivo ?: 'Ajuste manual',
            'id_contador' => $idContador,
            'id_empresa'  => $idEmpresa,
        ]);
        // aplica ajuste
        if ($tipo === 'saida') {
            $prodModel->baixarEstoque([[ 'id_produto' => $id, 'quantidade' => $qtd ]]);
        } else {
            $prodModel->estornarEstoque([[ 'id_produto' => $id, 'quantidade' => $qtd ]]);
        }
        $db->transComplete();
        if ($db->transStatus() === false) return $this->failServerError('Falha ao ajustar estoque');
        return $this->respond(['ok' => true]);
    }
}


