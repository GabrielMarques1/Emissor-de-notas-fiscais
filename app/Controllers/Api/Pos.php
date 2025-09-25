<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ShiftModel;
use App\Models\EmpresaModel;
use App\Models\ProdutoProvisorioModel;
use App\Models\NFCeModel;
use App\Models\PosSaleModel;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

class Pos extends ResourceController
{
    protected $format = 'json';

    /**
     * Usaremos PosSale como recurso principal da API de PDV por enquanto.
     */
    protected $modelName = 'App\\Models\\PosSaleModel';

    public function index()
    {
        try {
            $q            = trim((string) ($this->request->getGet('q') ?? ''));
            $status       = trim((string) ($this->request->getGet('status') ?? ''));
            $payment      = trim((string) ($this->request->getGet('payment_type') ?? ''));
            $period       = trim((string) ($this->request->getGet('period') ?? ''));
            $de           = trim((string) ($this->request->getGet('de') ?? ''));
            $ate          = trim((string) ($this->request->getGet('ate') ?? ''));
            $page         = max(1, (int) ($this->request->getGet('page') ?? 1));
            $perPage      = (int) ($this->request->getGet('per_page') ?? 20);
            $perPage      = $perPage > 0 && $perPage <= 200 ? $perPage : 20;

            // Calcula intervalo por atalhos de período
            if ($period !== '') {
                $today = date('Y-m-d');
                if ($period === 'today') { $de = $today; $ate = $today; }
                elseif ($period === 'yesterday') { $y = date('Y-m-d', strtotime('-1 day')); $de = $y; $ate = $y; }
                elseif ($period === 'last7') { $de = date('Y-m-d', strtotime('-6 days')); $ate = $today; }
                elseif ($period === 'month') { $de = date('Y-m-01'); $ate = date('Y-m-t'); }
            }

            // CORREÇÃO CRÍTICA: Aplicar filtragem multi-tenant
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }

            $builder = $this->model->builder();
            
            // SEGURANÇA MULTI-TENANT: Filtrar por empresa/contador
            if ($idContador) { $builder->where('pos_sales.id_contador', $idContador); }
            if ($idEmpresa)  { $builder->where('pos_sales.id_empresa',  $idEmpresa); }
            
            // Join com clientes para expor nome
            try { $builder->select('pos_sales.*, clientes.nome as cliente_nome')->join('clientes', 'clientes.id_cliente = pos_sales.id_cliente', 'left'); } catch (\Throwable $e) { /* ignora join se não existir */ }

            if ($status !== '') { $builder->where('pos_sales.status', $status); }
            if ($payment !== '') { $builder->where('pos_sales.payment_type', $payment); }
            if ($de !== '') { $builder->where('pos_sales.created_at >=', $de . ' 00:00:00'); }
            if ($ate !== '') { $builder->where('pos_sales.created_at <=', $ate . ' 23:59:59'); }

            if ($q !== '') {
                // Tentar buscar também por nome do item
                try { $builder->join('pos_sale_items psi', 'psi.id_pos_sale = pos_sales.id_pos_sale', 'left'); } catch (\Throwable $e) {}
                $builder->groupStart()
                        ->like('pos_sales.sale_number', $q)
                        ->orLike('pos_sales.id_pos_sale', $q)
                        ->orLike('pos_sales.notes', $q)
                        ->orLike('clientes.nome', $q)
                        ->orLike('psi.nome', $q)
                        ->groupEnd();
            }

            // Evitar duplicação por join em itens
            try { $builder->groupBy('pos_sales.id_pos_sale'); } catch (\Throwable $e) {}

            // Conta total para paginação
            $countBuilder = clone $builder;
            $total = (int) $countBuilder->countAllResults();

            $offset = ($page - 1) * $perPage;
            $rows = $builder->orderBy('pos_sales.id_pos_sale', 'DESC')->get($perPage, $offset)->getResultArray();

            return $this->respond([
                'data' => $rows ?: [],
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'has_next' => ($offset + $perPage) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            // Em modo inicial (sem migrations), não falhar: retorna lista vazia
            return $this->respond(['data' => [], 'meta' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'has_next' => false]]);
        }
    }

    public function show($id = null)
    {
        // CORREÇÃO CRÍTICA: Usar BaseAppModel que já aplica filtragem multi-tenant
        $data = $this->model->find($id);
        if (!$data) {
            return $this->failNotFound('Recurso não encontrado');
        }
        return $this->respond($data);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        if (!$payload) {
            return $this->failValidationErrors('Payload vazio');
        }
        // validações básicas
        $required = ['sale_number', 'id_cash_register', 'id_shift'];
        foreach ($required as $f) {
            if (!isset($payload[$f]) || $payload[$f] === '') {
                return $this->failValidationErrors("Campo obrigatório: {$f}");
            }
        }
        // IDs de empresa/contador da sessão, se existirem
        $session = session();
        if (!isset($payload['id_empresa']) && $session->get('id_empresa')) {
            $payload['id_empresa'] = (int) $session->get('id_empresa');
        }
        if (!isset($payload['id_contador']) && $session->get('id_contador')) {
            $payload['id_contador'] = (int) $session->get('id_contador');
        }
        if (!$this->model->insert($payload)) {
            return $this->failValidationErrors($this->model->errors());
        }
        $id = $this->model->getInsertID();
        $created = $this->model->find($id);
        return $this->respondCreated($created);
    }

