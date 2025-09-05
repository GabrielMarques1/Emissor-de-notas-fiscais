<?php

namespace App\Controllers;

use App\Models\NFCeModel;
use App\Models\ProdutoProvisorioModel;
use App\Models\ClienteModel;
use App\Models\LoginModel;
use App\Models\EmpresaModel;

use CodeIgniter\Controller;

// As classes só serão encontradas se o autoload.php for carregado corretamente.
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

use stdClass;
use ZipArchive;


class NFCe extends Controller
{
    private $session;
    private $id_contador;
    private $id_empresa;
    
    private $link = '3';

    private $nfce_model;
    private $produto_provisorio_model;
    private $cliente_model;
    private $login_model;
    private $empresa_model;

    private $tools;

    function __construct()
    {
        // --- SOLUÇÃO DEFINITIVA PARA O ERRO "CLASSE NÃO ENCONTRADA" ---
        $autoloader = FCPATH . '../app/ThirdParty/sped-nfe/vendor/autoload.php';
        $autoloader = realpath($autoloader);

        if ($autoloader === false || !file_exists($autoloader)) {
            throw new \RuntimeException(
                "<h1>ERRO CRÍTICO: Autoload da NFePHP não encontrado.</h1>" .
                "<p>O sistema não conseguiu carregar a biblioteca de emissão de notas.</p>" .
                "<p><b>Causa provável:</b> A estrutura de pastas do projeto foi alterada ou a biblioteca não está no local esperado.</p>" .
                "<hr><p><b>Ação recomendada:</b></p><ol>" .
                "<li>Confirme que a biblioteca NFePHP está localizada em: <br><strong><code>/app/ThirdParty/sped-nfe/</code></strong></li>" .
                "<li>Verifique se a pasta <strong><code>vendor</code></strong> existe dentro de <code>sped-nfe</code>. Se não existir, execute o comando <code>composer install</code> nesse diretório.</li>" .
                "<li>Se sua estrutura de pastas for diferente, ajuste o caminho na variável <code>\$autoloader</code> no início do método <code>__construct()</code>.</li>" .
                "</ol><p>Caminho verificado (não encontrado): <code>" . FCPATH . '../app/ThirdParty/sped-nfe/vendor/autoload.php' . "</code></p>"
            );
        }
        require_once $autoloader;
        // --- FIM DA SOLUÇÃO ---

        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->nfce_model               = new NFCeModel();
        $this->produto_provisorio_model = new ProdutoProvisorioModel();
        $this->cliente_model            = new ClienteModel();
        $this->login_model              = new loginModel();
        $this->empresa_model            = new EmpresaModel();
    }
    
    public function baixarXML($id_nfce)
    {
        $nfce = $this->nfce_model
                    ->where('id_contador', $this->id_contador)
                    ->where('id_empresa', $this->id_empresa)
                    ->where('id_nfce', $id_nfce)
                    ->first();

        $nome_do_arquivo = "{$nfce['chave']}.xml";
        $xml             = $nfce['xml'];

        return $this->response->download($nome_do_arquivo, $xml);
    }

    public function baixarXmlContador($id_nfce, $id_empresa)
    {
        $nfce = $this->nfce_model
                ->where('id_empresa', $id_empresa)
                ->where('id_nfce', $id_nfce)
                ->first();

        $nome_do_arquivo = "{$nfce['chave']}.xml";
        $xml             = $nfce['xml'];

        return $this->response->download($nome_do_arquivo, $xml);
    }

