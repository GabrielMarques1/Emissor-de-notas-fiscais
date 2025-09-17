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
            return $this->respond($this->model->findAll());
        } catch (\Throwable $e) {
            // Em modo inicial (sem migrations), não falhar: retorna lista vazia
            return $this->respond([]);
        }
    }

    public function show($id = null)
    {
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

    // Finaliza venda e emite NFC-e automaticamente se houver turno aberto
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

        // Atualiza venda antes da emissão (inicia transação)
        $db = \Config\Database::connect();
        $db->transStart();
        if (!$this->model->update($id, $data)) {
            $db->transRollback();
            return $this->failValidationErrors($this->model->errors());
        }

        // Emissão automática de NFC-e reutilizando fluxo existente
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

            if (! $dados_do_emitente) {
                // Fallback sem joins para devolver erro mais claro
                $simple = $empresaModel->where('id_contador', $idContador)->where('id_empresa', $idEmpresa)->first();
                if (! $simple) {
                    return $this->fail('Empresa não encontrada para emissão.');
                }
                // Verificação de campos mínimos
                $required = ['CNPJ','IE','xNome','xLgr','nro','xBairro','id_uf','id_municipio','tpAmb_NFCe','CSC','CSC_Id'];
                $missing = [];
                foreach ($required as $f) {
                    if (empty($simple[$f] ?? null)) { $missing[] = $f; }
                }
                return $this->fail('Dados do emitente incompletos: ' . implode(',', $missing), 422);
            }

            $produtoProvisorioModel = new ProdutoProvisorioModel();
            $produtos_da_nota = $produtoProvisorioModel
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->findAll();
            if (empty($produtos_da_nota)) {
                return $this->failValidationErrors('Não há itens no carrinho para emitir a NFC-e.');
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

            $simulate = (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') || (bool) getenv('PDV_SIMULATE_NFCE');
            if ($simulate) {
                // Simulação para dev/teste: gera dados dummy sem SEFAZ
                $chave = 'SIM' . date('YmdHis');
                $xml_protocolado = '<xml>simulado</xml>';
                $protocolo = '<protSimulado/>';
                $nfce = new class($chave) { private $c; public function __construct($c){$this->c=$c;} public function getChave(){return $this->c;} };
            } else {
                // Usa controlador NFCe para emitir
                $nfceController = new \App\Controllers\NFCe();
                $nfce = $nfceController->montaXml($dados_do_emitente, $dados_da_nota, $produtos_da_nota);
                $xml = $nfce->getXML();
                $config_json = $nfceController->preparaConfigJson($dados_do_emitente);
                $xml_assinado = $nfceController->assinaXML($dados_do_emitente, $config_json, $xml);
                $protocolo = $nfceController->enviaLoteParaSefaz($xml_assinado);
                $xml_protocolado = $nfceController->protocolaXmlNaSefaz($xml_assinado, $protocolo);
            }

            // Atualiza número de NFC-e
            $campo_update = ((int) $dados_do_emitente['tpAmb_NFCe'] == 1) ? 'nNFC_producao' : 'nNFC_homologacao';
            $guarda_numero_da_nota = $dados_do_emitente[$campo_update];
            $nova_nNFC = $guarda_numero_da_nota + 1;
            $empresaModel->update($idEmpresa, [$campo_update => $nova_nNFC]);

            // Persiste NFC-e
            $nfceModel = new NFCeModel();
            $id_nfce = $nfceModel->insert([
                'chave'         => $nfce->getChave(),
                'numero'        => $guarda_numero_da_nota,
                'valor_da_nota' => $dados_da_nota['valor_da_nota'],
                'data'          => $dados_da_nota['data'],
                'hora'          => $dados_da_nota['hora'],
                'xml'           => $xml_protocolado,
                'protocolo'     => $protocolo,
                'status'        => 'Emitida',
                'id_contador'   => $idContador,
                'id_empresa'    => $idEmpresa,
            ]);

            // Grava itens em pos_sale_items e baixa estoque; limpa provisórios
            $itemModel = new \App\Models\PosSaleItemModel();
            $produtoModel = new \App\Models\ProdutoModel();
            $baixar = [];
            foreach ($produtos_da_nota as $p) {
                $itemModel->insert([
                    'id_pos_sale' => (is_array($updatedSale) ? $updatedSale['id_pos_sale'] : ($updatedSale->id_pos_sale ?? $id)),
                    'nome' => $p['nome'],
                    'codigo_de_barras' => $p['codigo_de_barras'] ?? 'SEM GTIN',
                    'unidade' => $p['unidade'] ?? 'UN',
                    'quantidade' => $p['quantidade'] ?? 1,
                    'valor_unitario' => $p['valor_unitario'] ?? 0,
                    'desconto' => $p['desconto'] ?? 0,
                    'CFOP_NFe' => $p['CFOP_NFe'] ?? null,
                    'CFOP_NFCe' => $p['CFOP_NFCe'] ?? null,
                    'CFOP_Externo' => $p['CFOP_Externo'] ?? null,
                    'NCM' => $p['NCM'] ?? null,
                    'CSOSN' => $p['CSOSN'] ?? null,
                ]);
                // baixa estoque se houver id_produto vinculado
                if (isset($p['id_produto'])) {
                    $baixar[] = ['id_produto' => (int) $p['id_produto'], 'quantidade' => (float) ($p['quantidade'] ?? 0)];
                }
            }
            if (!empty($baixar)) { $produtoModel->baixarEstoque($baixar); }
            $produtoProvisorioModel->where('id_empresa', $idEmpresa)->delete();

            // Atualiza venda com referência
            $this->model->update($id, [
                'id_nfce' => $id_nfce,
                'chave_nfce' => $nfce->getChave(),
                'notes' => 'NFC-e emitida e vinculada à venda.'
            ]);

            $db->transComplete();
            $final = $this->model->find($id);
            return $this->respond($final);
        } catch (\Throwable $e) {
            if (isset($db) && $db->transStatus() !== false) {
                $db->transRollback();
            }
            return $this->failServerError('Falha ao emitir NFC-e: ' . $e->getMessage());
        }
    }

    

    // Download do XML da NFC-e vinculada à venda
    public function receipt($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID é obrigatório');
        $sale = $this->model->find($id);
        if (! $sale) return $this->failNotFound('Venda não encontrada');
        $idNfce = is_array($sale) ? ($sale['id_nfce'] ?? null) : ($sale->id_nfce ?? null);
        if (! $idNfce) return $this->fail('Venda não possui NFC-e vinculada.', 409);

        $nfceModel = new \App\Models\NFCeModel();
        $nfce = $nfceModel->find($idNfce);
        if (! $nfce) return $this->failNotFound('NFC-e não encontrada');

        $nome = ($nfce['chave'] ?? ('nfce-' . $idNfce)) . '.xml';
        // retorna arquivo para download
        return $this->response->download($nome, $nfce['xml'] ?? '');
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
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>DANFE NFC-e</title>' .
                '<style>body{font-family:Arial,Helvetica,sans-serif;font-size:12px;} .center{text-align:center} .q{margin-top:8px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ddd;padding:4px}</style>' .
                '</head><body onload="window.print()">' .
                '<div class="center"><h3>DANFE NFC-e (Recibo)</h3></div>' .
                '<div><strong>' . htmlspecialchars($emitente['xFant'] ?? '') . '</strong><br/>' . htmlspecialchars(($emitente['xLgr'] ?? '') . ', ' . ($emitente['nro'] ?? '') . ' - ' . ($emitente['xBairro'] ?? '')) . '<br/>' . 'CNPJ: ' . htmlspecialchars($emitente['CNPJ'] ?? '') . '</div>' .
                '<div>Chave: ' . htmlspecialchars($nfce['chave']) . '</div>' .
                '<div>Protocolo: ' . htmlspecialchars(strip_tags((string) ($nfce['protocolo'] ?? ''))) . '</div>' .
                '<div>Valor: R$ ' . number_format($valor, 2, ',', '.') . '</div>' .
                '<div class="q"><table><thead><tr><th>Produto</th><th>Qtd</th><th>Vlr Unit</th><th>Subtotal</th></tr></thead><tbody>' . $linhas . '</tbody></table></div>' .
                '<div class="q">XML disponível no sistema.</div>' .
                ($qr ? ('<div class="q">Consulta: <a href="' . htmlspecialchars($qr) . '" target="_blank">' . htmlspecialchars($qr) . '</a></div>') : '') .
                '</body></html>';
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
        $shiftModel = new ShiftModel();
        $openShift = $shiftModel->where('id_contador', $idContador)
                                ->where('id_empresa', $idEmpresa)
                                ->where('status', 'open')
                                ->orderBy('id_shift', 'DESC')->first();
        if (! $openShift) {
            return $this->fail('Não há turno aberto.', 409);
        }
        $saleModel = new PosSaleModel();
        $active = $saleModel->where('id_shift', (int) ($openShift['id_shift'] ?? $openShift->id_shift))
                            ->where('status', 'draft')
                            ->orderBy('id_pos_sale', 'DESC')->first();
        if (! $active) {
            $saleId = $saleModel->insert([
                'id_shift' => (int) ($openShift['id_shift'] ?? $openShift->id_shift),
                'id_cash_register' => (int) ($openShift['id_cash_register'] ?? $openShift->id_cash_register),
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

    // Cancelamento fiscal integrado à SEFAZ (se houver NFC-e)
    public function cancel($id = null)
    {
        if ($id === null) return $this->failValidationErrors('ID é obrigatório');
        $sale = $this->model->find($id);
        if (! $sale) return $this->failNotFound('Venda não encontrada');
        $idNfce = is_array($sale) ? ($sale['id_nfce'] ?? null) : ($sale->id_nfce ?? null);
        if (! $idNfce) {
            // Sem NFC-e vinculada: apenas marca cancelada
            if (! $this->model->update($id, ['status' => 'cancelled'])) {
                return $this->failValidationErrors($this->model->errors());
            }
            return $this->respond($this->model->find($id));
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

        // Config e certificado
        $nfceController = new \App\Controllers\NFCe();
        $configJson = $nfceController->preparaConfigJson($emit);
        $arq_certificado = WRITEPATH . 'uploads/certificados/' . $emit['certificado'];
        $certificado_digital = file_exists($arq_certificado) ? file_get_contents($arq_certificado) : '';
        if ($certificado_digital === '') return $this->fail('Certificado não encontrado para cancelamento.', 500);
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
                // Estornar estoque a partir dos itens da venda
                $itemModel = new \App\Models\PosSaleItemModel();
                $produtoModel = new \App\Models\ProdutoModel();
                $itens = $itemModel->where('id_pos_sale', (int) (is_array($sale) ? ($sale['id_pos_sale'] ?? 0) : ($sale->id_pos_sale ?? $id)))->findAll();
                $estorno = [];
                foreach ($itens as $it) {
                    // precisa mapear id_produto se disponível; se não, pula
                    if (isset($it['id_produto'])) { $estorno[] = ['id_produto' => (int) $it['id_produto'], 'quantidade' => (float) ($it['quantidade'] ?? 0)]; }
                }
                if (!empty($estorno)) { $produtoModel->estornarEstoque($estorno); }
                return $this->respond($this->model->find($id));
            }
            $motivo = $std->retEvento->infEvento->xMotivo ?? $std->xMotivo ?? 'Motivo não especificado';
            return $this->fail('Falha ao cancelar: ' . $motivo, 500);
        } catch (\Throwable $e) {
            return $this->fail('Erro no cancelamento: ' . $e->getMessage(), 500);
        }
    }
}