    public function update($id = null)
    {
        if ($id === null) {
            return $this->failValidationErrors('ID é obrigatório');
        }
        $payload = [];
        if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest) {
            $json = $this->request->getJSON(true);
            $payload = $json ?? ($this->request->getRawInput() ?? []);
        }
        if (!$payload) {
            return $this->failValidationErrors('Payload vazio');
        }
        if (!$this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }
        $updated = $this->model->find($id);
        return $this->respond($updated);
    }

    public function delete($id = null)
    {
        if ($id === null) {
            return $this->failValidationErrors('ID é obrigatório');
        }
        $existing = $this->model->find($id);
        if (!$existing) {
            return $this->failNotFound('Recurso não encontrado');
        }
        $this->model->delete($id);
        return $this->respondDeleted(['id' => $id]);
    }

    // Finaliza venda; pode emitir NFC-e (emit_nfce=true) ou apenas registrar sem emissão
    public function finalize($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID é obrigatório');
        $payload = [];
        if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest) {
            $json = $this->request->getJSON(true);
            $payload = $json ?? ($this->request->getRawInput() ?? []);
        } else {
            // CLIRequest não possui getJSON; segue vazio
            $payload = [];
        }
        $data = [ 'status' => 'finalized' ];
        if (isset($payload['total'])) $data['total'] = (float) $payload['total'];
        if (isset($payload['discount'])) $data['discount'] = (float) $payload['discount'];
        if (isset($payload['paid_amount'])) $data['paid_amount'] = (float) $payload['paid_amount'];
        if (isset($payload['change_amount'])) $data['change_amount'] = (float) $payload['change_amount'];
        if (isset($payload['payment_type'])) $data['payment_type'] = (string) $payload['payment_type'];

        // Valida turno aberto
        $sale = $this->model->find($id);
        if (! $sale) {
            return $this->failNotFound('Venda não encontrada');
        }
        $shiftModel = new ShiftModel();
        $saleShiftId = is_array($sale) ? (int) ($sale['id_shift'] ?? 0) : (int) ($sale->id_shift ?? 0);
        $shift = $shiftModel->find($saleShiftId);
        $shiftStatus = is_array($shift) ? (string) ($shift['status'] ?? '') : (string) ($shift->status ?? '');
        if (! $shift || strtolower($shiftStatus) !== 'open') {
            return $this->fail('O turno para esta venda não está aberto.', 409);
        }

        // Descobre sessão de caixa aberta para este tenant e vincula a venda
        $db = \Config\Database::connect();
        $db->transStart();
        $session = session();
        $idContadorSess = (int) ($session->get('id_contador') ?? 0);
        $idEmpresaSess  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContadorSess === 0 || $idEmpresaSess === 0) && function_exists('resolve_tenant_ids')) {
            [$idContadorSess,$idEmpresaSess] = resolve_tenant_ids();
        }
        $caixaRow = $db->query(
            "SELECT id FROM caixa_sessoes WHERE status='aberto' AND id_contador=? AND id_empresa=? ORDER BY id DESC LIMIT 1",
            [$idContadorSess ?: 0, $idEmpresaSess ?: 0]
        )->getFirstRow('array');
        if ($caixaRow && !isset($data['id_caixa_sessao'])) {
            $data['id_caixa_sessao'] = (int) $caixaRow['id'];
        }
        if (!$this->model->update($id, $data)) {
            $db->transRollback();
            return $this->failValidationErrors($this->model->errors());
        }

        // Decide se deve emitir NFC-e DEPOIS do commit
        $shouldEmitNfce = (bool) ($payload['emit_nfce'] ?? false);

        try {
            // Helpers necessários (format, removeMascaras, etc.)
            if (function_exists('helper')) { helper('app'); }
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);

            // Fallback para ambiente CLI/sem sessão: usa dados da venda
            if ($idContador === 0) {
                $idContador = (int) (is_array($sale) ? ($sale['id_contador'] ?? 0) : ($sale->id_contador ?? 0));
            }
            if ($idEmpresa === 0) {
                $idEmpresa = (int) (is_array($sale) ? ($sale['id_empresa'] ?? 0) : ($sale->id_empresa ?? 0));
            }

            $empresaModel = new EmpresaModel();
            $dados_do_emitente = $empresaModel
                ->where('empresas.id_contador', $idContador)
                ->where('empresas.id_empresa', $idEmpresa)
                ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                ->first();

            // Não falhar a finalização caso dados do emitente estejam ausentes; emission será tentada após commit

            $produtoProvisorioModel = new ProdutoProvisorioModel();
            $produtos_da_nota = $produtoProvisorioModel
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->findAll();
            if (empty($produtos_da_nota)) {
                return $this->failValidationErrors('Não há itens no carrinho.');
            }

            // Monta dados da nota a partir da venda
            $updatedSale = $this->model->find($id);
            $total = (float) (is_array($updatedSale) ? ($updatedSale['total'] ?? 0) : ($updatedSale->total ?? 0));
            if ($total <= 0) {
                $total = 0.0;
                foreach ($produtos_da_nota as $p) {
                    $total += ((float) $p['valor_unitario'] * (float) $p['quantidade']) - (float) ($p['desconto'] ?? 0);
                }
            }
            $dados_da_nota = [
                'valor_da_nota' => $total,
                'data' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'tipo_de_pagamento' => $this->mapTPag(is_array($updatedSale) ? ($updatedSale['payment_type'] ?? 'cash') : ($updatedSale->payment_type ?? 'cash')),
                'forma_de_pagamento' => 0,
                'troco' => (float) (is_array($updatedSale) ? ($updatedSale['change_amount'] ?? 0) : ($updatedSale->change_amount ?? 0)),
            ];
            // Emissão de NFC-e será tentada APÓS commit para não impactar a finalização
            $id_nfce = null; $nfce = null; $dados_emitente_para_nfce = $dados_do_emitente; $itens_para_nfce = $produtos_da_nota; $dados_da_nota_para_nfce = $dados_da_nota;

            // Serviço: baixa estoque + grava itens + movimentos
            $idPos = (is_array($updatedSale) ? ($updatedSale['id_pos_sale'] ?? $id) : ($updatedSale->id_pos_sale ?? $id));
            (new \App\Libraries\EstoqueService())->darBaixaPorVenda($produtos_da_nota, (int) $idPos, $idContador, $idEmpresa);
            // Limpa carrinho provisório
            $produtoProvisorioModel->where('id_empresa', $idEmpresa)->delete();

            // Atualiza venda com referência
            $updateSale = [];
            // id_nfce será definido após commit (se emissão ocorrer)
            if (!empty($updateSale)) { $this->model->update($id, $updateSale); }

            $db->transComplete();
            $final = $this->model->find($id);

            // Tenta emitir NFC-e (opcional), sem afetar a venda já salva (fora da transação)
            if ($shouldEmitNfce) {
                try {
                    $empresaModel = new EmpresaModel();
                    $emit = $dados_emitente_para_nfce ?: $empresaModel->find($idEmpresa);
                    // Checagem mínima
                    if ($emit && !empty($itens_para_nfce)) {
                        $simulate = (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') || (bool) getenv('PDV_SIMULATE_NFCE');
                        if ($simulate) {
                            $chave = 'SIM' . date('YmdHis');
                            $xml_protocolado = '<xml>simulado</xml>';
                            $protocolo = '<protSimulado/>';
                            $nfce = new class($chave) { private $c; public function __construct($c){$this->c=$c;} public function getChave(){return $this->c;} };
                        } else {
                            $nfceController = new \App\Controllers\NFCe();
                            $nfce = $nfceController->montaXml($emit, $dados_da_nota_para_nfce, $itens_para_nfce);
                            $xml = $nfce->getXML();
                            $config_json = $nfceController->preparaConfigJson($emit);
                            $xml_assinado = $nfceController->assinaXML($emit, $config_json, $xml);
                            $protocolo = $nfceController->enviaLoteParaSefaz($xml_assinado);
                            $xml_protocolado = $nfceController->protocolaXmlNaSefaz($xml_assinado, $protocolo);
                        }

                        // Atualiza número de NFC-e
                        if (!empty($emit['tpAmb_NFCe'])) {
                            $campo_update = ((int) $emit['tpAmb_NFCe'] == 1) ? 'nNFC_producao' : 'nNFC_homologacao';
                            $guarda_numero_da_nota = (int) ($emit[$campo_update] ?? 0);
                            $nova_nNFC = $guarda_numero_da_nota + 1;
                            $empresaModel->update($idEmpresa, [$campo_update => $nova_nNFC]);
                        } else { $guarda_numero_da_nota = 0; }

                        // Persiste NFC-e
                        $nfceModel = new NFCeModel();
                        $id_nfce = $nfceModel->insert([
                            'chave'         => $nfce->getChave(),
                            'numero'        => $guarda_numero_da_nota,
                            'valor_da_nota' => $dados_da_nota_para_nfce['valor_da_nota'] ?? 0,
                            'data'          => $dados_da_nota_para_nfce['data'] ?? date('Y-m-d'),
                            'hora'          => $dados_da_nota_para_nfce['hora'] ?? date('H:i:s'),
                            'xml'           => $xml_protocolado ?? '',
                            'protocolo'     => $protocolo ?? '',
                            'status'        => 'Emitida',
                            'id_contador'   => $idContador,
                            'id_empresa'    => $idEmpresa,
                        ]);
                        // Vincula na venda
                        $this->model->update($id, [
                            'id_nfce' => $id_nfce,
                            'chave_nfce' => $nfce ? $nfce->getChave() : null,
                        ]);
                        // Outbox: NFC-e emitida
                        $pk = ['id_nfce' => $id_nfce];
                        \App\Libraries\Outbox::record('nfces', $pk, 'insert', $pk);
                        // Atualiza instância final
                        $final = $this->model->find($id);
                    }
                } catch (\Throwable $e) {
                    // Não interrompe; apenas registra nota em sale.notes
                    try { $this->model->update($id, ['notes' => 'NFC-e: falha na emissão']); } catch (\Throwable $e2) {}
                }
            }

            // Lançamento financeiro (recebimento em caixa)
            try {
                $valorLanc = (float) (is_array($final) ? ($final['total'] ?? 0) : ($final->total ?? 0));
                (new \App\Libraries\FinanceiroService())->criarLancamentoPorVenda((int) (is_array($final)?($final['id_pos_sale'] ?? $id):($final->id_pos_sale ?? $id)), $valorLanc, $idContador, $idEmpresa);
            } catch (\Throwable $e) { /* não bloqueia fluxo */ }
            // Outbox: notificar venda finalizada
            \App\Libraries\Outbox::record('pos_sales', ['id_pos_sale' => (is_array($final)?$final['id_pos_sale']:$final->id_pos_sale)], 'update', is_array($final)?$final:(array) $final);
            // Outbox: NFC-e emitida
            if ($id_nfce || (is_array($final) ? ($final['id_nfce'] ?? null) : ($final->id_nfce ?? null))) {
                $pk = ['id_nfce' => (is_array($final)?($final['id_nfce'] ?? null):($final->id_nfce ?? null))];
                \App\Libraries\Outbox::record('nfces', $pk, 'insert', $pk);
            }
            return $this->respond($final);
        } catch (\Throwable $e) {
            if (isset($db)) { try { $db->transRollback(); } catch (\Throwable $e2) {} }
            return $this->failServerError('Falha ao finalizar venda: ' . $e->getMessage());
        }
    }

    

    // Download do XML da NFC-e vinculada à venda
    public function receipt($id = null)
    {
        if ($id === null) {
            return $this->response->setStatusCode(400)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>ID é obrigatório</h3></body></html>');
        }
        $sale = $this->model->find($id);
        if (! $sale) {
            return $this->response->setStatusCode(404)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>Venda não encontrada</h3></body></html>');
        }
        $idNfce = is_array($sale) ? ($sale['id_nfce'] ?? null) : ($sale->id_nfce ?? null);
        if (! $idNfce) {
            return $this->response->setStatusCode(409)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>Venda não possui NFC-e vinculada.</h3></body></html>');
        }

        $nfceModel = new \App\Models\NFCeModel();
        $nfce = $nfceModel->find($idNfce);
        if (! $nfce) {
            return $this->response->setStatusCode(404)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>NFC-e não encontrada</h3></body></html>');
        }

        $nome = ($nfce['chave'] ?? ('nfce-' . $idNfce)) . '.xml';
        // retorna arquivo para download
        return $this->response->download($nome, $nfce['xml'] ?? '');
    }

    // Lista itens da venda (para histórico no PDV)
    public function items($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID é obrigatório');
        $sale = $this->model->find($id);
        if (! $sale) return $this->failNotFound('Venda não encontrada');
        $saleId = (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? $id) : ($sale->id_pos_sale ?? $id));
        $itemModel = new \App\Models\PosSaleItemModel();
        $itens = $itemModel->where('id_pos_sale', $saleId)->orderBy('id_item', 'ASC')->findAll();
        return $this->respond($itens ?: []);
    }

    // Cupom não fiscal (recibo simples) - independente de NFC-e
    public function receiptNonFiscal($id = null)
    {
        if ($id === null) {
            return $this->response->setStatusCode(400)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>ID é obrigatório</h3></body></html>');
        }
        $sale = $this->model->find($id);
        if (! $sale) {
            return $this->response->setStatusCode(404)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>Venda não encontrada</h3></body></html>');
        }
        try {
            $empresa = (new EmpresaModel())->find(is_array($sale)?($sale['id_empresa']??0):($sale->id_empresa??0));
            $saleId = (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? $id) : ($sale->id_pos_sale ?? $id));
            $itemModel = new \App\Models\PosSaleItemModel();
            $itens = $itemModel->where('id_pos_sale', $saleId)->orderBy('id_item', 'ASC')->findAll();

            $linhas = '';
            $subtotal = 0.0;
            foreach ($itens as $it) {
                $q = (float) ($it['quantidade'] ?? 0);
                $vu = (float) ($it['valor_unitario'] ?? 0);
                $desc = (float) ($it['desconto'] ?? 0);
                $st = $q * $vu - $desc;
                $subtotal += $st;
                $linhas .= '<tr>'
                    . '<td>' . htmlspecialchars((string)($it['nome'] ?? '')) . '</td>'
                    . '<td class="text-right">' . number_format($q, 0, ',', '.') . '</td>'
                    . '<td class="text-right">' . number_format($vu, 2, ',', '.') . '</td>'
                    . '<td class="text-right">' . number_format($st, 2, ',', '.') . '</td>'
                    . '</tr>';
            }
            $total = (float) (is_array($sale)?($sale['total']??$subtotal):($sale->total??$subtotal));
            $discount = (float) (is_array($sale)?($sale['discount']??0):($sale->discount??0));
            $paid = (float) (is_array($sale)?($sale['paid_amount']??$total):($sale->paid_amount??$total));
            $troco = (float) (is_array($sale)?($sale['change_amount']??0):($sale->change_amount??0));
            $payment = (string) (is_array($sale)?($sale['payment_type']??'') : ($sale->payment_type??''));
            $createdAt = (string) (is_array($sale)?($sale['created_at']??'') : ($sale->created_at??''));

            $css = 'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;max-width:280px;margin:0 auto;} .center{text-align:center} .q{margin-top:8px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ddd;padding:4px}';
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Recibo (Não Fiscal)</title><style>' . $css . '</style></head><body onload="window.print()">';
            $html .= '<div class="center"><h3>' . htmlspecialchars($empresa['xFant'] ?? 'Loja') . '</h3></div>';
            $html .= '<div><strong>Recibo Não Fiscal</strong></div>';
            $html .= '<div>Venda: #' . htmlspecialchars((string)$saleId) . '</div>';
            $html .= '<div>Data/Hora: ' . htmlspecialchars(str_replace(['T','Z'], [' ',''], $createdAt)) . '</div>';
            $html .= '<div class="q"><table><thead><tr><th>Item</th><th>Qtd</th><th>Vlr Unit</th><th>Subtotal</th></tr></thead><tbody>' . $linhas . '</tbody></table></div>';
            $html .= '<div class="q">Subtotal: R$ ' . number_format($subtotal, 2, ',', '.') . '</div>';
            $html .= '<div>Desconto: R$ ' . number_format($discount, 2, ',', '.') . '</div>';
            $html .= '<div><strong>Total:</strong> R$ ' . number_format($total, 2, ',', '.') . '</div>';
            $html .= '<div>Pago: R$ ' . number_format($paid, 2, ',', '.') . '</div>';
            $html .= '<div>Troco: R$ ' . number_format($troco, 2, ',', '.') . '</div>';
            $html .= '<div>Pagamento: ' . htmlspecialchars($payment) . '</div>';
            $html .= '<div class="q center"><small>ESTE DOCUMENTO NÃO É UM CUPOM FISCAL</small></div>';
            $html .= '</body></html>';
            return $this->response->setHeader('Content-Type', 'text/html; charset=utf-8')->setBody($html);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)
                ->setHeader('Content-Type', 'text/html; charset=utf-8')
                ->setBody('<!DOCTYPE html><html><body><h3>Erro ao gerar recibo</h3></body></html>');
        }
    }

    // Recibo HTML imprimível do DANFE NFC-e
    public function receiptHtml($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID é obrigatório');
        $sale = $this->model->find($id);
        if (! $sale) return $this->failNotFound('Venda não encontrada');
        $idNfce = is_array($sale) ? ($sale['id_nfce'] ?? null) : ($sale->id_nfce ?? null);
        if (! $idNfce) return $this->fail('Venda não possui NFC-e vinculada.', 409);

        $nfceModel = new \App\Models\NFCeModel();
        $nfce = $nfceModel->find($idNfce);
        if (! $nfce) return $this->failNotFound('NFC-e não encontrada');

        $valor = (float) (is_array($sale) ? ($sale['total'] ?? 0) : ($sale->total ?? 0));
        // recuperar itens da venda
        $itemModel = new \App\Models\PosSaleItemModel();
        $saleId = (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id));
        $itens = $itemModel->where('id_pos_sale', $saleId)->findAll();
        $linhas = '';
        foreach ($itens as $it) {
            $subtotal = ((float) $it['valor_unitario'] * (float) $it['quantidade']) - (float) ($it['desconto'] ?? 0);
            $linhas .= '<tr>'
                . '<td>' . htmlspecialchars($it['nome']) . '</td>'
                . '<td class="text-right">' . number_format((float) $it['quantidade'], 0, ',', '.') . '</td>'
                . '<td class="text-right">' . number_format((float) $it['valor_unitario'], 2, ',', '.') . '</td>'
                . '<td class="text-right">' . number_format($subtotal, 2, ',', '.') . '</td>'
                . '</tr>';
        }
        $emitente = (new EmpresaModel())->find(is_array($sale)?($sale['id_empresa']??0):($sale->id_empresa??0));
        $qr = isset($nfce['chave']) ? 'https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx?chNFe=' . urlencode($nfce['chave']) : '';
        // Define layout: 'thermal' (default) or 'a4' (PDF-friendly)
        $layout = (string) ($this->request->getGet('layout') ?? 'thermal');
        $cssThermal = 'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;max-width:280px;margin:0 auto;} .center{text-align:center} .q{margin-top:8px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ddd;padding:4px}';
        $cssA4 = '@page{size:A4 portrait;margin:12mm;} body{font-family:Arial,Helvetica,sans-serif;font-size:12px;margin:0;} .container{max-width:700px;margin:0 auto;} .center{text-align:center} .q{margin-top:8px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ddd;padding:4px}';
        $style = $layout === 'a4' ? $cssA4 : $cssThermal;
        $wrapStart = $layout === 'a4' ? '<div class="container">' : '';
        $wrapEnd = $layout === 'a4' ? '</div>' : '';

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>DANFE NFC-e</title>' .
                '<style>' . $style . '</style>' .
                '</head><body onload="window.print()">' . $wrapStart .
                '<div class="center"><h3>DANFE NFC-e (Recibo)</h3></div>' .
                '<div><strong>' . htmlspecialchars($emitente['xFant'] ?? '') . '</strong><br/>' . htmlspecialchars(($emitente['xLgr'] ?? '') . ', ' . ($emitente['nro'] ?? '') . ' - ' . ($emitente['xBairro'] ?? '')) . '<br/>' . 'CNPJ: ' . htmlspecialchars($emitente['CNPJ'] ?? '') . '</div>' .
                '<div>Chave: ' . htmlspecialchars($nfce['chave']) . '</div>' .
                '<div>Protocolo: ' . htmlspecialchars(strip_tags((string) ($nfce['protocolo'] ?? ''))) . '</div>' .
                '<div>Valor: R$ ' . number_format($valor, 2, ',', '.') . '</div>' .
                '<div class="q"><table><thead><tr><th>Produto</th><th>Qtd</th><th>Vlr Unit</th><th>Subtotal</th></tr></thead><tbody>' . $linhas . '</tbody></table></div>' .
                '<div class="q">XML disponível no sistema.</div>' .
                ($qr ? ('<div class="q center"><div id="qrcode"></div><div style="word-break:break-all;margin-top:4px">' . htmlspecialchars($qr) . '</div></div>') : '') .
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>' .
                ($qr ? ('<script>try{ new QRCode(document.getElementById("qrcode"), { text: ' . json_encode($qr) . ', width: 128, height: 128 }); }catch(e){}</script>') : '') .
                $wrapEnd . '</body></html>';
        return $this->response->setHeader('Content-Type', 'text/html; charset=utf-8')->setBody($html);
    }

    private function mapTPag(string $paymentType): string
    {
        $map = [
            'cash' => '01',
            'credit' => '03',
            'debit' => '04',
            'pix' => '17',
        ];
        return $map[strtolower($paymentType)] ?? '99';
    }

    // Obtém ou cria uma venda ativa (status draft) no turno aberto
    public function active()
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        $shiftModel = new ShiftModel();
        $openShift = $shiftModel->where('id_contador', $idContador)
                                ->where('id_empresa', $idEmpresa)
                                ->where('status', 'open')
                                ->orderBy('id_shift', 'DESC')->first();
        if (! $openShift) {
            return $this->fail('Não há turno aberto.', 409);
        }
        // Normaliza acesso (entity ou array)
        $shiftId = is_array($openShift) ? (int) ($openShift['id_shift'] ?? 0) : (int) ($openShift->id_shift ?? 0);
        $cashRegId = is_array($openShift) ? (int) ($openShift['id_cash_register'] ?? 0) : (int) ($openShift->id_cash_register ?? 0);

        $saleModel = new PosSaleModel();
        $active = $saleModel->where('id_shift', $shiftId)
                            ->where('status', 'draft')
                            ->orderBy('id_pos_sale', 'DESC')->first();
        if (! $active) {
            $saleId = $saleModel->insert([
                'id_shift' => $shiftId,
                'id_cash_register' => $cashRegId,
                'sale_number' => 'PDV-' . time(),
                'total' => 0,
                'discount' => 0,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_type' => 'cash',
                'status' => 'draft',
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
            ]);
            $active = $saleModel->find($saleId);
        }
        return $this->respond($active);
    }

    // KPIs e métricas para o dashboard de relatórios
    public function stats()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $de = (string) ($this->request->getGet('de') ?? date('Y-m-d'));
            $ate = (string) ($this->request->getGet('ate') ?? date('Y-m-d'));

            $db = \Config\Database::connect();
            // Faturamento total e número de vendas finalizadas
            $builder = $db->table('pos_sales')
                         ->select('COUNT(*) as num_vendas, COALESCE(SUM(total),0) as faturamento')
                         ->where('status', 'finalized')
                         ->where('created_at >=', $de . ' 00:00:00')
                         ->where('created_at <=', $ate . ' 23:59:59');
            if ($idContador) $builder->where('id_contador', $idContador);
            if ($idEmpresa)  $builder->where('id_empresa',  $idEmpresa);
            $row = $builder->get()->getRowArray() ?: ['num_vendas'=>0,'faturamento'=>0];

            $numVendas = (int) ($row['num_vendas'] ?? 0);
            $faturamento = (float) ($row['faturamento'] ?? 0);
            $ticketMedio = $numVendas > 0 ? ($faturamento / $numVendas) : 0.0;

            // Top produtos do período
            $it = $db->table('pos_sale_items as i')
                    ->select('i.nome, SUM(i.quantidade) as qtd, SUM((i.valor_unitario*i.quantidade) - COALESCE(i.desconto,0)) as total')
                    ->join('pos_sales as s', 's.id_pos_sale = i.id_pos_sale')
                    ->where('s.status', 'finalized')
                    ->where('s.created_at >=', $de . ' 00:00:00')
                    ->where('s.created_at <=', $ate . ' 23:59:59')
                    ->groupBy('i.nome')
                    ->orderBy('qtd', 'DESC')
                    ->limit(10);
            if ($idContador) $it->where('s.id_contador', $idContador);
            if ($idEmpresa)  $it->where('s.id_empresa',  $idEmpresa);
            $top = $it->get()->getResultArray();

            return $this->respond([
                'periodo' => ['de' => $de, 'ate' => $ate],
                'faturamento' => $faturamento,
                'num_vendas' => $numVendas,
                'ticket_medio' => $ticketMedio,
                'top_produtos' => $top,
            ]);
        } catch (\Throwable $e) {
            return $this->respond([
                'periodo' => ['de' => date('Y-m-d'), 'ate' => date('Y-m-d')],
                'faturamento' => 0,
                'num_vendas' => 0,
                'ticket_medio' => 0,
                'top_produtos' => [],
            ]);
        }
    }

    // Relatório: vendas (totais, descontos, série temporal)
    public function reportSales()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $de = (string) ($this->request->getGet('de') ?? date('Y-m-01'));
            $ate = (string) ($this->request->getGet('ate') ?? date('Y-m-d'));
            $db = \Config\Database::connect();

            $base = $db->table('pos_sales')->where('status', 'finalized')
                     ->where('created_at >=', $de . ' 00:00:00')
                     ->where('created_at <=', $ate . ' 23:59:59');
            if ($idContador) $base->where('id_contador', $idContador);
            if ($idEmpresa)  $base->where('id_empresa',  $idEmpresa);

            $totais = (clone $base)->select('COALESCE(SUM(total),0) as total, COALESCE(SUM(discount),0) as descontos, COUNT(*) as num')->get()->getRowArray();
            $serie = (clone $base)->select("DATE(created_at) as dia, COALESCE(SUM(total),0) as total, COUNT(*) as num")
                                  ->groupBy('DATE(created_at)')->orderBy('dia','ASC')->get()->getResultArray();
            $res = [
                'periodo' => ['de'=>$de,'ate'=>$ate],
                'total' => (float) ($totais['total'] ?? 0),
                'descontos' => (float) ($totais['descontos'] ?? 0),
                'num_vendas' => (int) ($totais['num'] ?? 0),
                'taxas' => 0.0, // sem coluna específica de taxas no schema atual
                'lucro' => null, // depende de custo do produto (não presente no schema atual)
                'serie' => $serie,
            ];
            return $this->respond($res);
        } catch (\Throwable $e) {
            return $this->respond(['periodo'=>['de'=>date('Y-m-01'),'ate'=>date('Y-m-d')],'total'=>0,'descontos'=>0,'num_vendas'=>0,'taxas'=>0,'lucro'=>null,'serie'=>[]]);
        }
    }

    // Relatório: produtos (top por quantidade e por valor) + busca
    public function reportProducts()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $de = (string) ($this->request->getGet('de') ?? date('Y-m-01'));
            $ate = (string) ($this->request->getGet('ate') ?? date('Y-m-d'));
            $q  = trim((string) ($this->request->getGet('q') ?? ''));
            $db = \Config\Database::connect();

            $builder = $db->table('pos_sale_items as i')
                ->join('pos_sales as s', 's.id_pos_sale = i.id_pos_sale')
                ->where('s.status','finalized')
                ->where('s.created_at >=', $de . ' 00:00:00')
                ->where('s.created_at <=', $ate . ' 23:59:59');
            if ($idContador) $builder->where('s.id_contador', $idContador);
            if ($idEmpresa)  $builder->where('s.id_empresa',  $idEmpresa);
            if ($q !== '') {
                if (ctype_digit($q)) $builder->groupStart()->where('i.id_produto', (int) $q)->groupEnd();
                else $builder->groupStart()->like('i.nome', $q)->groupEnd();
            }

            $topQty = (clone $builder)->select('i.nome, SUM(i.quantidade) as qtd')
                                      ->groupBy('i.nome')->orderBy('qtd','DESC')->limit(20)->get()->getResultArray();
            $topVal = (clone $builder)->select('i.nome, SUM((i.valor_unitario*i.quantidade) - COALESCE(i.desconto,0)) as total')
                                      ->groupBy('i.nome')->orderBy('total','DESC')->limit(20)->get()->getResultArray();
            return $this->respond(['periodo'=>['de'=>$de,'ate'=>$ate],'top_quantidade'=>$topQty,'top_valor'=>$topVal]);
        } catch (\Throwable $e) {
            return $this->respond(['periodo'=>['de'=>date('Y-m-01'),'ate'=>date('Y-m-d')],'top_quantidade'=>[],'top_valor'=>[]]);
        }
    }

    // Relatório: pagamentos (resumo por forma)
    public function reportPayments()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $de = (string) ($this->request->getGet('de') ?? date('Y-m-d'));
            $ate = (string) ($this->request->getGet('ate') ?? date('Y-m-d'));
            $db = \Config\Database::connect();
            $b = $db->table('pos_sales')->select('payment_type, COUNT(*) as qtd, COALESCE(SUM(total),0) as valor')
                ->where('status','finalized')
                ->where('created_at >=', $de . ' 00:00:00')
                ->where('created_at <=', $ate . ' 23:59:59')
                ->groupBy('payment_type');
            if ($idContador) $b->where('id_contador', $idContador);
            if ($idEmpresa)  $b->where('id_empresa',  $idEmpresa);
            $rows = $b->get()->getResultArray();
            $total = 0.0; foreach ($rows as $r) { $total += (float) ($r['valor'] ?? 0); }
            return $this->respond(['periodo'=>['de'=>$de,'ate'=>$ate],'resumo'=>$rows,'total'=>$total]);
        } catch (\Throwable $e) { return $this->respond(['periodo'=>['de'=>date('Y-m-d'),'ate'=>date('Y-m-d')],'resumo'=>[],'total'=>0]); }
    }

    // Relatório: categorias (fallback para CFOP_NFCe se não houver categoria)
    public function reportCategories()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $de = (string) ($this->request->getGet('de') ?? date('Y-m-01'));
            $ate = (string) ($this->request->getGet('ate') ?? date('Y-m-d'));
            $db = \Config\Database::connect();
            $b = $db->table('pos_sale_items as i')
                ->select('COALESCE(p.categoria, i.CFOP_NFCe) as categoria, SUM(i.quantidade) as qtd, SUM((i.valor_unitario*i.quantidade) - COALESCE(i.desconto,0)) as total')
                ->join('pos_sales as s', 's.id_pos_sale = i.id_pos_sale')
                ->join('produtos as p', 'p.id_produto = i.id_produto', 'left')
                ->where('s.status','finalized')
                ->where('s.created_at >=', $de . ' 00:00:00')
                ->where('s.created_at <=', $ate . ' 23:59:59')
                ->groupBy('COALESCE(p.categoria, i.CFOP_NFCe)')
                ->orderBy('total', 'DESC');
            if ($idContador) $b->where('s.id_contador', $idContador);
            if ($idEmpresa)  $b->where('s.id_empresa',  $idEmpresa);
            $rows = $b->get()->getResultArray();
            return $this->respond(['periodo'=>['de'=>$de,'ate'=>$ate],'categorias'=>$rows]);
        } catch (\Throwable $e) { return $this->respond(['periodo'=>['de'=>date('Y-m-01'),'ate'=>date('Y-m-d')],'categorias'=>[]]); }
    }

    // Cancelamento fiscal integrado à SEFAZ (se houver NFC-e)
    public function cancel($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID é obrigatório');
        $sale = $this->model->find($id);
        if (! $sale) return $this->failNotFound('Venda não encontrada');
        $idNfce = is_array($sale) ? ($sale['id_nfce'] ?? null) : ($sale->id_nfce ?? null);
        if (! $idNfce) {
            // Sem NFC-e vinculada: marca cancelada e estorna estoque/financeiro
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? (is_array($sale) ? ($sale['id_contador'] ?? 0) : ($sale->id_contador ?? 0)));
            $idEmpresa  = (int) ($session->get('id_empresa') ?? (is_array($sale) ? ($sale['id_empresa'] ?? 0) : ($sale->id_empresa ?? 0)));
            $db = \Config\Database::connect();
            $db->transStart();
            if (! $this->model->update($id, ['status' => 'cancelled'])) {
                $db->transRollback();
                return $this->failValidationErrors($this->model->errors());
            }
            try {
                // Estorno financeiro simples (lança valor negativo)
                $valor = (float) (is_array($sale) ? ($sale['total'] ?? 0) : ($sale->total ?? 0));
                if ($valor > 0) {
                    $pag = new \App\Models\PagamentoModel();
                    $pag->insert([
                        'data_do_pagamento' => date('Y-m-d'),
                        'valor' => -$valor,
                        'observacoes' => 'Estorno PDV cancel #' . (is_array($sale)?($sale['id_pos_sale'] ?? $id):($sale->id_pos_sale ?? $id)),
                        'id_contador' => $idContador,
                        'id_empresa'  => $idEmpresa,
                    ]);
                }
            } catch (\Throwable $e) { /* não bloqueia */ }
            // Estorna estoque via itens
            try {
                $itemModel = new \App\Models\PosSaleItemModel();
                $produtoModel = new \App\Models\ProdutoModel();
                $movModel = new \App\Models\InventoryMovementModel();
                $itens = $itemModel->where('id_pos_sale', (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)))->findAll();
                $estorno = [];
                foreach ($itens as $it) {
                    if (isset($it['id_produto'])) {
                        $estorno[] = ['id_produto' => (int) $it['id_produto'], 'quantidade' => (float) ($it['quantidade'] ?? 0)];
                        $movModel->insert([
                            'id_produto'  => (int) $it['id_produto'],
                            'tipo'        => 'entrada',
                            'quantidade'  => (float) ($it['quantidade'] ?? 0),
                            'motivo'      => 'PDV cancelamento',
                            'id_pos_sale' => (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)),
                            'id_contador' => $idContador,
                            'id_empresa'  => $idEmpresa,
                        ]);
                    }
                }
                if (!empty($estorno)) { $produtoModel->estornarEstoque($estorno); }
            } catch (\Throwable $e) {
                $db->transRollback();
                return $this->failServerError('Falha ao estornar estoque: ' . $e->getMessage());
            }
            $db->transComplete();
            $final = $this->model->find($id);
            \App\Libraries\Outbox::record('pos_sales', ['id_pos_sale' => (is_array($final)?$final['id_pos_sale']:$final->id_pos_sale)], 'update', is_array($final)?$final:(array) $final);
            return $this->respond($final);
        }

        $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
        $just = (string) ($payload['justificativa'] ?? 'Cancelamento via PDV');

        // Carrega emitente
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? (is_array($sale) ? ($sale['id_contador'] ?? 0) : ($sale->id_contador ?? 0)));
        $idEmpresa  = (int) ($session->get('id_empresa') ?? (is_array($sale) ? ($sale['id_empresa'] ?? 0) : ($sale->id_empresa ?? 0)));
        $empresaModel = new EmpresaModel();
        $emit = $empresaModel->where('id_contador', $idContador)->where('id_empresa', $idEmpresa)
                             ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                             ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                             ->first();
        if (! $emit) return $this->fail('Emitente não encontrado', 404);

        $nfceModel = new NFCeModel();
        $nfce = $nfceModel->find($idNfce);
        if (! $nfce) return $this->failNotFound('NFC-e não encontrada');

        // Autoloader NFePHP (mesmo padrão do controller NFCe)
        $autoloader = FCPATH . '../app/ThirdParty/sped-nfe/vendor/autoload.php';
        $autoloader = realpath($autoloader);
        if ($autoloader && file_exists($autoloader)) require_once $autoloader;

        // Detecta simulação
        $simulate = (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') || (bool) getenv('PDV_SIMULATE_NFCE');

        // Config e certificado
        $nfceController = new \App\Controllers\NFCe();
        $configJson = $nfceController->preparaConfigJson($emit);
        $arq_certificado = WRITEPATH . 'uploads/certificados/' . ($emit['certificado'] ?? '');
        $certificado_digital = ($arq_certificado && file_exists($arq_certificado)) ? file_get_contents($arq_certificado) : '';
        if ($simulate || $certificado_digital === '') {
            // Cancelamento local (simulado): marca venda cancelada e estorna estoque/financeiro
            $this->model->update($id, ['status' => 'cancelled']);
            // Estornar financeiro simples (lança valor negativo)
            try {
                $valor = (float) (is_array($sale) ? ($sale['total'] ?? 0) : ($sale->total ?? 0));
                if ($valor > 0) {
                    $pag = new \App\Models\PagamentoModel();
                    $pag->insert([
                        'data_do_pagamento' => date('Y-m-d'),
                        'valor' => -$valor,
                        'observacoes' => 'Estorno PDV cancel (simulado) #' . (is_array($sale)?($sale['id_pos_sale'] ?? $id):($sale->id_pos_sale ?? $id)),
                        'id_contador' => $idContador,
                        'id_empresa'  => $idEmpresa,
                    ]);
                }
            } catch (\Throwable $e) {}
            // Estorna estoque via itens
            $itemModel = new \App\Models\PosSaleItemModel();
            $produtoModel = new \App\Models\ProdutoModel();
            $movModel = new \App\Models\InventoryMovementModel();
            $itens = $itemModel->where('id_pos_sale', (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)))->findAll();
            $estorno = [];
            foreach ($itens as $it) {
                if (isset($it['id_produto'])) {
                    $estorno[] = ['id_produto' => (int) $it['id_produto'], 'quantidade' => (float) ($it['quantidade'] ?? 0)];
                    $movModel->insert([
                        'id_produto'  => (int) $it['id_produto'],
                        'tipo'        => 'entrada',
                        'quantidade'  => (float) ($it['quantidade'] ?? 0),
                        'motivo'      => 'PDV cancelamento (simulado)',
                        'id_pos_sale' => (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)),
                        'id_contador' => $idContador,
                        'id_empresa'  => $idEmpresa,
                    ]);
                }
            }
            if (!empty($estorno)) { $produtoModel->estornarEstoque($estorno); }
            $final = $this->model->find($id);
            \App\Libraries\Outbox::record('pos_sales', ['id_pos_sale' => (is_array($final)?$final['id_pos_sale']:$final->id_pos_sale)], 'update', is_array($final)?$final:(array) $final);
            return $this->respond($final);
        }

        $certificate = Certificate::readPfx($certificado_digital, $emit['senha_do_certificado']);
        $tools = new Tools($configJson, $certificate);
        $tools->model('65');

        // Número do protocolo a partir do XML salvo
        $protXml = (string) ($nfce['protocolo'] ?? '');
        $numProt = null;
        if (preg_match('/<nProt>(\d+)<\/nProt>/', $protXml, $m)) { $numProt = $m[1]; }
        if (! $numProt) return $this->fail('Protocolo não encontrado para cancelamento.', 500);

        try {
            $response = $tools->sefazCancela($nfce['chave'], $just, $numProt);
            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();
            if ($std->cStat == 128 && in_array($std->retEvento->infEvento->cStat, ['101','135','155'])) {
                $xml = Complements::toAuthorize($tools->lastRequest, $response);
                $nfceModel->update($idNfce, ['xml' => $xml, 'status' => 'Cancelada']);
                $this->model->update($id, ['status' => 'cancelled']);
                // Estorno financeiro simples (lança valor negativo)
                try {
                    $valor = (float) (is_array($sale) ? ($sale['total'] ?? 0) : ($sale->total ?? 0));
                    if ($valor > 0) {
                        $pag = new \App\Models\PagamentoModel();
                        $pag->insert([
                            'data_do_pagamento' => date('Y-m-d'),
                            'valor' => -$valor,
                            'observacoes' => 'Estorno PDV cancel #' . (is_array($sale)?($sale['id_pos_sale'] ?? $id):($sale->id_pos_sale ?? $id)),
                            'id_contador' => $idContador,
                            'id_empresa'  => $idEmpresa,
                        ]);
                    }
                } catch (\Throwable $e) { }
                // Estornar estoque a partir dos itens da venda
                $itemModel = new \App\Models\PosSaleItemModel();
                $produtoModel = new \App\Models\ProdutoModel();
                $movModel = new \App\Models\InventoryMovementModel();
                $itens = $itemModel->where('id_pos_sale', (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)))->findAll();
                $estorno = [];
                foreach ($itens as $it) {
                    // precisa mapear id_produto se disponível; se não, pula
                    if (isset($it['id_produto'])) { $estorno[] = ['id_produto' => (int) $it['id_produto'], 'quantidade' => (float) ($it['quantidade'] ?? 0)]; }
                    if (isset($it['id_produto'])) {
                        $movModel->insert([
                            'id_produto'  => (int) $it['id_produto'],
                            'tipo'        => 'entrada',
                            'quantidade'  => (float) ($it['quantidade'] ?? 0),
                            'motivo'      => 'PDV cancelamento',
                            'id_pos_sale' => (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)),
                            'id_contador' => $idContador,
                            'id_empresa'  => $idEmpresa,
                        ]);
                    }
                }
                if (!empty($estorno)) { $produtoModel->estornarEstoque($estorno); }
                $final = $this->model->find($id);
                \App\Libraries\Outbox::record('pos_sales', ['id_pos_sale' => (is_array($final)?$final['id_pos_sale']:$final->id_pos_sale)], 'update', is_array($final)?$final:(array) $final);
                // Outbox: NFC-e cancelada
                if ($idNfce) {
                    \App\Libraries\Outbox::record('nfces', ['id_nfce' => $idNfce], 'update', ['status' => 'Cancelada']);
                }
                return $this->respond($final);
            }
            $motivo = $std->retEvento->infEvento->xMotivo ?? $std->xMotivo ?? 'Motivo não especificado';
            return $this->fail('Falha ao cancelar: ' . $motivo, 500);
        } catch (\Throwable $e) {
            return $this->fail('Erro no cancelamento: ' . $e->getMessage(), 500);
        }
    }
}