    public function baixaXMLS($data_inicio, $data_final)
    {
        $empresa = $this->empresa_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->first();

        $zipFilePath = "assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip";
        if(file_exists($zipFilePath)) :
            unlink($zipFilePath);
        endif;

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            $nfces = $this->nfce_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('data >=', $data_inicio)
                        ->where('data <=', $data_final)
                        ->findAll();

            foreach($nfces as $nfce) :
                $zip->addFromString("{$nfce['chave']}.xml", $nfce['xml']);
            endforeach;
            $zip->close();
            return $this->response->download($zipFilePath, NULL);
        } else {
            throw new \RuntimeException('Não foi possível criar o arquivo ZIP.');
        }
    }

    public function baixaXMLsContador($data_inicio, $data_final, $id_empresa)
    {
        $empresa = $this->empresa_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $id_empresa)
                        ->first();

        $zipFilePath = "assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip";
        if(file_exists($zipFilePath)) :
            unlink($zipFilePath);
        endif;

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            $nfces = $this->nfce_model
                        ->where('id_empresa', $id_empresa)
                        ->where('data >=', $data_inicio)
                        ->where('data <=', $data_final)
                        ->findAll();

            foreach($nfces as $nfce) :
                $zip->addFromString("{$nfce['chave']}.xml", $nfce['xml']);
            endforeach;
            $zip->close();
            return $this->response->download($zipFilePath, NULL);
        } else {
            throw new \RuntimeException('Não foi possível criar o arquivo ZIP.');
        }
    }

    public function detalhaRejeicao($rejeicao)
    {
        $data['titulo'] = [
            'modulo' => 'Falha na Emissão da Nota',
            'icone'  => 'fa fa-times-circle'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "NFC-e", 'rota' => "/notaDeSaida/emitir", 'active' => false],
            ['titulo' => "Erro", 'rota'   => "", 'active' => true]
        ];
        $data['link'] = $this->link;
        $data['rejeicao'] = str_replace(["\n", "\r"], ' ', $rejeicao);

        echo View('templates/header');
        echo View('emissor/rejeicao', $data);
        echo View('templates/footer');
    }

    public function montaXml($dados_do_emitente, $dados_da_nota, $produtos_da_nota)
    {
        $nfe = new Make();
        // Tag INFORMAÇÕES
        $inf = new stdClass();
        $inf->versao = '4.00';
        $nfe->taginfNFe($inf);

        // Tag IDE
        $ide = new stdClass();
        $ide->cUF = $dados_do_emitente['codigo_uf'];
        $ide->cNF = rand(1, 99999999);
        $ide->natOp = $dados_do_emitente['natOp'];
        $ide->mod = 65;
        $ide->serie = $dados_do_emitente['serie'];
        $numero_da_nf = ($dados_do_emitente['tpAmb_NFCe'] == 1) ? $dados_do_emitente['nNFC_producao'] : $dados_do_emitente['nNFC_homologacao'];
        $ide->nNF = $numero_da_nf;
        $ide->dhEmi = date('Y-m-d\TH:i:sP');
        $ide->tpNF = 1;
        $ide->idDest = 1;
        $ide->cMunFG = $dados_do_emitente['codigo'];
        $ide->tpImp = 4;
        $ide->tpEmis = 1;
        $ide->tpAmb = $dados_do_emitente['tpAmb_NFCe'];
        $ide->finNFe = 1;
        $ide->indFinal = 1;
        $ide->indPres = 1;
        $ide->procEmi = 0;
        $ide->verProc = "1.0.1";
        $nfe->tagide($ide);

        // Tag EMITENTE
        $emitente = new stdClass();
        $emitente->CNPJ = $dados_do_emitente['CNPJ'];
        $emitente->xNome = $dados_do_emitente['xNome'];
        $emitente->xFant = $dados_do_emitente['xFant'];
        $emitente->IE = $dados_do_emitente['IE'];
        $emitente->CRT = 1;
        $nfe->tagemit($emitente);

        $enderEmit = new stdClass();
        $enderEmit->xLgr = $dados_do_emitente['xLgr'];
        $enderEmit->nro = $dados_do_emitente['nro'];
        $enderEmit->xCpl = $dados_do_emitente['xCpl'];
        $enderEmit->xBairro = $dados_do_emitente['xBairro'];
        $enderEmit->cMun = $dados_do_emitente['codigo'];
        $enderEmit->xMun = $dados_do_emitente['municipio'];
        $enderEmit->UF = $dados_do_emitente['uf'];
        $enderEmit->CEP = $dados_do_emitente['CEP'];
        $enderEmit->cPais = "1058";
        $enderEmit->xPais = "BRASIL";
        $enderEmit->fone = $dados_do_emitente['fone'];
        $nfe->tagenderEmit($enderEmit);

        // Tag DESTINATÁRIO (se houver)
        if (isset($dados_da_nota['cpf_cnpj_na_nota']) && !empty($dados_da_nota['cpf_cnpj_na_nota'])) {
            $dest = new stdClass();
            if (isset($dados_da_nota['tipo_identificacao_na_nota']) && $dados_da_nota['tipo_identificacao_na_nota'] == "CPF") {
                $dest->CPF = $dados_da_nota['cpf_cnpj_na_nota'];
            } else {
                $dest->CNPJ = $dados_da_nota['cpf_cnpj_na_nota'];
            }
            $nfe->tagdest($dest);
        }

        // Loop de PRODUTOS
        foreach ($produtos_da_nota as $i => $produto) {
            $item = $i + 1;
            $prod = new \stdClass();
            $prod->item = $item;
            $prod->cProd = $produto['id_produto_provisorio'];
            $prod->cEAN = "SEM GTIN";
            $prod->xProd = $produto['nome'];
            $prod->NCM = $produto['NCM'];
            $prod->CFOP = $produto['CFOP_NFCe'];
            $prod->uCom = $produto['unidade'];
            $prod->qCom = $produto['quantidade'];
            $prod->vUnCom = format($produto['valor_unitario']);
            if ($produto['desconto'] != 0) {
                $prod->vDesc = format($produto['desconto']);
            }
            $prod->vProd = format($produto['valor_unitario']) * $produto['quantidade'];
            $prod->cEANTrib = "SEM GTIN";
            $prod->uTrib = $produto['unidade'];
            $prod->qTrib = $produto['quantidade'];
            $prod->vUnTrib = format($produto['valor_unitario']);
            $prod->indTot = 1;
            $nfe->tagprod($prod);

            // IMPOSTOS
            $imposto = new \stdClass(); $imposto->item = $item; $nfe->tagimposto($imposto);
            $icms = new \stdClass(); $icms->item = $item; $nfe->tagICMS($icms);
            $icmssn = new stdClass(); $icmssn->item = $item; $icmssn->orig = 0; $icmssn->CSOSN = '102'; $nfe->tagICMSSN($icmssn);
            $pis = new \stdClass(); $pis->item = $item; $pis->CST = '49'; $nfe->tagPIS($pis);
            $cofins = new \stdClass(); $cofins->item = $item; $cofins->CST = '49'; $nfe->tagCOFINS($cofins);
        }

        // TOTAIS
        $nfe->tagICMSTot(new stdClass());
        $transp = new stdClass(); $transp->modFrete = 9; $nfe->tagtransp($transp);

        // PAGAMENTO
        $pag = new stdClass();
        $pag->vTroco = format($dados_da_nota['troco']);
        $nfe->tagpag($pag);

        $detPag = new stdClass();
        $detPag->tPag = $dados_da_nota['tipo_de_pagamento'];
        $detPag->vPag = format($dados_da_nota['valor_da_nota']);
        $detPag->indPag = $dados_da_nota['forma_de_pagamento'];
        if (in_array($detPag->tPag, ["03", "04"])) { $detPag->tpIntegra = 2; }
        $nfe->tagdetPag($detPag);

        // RESPONSÁVEL TÉCNICO
        $respTec = new stdClass();
        $respTec->CNPJ = $dados_do_emitente['CNPJ'];
        $respTec->xContato = $dados_do_emitente['xNome'];
        $respTec->email = "suporte-empresa@gmail.com";
        $respTec->fone = $dados_do_emitente['fone'];
        $nfe->taginfRespTec($respTec);

        try {
            $nfe->montaNFe();
            return $nfe;
        } catch (\Exception $e) {
            $errors = $nfe->getErrors();
            $errorString = is_array($errors) ? implode(" | ", $errors) : 'Erro desconhecido ao montar a NFC-e.';
            throw new \RuntimeException("Falha na montagem do XML: " . $errorString);
        }
    }

    public function preparaConfigJson($dados_do_emitente)
    {
        return json_encode([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb"       => intval($dados_do_emitente['tpAmb_NFCe']),
            "razaosocial" => $dados_do_emitente['xNome'],
            "cnpj"        => $dados_do_emitente['CNPJ'],
            "ie"          => $dados_do_emitente['IE'],
            "siglaUF"     => $dados_do_emitente['uf'],
            "schemes"     => "PL_009_V4",
            "versao"      => '4.00',
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $dados_do_emitente['CSC'],
            "CSCid"       => $dados_do_emitente['CSC_Id']
        ]);
    }

    public function assinaXML($dados_do_emitente, $config_json, $xml)
    {
        $arq_certificado = WRITEPATH . "uploads/certificados/" . $dados_do_emitente['certificado'];
        if (!file_exists($arq_certificado)) {
            throw new \RuntimeException("Certificado digital não encontrado. Verifique o cadastro da empresa.");
        }
        $certificado_digital = file_get_contents($arq_certificado);

        try {
            $this->tools = new Tools($config_json, Certificate::readPfx($certificado_digital, $dados_do_emitente['senha_do_certificado']));
            $this->tools->model('65');
            return $this->tools->signNFe($xml);
        } catch (\Exception $e) {
            throw new \RuntimeException("Erro ao assinar o XML: " . $e->getMessage() . ". Verifique se a senha do certificado está correta.");
        }
    }

    public function enviaLoteParaSefaz($xml_assinado)
    {
        try {
            $id_lote = str_pad(time(), 15, '0', STR_PAD_LEFT);
            $resp = $this->tools->sefazEnviaLote([$xml_assinado], $id_lote, 1);
            $st = new Standardize();
            $std = $st->toStd($resp);

            if ($std->cStat == 104) { // Lote processado
                if ($std->protNFe->infProt->cStat == 100) { // Autorizado
                    return $resp;
                }
                throw new \RuntimeException("Nota REJEITADA: [{$std->protNFe->infProt->cStat}] {$std->protNFe->infProt->xMotivo}");
            }
            throw new \RuntimeException("O Lote foi REJEITADO pela SEFAZ: [{$std->cStat}] {$std->xMotivo}");

        } catch (\Exception $e) {
            throw new \RuntimeException("Falha de comunicação com a SEFAZ: " . $e->getMessage());
        }
    }

    public function protocolaXmlNaSefaz($xml_assinado, $protocolo)
    {
        try {
            return Complements::toAuthorize($xml_assinado, $protocolo);
        } catch (\Exception $e) {
            throw new \RuntimeException("Falha ao criar o XML protocolado: " . $e->getMessage());
        }
    }

    public function emitir()
    {
        try {
            // Etapa 1: Obtenção de dados
            $dados_do_emitente = $this->empresa_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                                    ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                                    ->first();

            $dados_da_nota = $this->request->getVar();

            $produtos_da_nota = $this->produto_provisorio_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->findAll();

            // Etapa 2: Validação DETALHADA dos dados antes de prosseguir
            $this->validarDados($dados_do_emitente, $produtos_da_nota);
            
            // Etapa 3: Preparação dos dados
            if(isset($dados_da_nota['cpf_cnpj_na_nota']) && !empty($dados_da_nota['cpf_cnpj_na_nota'])) {
                $dados_da_nota['cpf_cnpj_na_nota'] = removeMascaras($dados_da_nota['cpf_cnpj_na_nota']);
            }
            
            // Etapa 4: Montagem do XML
            $nfce = $this->montaXml($dados_do_emitente, $dados_da_nota, $produtos_da_nota);
            $xml = $nfce->getXML();

            // Etapa 5: Assinatura do XML
            $config_json = $this->preparaConfigJson($dados_do_emitente);
            $xml_assinado = $this->assinaXML($dados_do_emitente, $config_json, $xml);

            // Etapa 6: Envio para a SEFAZ e Protocolo
            $protocolo = $this->enviaLoteParaSefaz($xml_assinado);
            $xml_protocolado = $this->protocolaXmlNaSefaz($xml_assinado, $protocolo);

            // Etapa 7: Atualização do número da nota
            $campo_update = ($dados_do_emitente['tpAmb_NFCe'] == 1) ? 'nNFC_producao' : 'nNFC_homologacao';
            $guarda_numero_da_nota = $dados_do_emitente[$campo_update];
            $nova_nNFC = $guarda_numero_da_nota + 1;
            $this->empresa_model->update($this->id_empresa, [$campo_update => $nova_nNFC]);

            // Etapa 8: Salvar a NFC-e no banco de dados
            $id_nfce = $this->nfce_model->insert([
                'chave'           => $nfce->getChave(),
                'numero'          => $guarda_numero_da_nota,
                'valor_da_nota'   => $dados_da_nota['valor_da_nota'],
                'data'            => $dados_da_nota['data'],
                'hora'            => $dados_da_nota['hora'],
                'xml'             => $xml_protocolado,
                'protocolo'       => $protocolo,
                'status'          => "Emitida",
                'id_contador'     => $this->id_contador,
                'id_empresa'      => $this->id_empresa
            ]);

            // Etapa 9: Limpeza e Redirecionamento
            $this->produto_provisorio_model->where('id_empresa', $this->id_empresa)->delete();
            
            $this->session->setFlashdata('id_nfce', $id_nfce);
            return redirect()->to("/notaDeSaida/emitir");

        } catch (\InvalidArgumentException $e) {
            $this->detalhaRejeicao('Erro de Validação: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            $this->detalhaRejeicao('Erro no Processo: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->detalhaRejeicao('Erro Inesperado: ' . $e->getMessage());
        }
    }

    private function validarDados($dados_do_emitente, $produtos_da_nota)
    {
        $requiredEmitente = ['CNPJ', 'IE', 'xNome', 'xLgr', 'nro', 'xBairro', 'codigo', 'municipio', 'uf', 'CEP', 'codigo_uf', 'natOp', 'serie', 'tpAmb_NFCe', 'CSC', 'CSC_Id'];
        foreach ($requiredEmitente as $field) {
            if (empty($dados_do_emitente[$field])) {
                throw new \InvalidArgumentException("Dados do EMITENTE incompletos. O campo obrigatório '{$field}' está vazio. Verifique o cadastro da empresa.");
            }
        }

        if (empty($produtos_da_nota)) {
            throw new \InvalidArgumentException("Não há produtos na venda. Adicione pelo menos um item para emitir a nota.");
        }

        foreach ($produtos_da_nota as $produto) {
            if (empty($produto['nome'])) throw new \InvalidArgumentException("Um dos produtos está sem nome.");
            if (empty($produto['NCM']) || !is_numeric($produto['NCM']) || strlen((string)$produto['NCM']) != 8) throw new \InvalidArgumentException("O produto '{$produto['nome']}' tem um NCM inválido ('{$produto['NCM']}'). NCMs devem ter 8 dígitos numéricos.");
            if (empty($produto['CFOP_NFCe']) || !is_numeric($produto['CFOP_NFCe']) || strlen((string)$produto['CFOP_NFCe']) != 4) throw new \InvalidArgumentException("O produto '{$produto['nome']}' tem um CFOP inválido ('{$produto['CFOP_NFCe']}'). CFOPs devem ter 4 dígitos numéricos.");
            if (!isset($produto['quantidade']) || !is_numeric($produto['quantidade']) || $produto['quantidade'] <= 0) throw new \InvalidArgumentException("O produto '{$produto['nome']}' tem uma quantidade inválida.");
            if (!isset($produto['valor_unitario']) || !is_numeric($produto['valor_unitario'])) throw new \InvalidArgumentException("O produto '{$produto['nome']}' tem um valor unitário inválido.");
        }
    }


    public function cancelar()
    {
        try {
            $id_nfce = $this->request->getVar('id_nfce');
            $justificativa = $this->request->getVar('justificativa');

            if (empty($id_nfce) || empty($justificativa)) {
                throw new \InvalidArgumentException("ID da nota e justificativa são obrigatórios.");
            }

            $nfce = $this->nfce_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('id_nfce', $id_nfce)
                        ->first();

            if (!$nfce) throw new \RuntimeException("NFC-e não encontrada.");

            preg_match('/<nProt>(\d+)<\/nProt>/', $nfce['protocolo'], $matches);
            if (!isset($matches[1])) throw new \RuntimeException("Número do protocolo não encontrado no XML.");
            $num_do_protocolo = $matches[1];

            $dados_do_emitente = $this->empresa_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                                    ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                                    ->first();

            $configJson = $this->preparaConfigJson($dados_do_emitente);
            
            $arq_certificado = WRITEPATH . "uploads/certificados/" . $dados_do_emitente['certificado'];
            if (!file_exists($arq_certificado)) throw new \RuntimeException("Certificado digital não encontrado.");
            $certificado_digital = file_get_contents($arq_certificado);

            $certificate = Certificate::readPfx($certificado_digital, $dados_do_emitente['senha_do_certificado']);
            $tools = new Tools($configJson, $certificate);
            $tools->model('65');

            $response = $tools->sefazCancela($nfce['chave'], $justificativa, $num_do_protocolo);

            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();
            
            if ($std->cStat == 128 && in_array($std->retEvento->infEvento->cStat, ['101', '135', '155'])) {
                $xml = Complements::toAuthorize($tools->lastRequest, $response);
                $this->nfce_model->update($id_nfce, ['xml' => $xml, 'status' => 'Cancelada']);
                $this->session->setFlashdata('alert', ['type' => 'success', 'title' => 'Nota cancelada com sucesso!']);
            } else {
                $motivo = $std->retEvento->infEvento->xMotivo ?? $std->xMotivo ?? 'Motivo não especificado.';
                throw new \RuntimeException("Falha ao cancelar: [{$std->retEvento->infEvento->cStat}] {$motivo}");
            }

            return redirect()->to('/emissor/listaXMLsNFCe');
        } catch (\Exception $e) {
            $this->session->setFlashdata('alert', ['type' => 'error', 'title' => 'Erro ao Cancelar', 'message' => $e->getMessage()]);
            return redirect()->back();
        }
    }
}

