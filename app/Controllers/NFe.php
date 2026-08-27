<?php

namespace App\Controllers;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

use App\Models\UnidadeModel;
use App\Models\FornecedorModel;
use App\Models\TransportadoraModel;
use App\Models\NFeModel;
use App\Models\ProdutoProvisorioModel;
use App\Models\ClienteModel;
use App\Models\LoginModel;
use App\Models\EmpresaModel;

use CodeIgniter\Controller;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

use stdClass;
use ZipArchive;


class NFe extends Controller
{
    private $tipo = 1;

    private $link;

    private $session;
    private $id_contador;
    private $id_empresa;
    
    private $unidade_model;
    private $fornecedor_model;
    private $transportadora_model;
    private $nfe_model;
    private $produto_provisorio_model;
    private $cliente_model;
    private $login_model;
    private $empresa_model;

    private $tools;

    function __construct()
    {
        require_once APPPATH . "ThirdParty/sped-nfe/vendor/autoload.php";

        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');
        
        $this->unidade_model            = new UnidadeModel();
        $this->fornecedor_model         = new FornecedorModel();
        $this->transportadora_model     = new TransportadoraModel();
        $this->nfe_model                = new NFeModel();
        $this->produto_provisorio_model = new ProdutoProvisorioModel();
        $this->cliente_model            = new ClienteModel();
        $this->login_model              = new loginModel();
        $this->empresa_model            = new EmpresaModel();
    }

    // -------------------------- Método voltado para o usuário que entrar como Emissor -------------------------- //
    public function baixarXML($id_nfe)
    {
        $nfe = $this->nfe_model
                    ->where('id_contador', $this->id_contador)
                    ->where('id_empresa', $this->id_empresa)
                    ->where('id_nfe', $id_nfe)
                    ->first();


        $nome_do_arquivo = "{$nfe['chave']}.xml";
        $xml             = $nfe['xml'];

        return $this->response->download($nome_do_arquivo, $xml);
    }

    // -------------------------- Método voltado para o usuário que entrar como Contador -------------------------- //
    public function baixarXmlContador($id_nfe, $id_empresa)
    {
        $nfe = $this->nfe_model
                    ->where('id_empresa', $id_empresa)
                    ->where('id_nfe', $id_nfe)
                    ->first();

        $nome_do_arquivo = "{$nfe['chave']}.xml";
        $xml             = $nfe['xml'];

        return $this->response->download($nome_do_arquivo, $xml);
    }

    // -------------------------- Método voltado para o usuário que entrar como Emissor -------------------------- //
    public function baixaXMLS($data_inicio, $data_final)
    {
        $empresa = $this->empresa_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->first();

        // Apaga se existir.
        if(file_exists("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip")) :

            unlink("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip");
        
        endif;

        $zip = new ZipArchive();
        $res = $zip->open("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip", ZipArchive::CREATE);
     
        if ($res === TRUE)
        {
            $nfes = $this->nfe_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('data >=', $data_inicio)
                        ->where('data <=', $data_final)
                        ->findAll();

            foreach($nfes as $nfe) :
                $zip->addFromString("{$nfe['chave']}.xml", $nfe['xml']);
            endforeach;

            $zip->setArchiveComment('new archive comment');
            $zip->close();

            return $this->response->download("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip", NULL);
        }
        else
        {
            echo 'failed';
        }
    }

    // -------------------------- Método voltado para o usuário que entrar como Contador -------------------------- //
    public function baixaXMLsContador($data_inicio, $data_final, $id_empresa)
    {
        $empresa = $this->empresa_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $id_empresa)
                        ->first();
                        
        // Apaga se existir.
        if(file_exists("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip")) :

            unlink("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip");
        
        endif;

        $zip = new ZipArchive();
        $res = $zip->open("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip", ZipArchive::CREATE);
     
        if ($res === TRUE)
        {
            $nfes = $this->nfe_model
                        ->where('id_empresa', $id_empresa)
                        ->where('data >=', $data_inicio)
                        ->where('data <=', $data_final)
                        ->findAll();

            foreach($nfes as $nfe) :
                $zip->addFromString("{$nfe['chave']}.xml", $nfe['xml']);
            endforeach;

            $zip->setArchiveComment('new archive comment');
            $zip->close();

            return $this->response->download("assets/temp_zip_xmls/{$empresa['xFant']}-{$empresa['CNPJ']}-{$data_inicio}-ate-{$data_final}.zip", NULL);
        }
        else
        {
            echo 'failed';
        }
    }  
    
    public function detalhaRejeicao($rejeicao)
    {
        $data['titulo'] = [
            'modulo' => 'Rejeição da Nota',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['link'] = $this->link;

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Clientes", 'rota' => "/clientes", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        $data['rejeicao'] = $rejeicao;

        echo View('templates/header');
        echo View('emissor/rejeicao', $data);
        echo View('templates/footer');
    }

    public function montaXmlEntrada($dados_do_emitente, $dados_da_nota, $produtos_da_nota)
    {
        // dd($dados_do_emitente);

        $nfe = new Make();

        // ----------- Tag INFORMAÇÕES ------------- //
        $inf           = new stdClass();
        $inf->versao   = '4.00'; //versão do layout (string)
        $inf->Id       = null; //se o Id de 44 digitos não for passado será gerado automaticamente
        $inf->pk_nItem = null; //deixe essa variavel sempre como NULL
        $nfe->taginfNFe($inf);

        // ----------- Tag IDE ------------- //
        $ide           = new stdClass();
        $ide->cUF      = $dados_do_emitente['codigo_uf'];
        $ide->cNF      = rand(1, 99999999);
        $ide->natOp    = $dados_da_nota['natureza_da_operacao'];
        // $ide->indPag   = 0; //NÃO EXISTE MAIS NA VERSÃO 4.00
        $ide->mod      = 55;
        $ide->serie    = $dados_do_emitente['serie'];

        if($dados_do_emitente['tpAmb_NFe'] == 1) :
            $numero_da_nf = $dados_do_emitente['nNF_producao'];
        else:
            $numero_da_nf = $dados_do_emitente['nNF_homologacao'];
        endif;

        // echo $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP");
        // exit();

        $ide->nNF      = $numero_da_nf;
        $ide->dhEmi    = $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP"); //date('Y-m-d\TH:i:sP');
        $ide->dhSaiEnt = $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP"); //date('Y-m-d\TH:i:sP');
        $ide->tpNF     = 0; // Tipo de operação: 0-entrada, 1-saida
        $ide->idDest   = 1;
        $ide->cMunFG   = $dados_do_emitente['codigo'];
        $ide->tpImp    = 1;
        $ide->tpEmis   = 1;
        $ide->cDV      = 0;
        $ide->tpAmb    = $dados_do_emitente['tpAmb_NFe'];
        $ide->finNFe   = 1;
        $ide->indFinal = 0;
        $ide->indPres  = 1;
        $ide->procEmi  = 0;
        $ide->verProc  = "xF 1.0.1";
        $ide->dhCont   = null;
        $ide->xJust    = null;
        $nfe->tagide($ide);

        // ----------- Tag EMITENTE ------------- //
        // -- Emitente -- //
        $emitente        = new stdClass();
        $emitente->CNPJ  = $dados_do_emitente['CNPJ'];
        $emitente->xNome = $dados_do_emitente['xNome'];
        $emitente->xFant = $dados_do_emitente['xFant'];
        $emitente->IE    = $dados_do_emitente['IE'];
        $emitente->CRT   = 1; // 1=Simples Nacional
        $nfe->tagemit($emitente);

        // -- Endereço do emitente -- //
        $endereco_do_emitente          = new stdClass();
        $endereco_do_emitente->xLgr    = $dados_do_emitente['xLgr'];
        $endereco_do_emitente->nro     = $dados_do_emitente['nro'];
        $endereco_do_emitente->xCpl    = $dados_do_emitente['xCpl'];
        $endereco_do_emitente->xBairro = $dados_do_emitente['xBairro'];
        $endereco_do_emitente->cMun    = $dados_do_emitente['codigo'];
        $endereco_do_emitente->xMun    = $dados_do_emitente['municipio'];
        $endereco_do_emitente->UF      = $dados_do_emitente['uf'];
        $endereco_do_emitente->CEP     = $dados_do_emitente['CEP'];
        $endereco_do_emitente->cPais   = "1058";
        $endereco_do_emitente->xPais   = "BRASIL";
        $endereco_do_emitente->fone    = $dados_do_emitente['fone'];
        $nfe->tagenderEmit($endereco_do_emitente);

        // ----------- Tag DESTINATÁRIO ------------- //
        // -- Destinatário -- //
        $destinatario        = new stdClass();
        $destinatario->CNPJ  = $dados_do_emitente['CNPJ'];
        $destinatario->xNome = $dados_do_emitente['xNome'];
        $destinatario->indIEDest = 1; // 1=Não Isento, 2=Isento
        $destinatario->IE = $dados_do_emitente['IE'];

        $nfe->tagdest($destinatario);

        // -- Endereço do destinatário -- //
        $endereco_do_destinatario = new stdClass();
        $endereco_do_destinatario->xLgr = $dados_do_emitente['xLgr'];
        
        if($dados_do_emitente['nro'] == "" || $dados_do_emitente['nro'] == 0) :
            $endereco_do_destinatario->nro = "S/N"; 
        else :
            $endereco_do_destinatario->nro = $dados_do_emitente['nro'];
        endif;

        $endereco_do_destinatario->xCpl    = $dados_do_emitente['xCpl'];
        $endereco_do_destinatario->xBairro = $dados_do_emitente['xBairro'];
        $endereco_do_destinatario->cMun    = $dados_do_emitente['codigo']; // Código do municipio
        $endereco_do_destinatario->xMun    = $dados_do_emitente['municipio']; // Nome do municipio
        $endereco_do_destinatario->UF      = $dados_do_emitente['uf'];
        $endereco_do_destinatario->CEP     = $dados_do_emitente['CEP'];
        $endereco_do_destinatario->cPais   = '1058';
        $endereco_do_destinatario->xPais   = 'BRASIL';
        $nfe->tagenderDest($endereco_do_destinatario);

        // ------------------------------------------- TAG PRODUTOS ---------------------------------------------- //
        $i = 0;
        foreach ($produtos_da_nota as $produto) :
            $i += 1;

            // ----------- Tag PRODUTOS ------------- //
            $std_produto         = new \stdClass();
            $std_produto->item   = $i;
            $std_produto->cProd  = $produto['id_produto'];

            // Verifica e configura o código de barras
            if($produto['codigo_de_barras'] == 0) :
                $codigo_de_barras = "SEM GTIN"; // Caso o produto não possua código de barras
            else :
                $codigo_de_barras = $produto['codigo_de_barras']; // Caso possua
            endif;

            $std_produto->cEAN   = $codigo_de_barras;
            $std_produto->xProd  = $produto['nome'];
            $std_produto->NCM    = $produto['NCM'];
            $std_produto->CFOP   = $produto['CFOP_NFe'];
            $std_produto->uCom   = $produto['unidade'];
            $std_produto->qCom   = $produto['quantidade']; // QUANTIDADE COMPRADA -----------------------------------------------------------
            $std_produto->vUnCom = format($produto['valor_unitario']); // COLOCAR O VALOR UNITÁRIO DO PRODUTO --------------------------------------------------------------

            // CASO HAJA DESCONTO NO PRODUTO INSERIDO AUTOMATICAMENTE CONDIÇÃO = DIFENTE DE ZERO
            if($produto['desconto'] != 0) :
                $std_produto->vDesc  = format($produto['desconto']); // DESCONTO DO PRODUTO
            endif;

            $subtotal = format($produto['valor_unitario']) * $produto['quantidade'];

            $std_produto->vProd    = format($subtotal); // COLOCAR O VALOR TOTAL QTDxVALOR.UNITARIO --------------------------------------------------------
            $std_produto->cEANTrib = $codigo_de_barras;
            $std_produto->uTrib    = $produto['unidade'];
            $std_produto->qTrib    = $produto['quantidade']; // QUANTIDADE A SER TRIBUTADA ----------------------------------------------------------------------------
            $std_produto->vUnTrib  = format($produto['valor_unitario']); // COLOCAR O VALOR DA UNIDADE -----------------------------------------------------------------------
            $std_produto->indTot   = 1; // Indica se o valor do item (vProd) entra no total da NF-e. 0-não compoe, 1 compoe
            $nfe->tagprod($std_produto);

            // -- Tag imposto -- //
            $std_imposto       = new \stdClass();
            $std_imposto->item = $i;
            $nfe->tagimposto($std_imposto);

            // -- Tag ICMS -- //
            $std_icms       = new \stdClass();
            $std_icms->item = $i;
            $nfe->tagICMS($std_icms);

            // -- Tag ICMSSN -- //
            $std_icmssm                  = new stdClass();
            $std_icmssm->item            = $i; //item da NFe
            $std_icmssm->orig            = 0;
            $std_icmssm->CSOSN           = $produto['CSOSN'];
            $std_icmssm->pCredSN         = '0.00';
            $std_icmssm->vCredICMSSN     = '0.00';
            $std_icmssm->modBCST         = null;
            $std_icmssm->pMVAST          = null;
            $std_icmssm->pRedBCST        = null;
            $std_icmssm->vBCST           = null;
            $std_icmssm->pICMSST         = null;
            $std_icmssm->vICMSST         = null;
            $std_icmssm->vBCFCPST        = null; //incluso no layout 4.00
            $std_icmssm->pFCPST          = null; //incluso no layout 4.00
            $std_icmssm->vFCPST          = null; //incluso no layout 4.00
            $std_icmssm->vBCSTRet        = null;
            $std_icmssm->pST             = null;
            $std_icmssm->vICMSSTRet      = null;
            $std_icmssm->vBCFCPSTRet     = null; //incluso no layout 4.00
            $std_icmssm->pFCPSTRet       = null; //incluso no layout 4.00
            $std_icmssm->vFCPSTRet       = null; //incluso no layout 4.00
            $std_icmssm->modBC           = null;
            $std_icmssm->vBC             = null;
            $std_icmssm->pRedBC          = null;
            $std_icmssm->pICMS           = null;
            $std_icmssm->vICMS           = null;
            $std_icmssm->pRedBCEfet      = null;
            $std_icmssm->vBCEfet         = null;
            $std_icmssm->pICMSEfet       = null;
            $std_icmssm->vICMSEfet       = null;
            $std_icmssm->vICMSSubstituto = null;
            $nfe->tagICMSSN($std_icmssm);

            // -- Tag PIS -- //
            $std_pis = new \stdClass();
            $std_pis->item = $i;
            $std_pis->CST  = '01';
            $std_pis->vBC  = '0.00';
            $std_pis->pPIS = '0.00';
            $std_pis->vPIS = '0.00';
            $nfe->tagPIS($std_pis);

            // -- COFINS -- //
            $std_cofins             = new \stdClass();
            $std_cofins->item       = $i;
            $std_cofins->CST        = '01';
            $std_cofins->vBC        = '0.00';
            $std_cofins->pCOFINS    = '0.0000';
            $std_cofins->vCOFINS    = '00.0';
            // $std_cofins->qBCProd = 0;
            $std_cofins->vAliqProd  = 0;
            $nfe->tagCOFINS($std_cofins);
        endforeach;

        // ----------- Tag ICMS TOTAL ------------- //
        $icms_total             = new stdClass();
        $icms_total->vBC        = null;
        $icms_total->vICMS      = null;
        $icms_total->vICMSDeson = null;
        $icms_total->vFCP       = null;
        $icms_total->vBCST      = null;
        $icms_total->vST        = null;
        $icms_total->vFCPST     = null;
        $icms_total->vFCPSTRet  = null;
        $icms_total->vProd      = null;
        $icms_total->vFrete     = null;
        $icms_total->vSeg       = null;
        $icms_total->vDesc      = null;
        $icms_total->vII        = null;
        $icms_total->vIPI       = null;
        $icms_total->vIPIDevol  = null;
        $icms_total->vPIS       = null;
        $icms_total->vCOFINS    = null;
        $icms_total->vOutro     = null;
        $icms_total->vNF        = null;
        $icms_total->vTotTrib   = null;
        $nfe->tagICMSTot($icms_total);

        // ----------- Tag TRANSPORTE ------------- //
        $transporte           = new stdClass();
        $transporte->modFrete = 9; // 1=com transporte, 9=Sem transporte
        $nfe->tagtransp($transporte);
        
        // ----------- Tag PAGAMENTO ------------- //
        $pagamento         = new stdClass();
        $pagamento->vTroco = null;
        $nfe->tagpag($pagamento);

        // -- Tipo de pagamento -- //
        $tipo_de_pagamento            = new stdClass();
        $tipo_de_pagamento->tPag      = $dados_da_nota['tipo_de_pagamento']; // 01=Dinheiro, 02=Cheque, 03=Cartão de Crédito ...
        $tipo_de_pagamento->vPag      = format($dados_da_nota['valor_da_nota']); //Obs: deve ser informado o valor total da nota
        $tipo_de_pagamento->indPag    = $dados_da_nota['forma_de_pagamento']; //0= Pagamento à Vista 1= Pagamento à Prazo
        $nfe->tagdetPag($tipo_de_pagamento);

        // ----------- Tag RESPONSÁVEL TÉCNICO ------------- // 
        $responsavel_tecnico           = new stdClass();
        $responsavel_tecnico->CNPJ     = $dados_do_emitente['CNPJ']; //CNPJ da pessoa jurídica
        $responsavel_tecnico->xContato = $dados_do_emitente['xNome']; //Nome da pessoa a ser contatada
        $responsavel_tecnico->email    = "suporte-empresa@gmail.com"; //E-mail da pessoa jurídica a ser contatada
        $responsavel_tecnico->fone     = $dados_do_emitente['fone']; //Telefone da pessoa jurídica/física a ser contatada
        $responsavel_tecnico->CSRT     = ''; //Código de Segurança do Responsável Técnico
        $responsavel_tecnico->idCSRT   = '0'; //Identificador do CSRT
        $nfe->taginfRespTec($responsavel_tecnico);
        
        // ----------- Tag INFORMAÇÕES ADICIONAIS --------- //
        if($dados_da_nota['informacoes_complementares'] != "" || $dados_da_nota['infomacoes_para_fisco']) :

            if($dados_da_nota['informacoes_complementares'] != "" && $dados_da_nota['infomacoes_para_fisco'] != "") :
                
                $std_informacoes_adicionais = new stdClass();
                $std_informacoes_adicionais->infAdFisco = $dados_da_nota['infomacoes_para_fisco'];
                $std_informacoes_adicionais->infCpl     = $dados_da_nota['informacoes_complementares'];
                $nfe->taginfAdic($std_informacoes_adicionais);

            elseif($dados_da_nota['informacoes_complementares'] != "") :

                $std_informacoes_adicionais = new stdClass();
                $std_informacoes_adicionais->infAdFisco = null;
                $std_informacoes_adicionais->infCpl     = $dados_da_nota['informacoes_complementares'];
                $nfe->taginfAdic($std_informacoes_adicionais);

            elseif($dados_da_nota['infomacoes_para_fisco'] != "") :

                $std_informacoes_adicionais = new stdClass();
                $std_informacoes_adicionais->infAdFisco = $dados_da_nota['infomacoes_para_fisco'];
                $std_informacoes_adicionais->infCpl     = null;
                $nfe->taginfAdic($std_informacoes_adicionais);

            endif;

        endif;

        // Verifica se todos os campos foram preenchidos corretamente e depois gera o XML
        try
        {
            return $nfe; // Retorna a instância da nota
        }
        catch (\Exception $e)
        {
            $erros = $nfe->getErrors();
            exit($nfe->getErrors());
        }
    }

    public function montaXml($dados_do_emitente, $dados_da_nota, $dados_do_destinatario, $produtos_da_nota)
    {
        $nfe = new Make();

        // ----------- Tag INFORMAÇÕES ------------- //
        $inf           = new stdClass();
        $inf->versao   = '4.00'; //versão do layout (string)
        $inf->Id       = null; //se o Id de 44 digitos não for passado será gerado automaticamente
        $inf->pk_nItem = null; //deixe essa variavel sempre como NULL
        $nfe->taginfNFe($inf);

        // ----------- Tag IDE ------------- //
        $ide           = new stdClass();
        $ide->cUF      = $dados_do_emitente['codigo_uf'];
        $ide->cNF      = rand(1, 99999999);
        $ide->natOp    = $dados_da_nota['natureza_da_operacao'];
        // $ide->indPag   = 0; //NÃO EXISTE MAIS NA VERSÃO 4.00
        $ide->mod      = 55;
        $ide->serie    = $dados_do_emitente['serie'];

        if($dados_do_emitente['tpAmb_NFe'] == 1) :
            $numero_da_nf = $dados_do_emitente['nNF_producao'];
        else:
            $numero_da_nf = $dados_do_emitente['nNF_homologacao'];
        endif;

        // echo $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP");
        // exit();

        $ide->nNF      = $numero_da_nf;
        $ide->dhEmi    = $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP"); //date('Y-m-d\TH:i:sP');
        $ide->dhSaiEnt = $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP"); //date('Y-m-d\TH:i:sP');
        $ide->tpNF     = 1; // Tipo de operação: 0-entrada, 1-saida

        if($dados_do_emitente['uf'] != $dados_do_destinatario['uf']) :
            $ide->idDest = 2; // Estadual=1, Interestadual=2
        else:
            $ide->idDest   = 1; // Estadual=1, Interestadual=2
        endif;

        $ide->cMunFG   = $dados_do_emitente['codigo'];
        $ide->tpImp    = 1;
        $ide->tpEmis   = 1;
        $ide->cDV      = 0;
        $ide->tpAmb    = $dados_do_emitente['tpAmb_NFe'];
        $ide->finNFe   = 1;
		
		// Verifica se está em algum dos estados abaixo, e coloca como consumidor final
        if (
            $dados_do_destinatario['uf'] == "AM" ||
            $dados_do_destinatario['uf'] == "BA" ||
            $dados_do_destinatario['uf'] == "CE" ||
            $dados_do_destinatario['uf'] == "GO" ||
            $dados_do_destinatario['uf'] == "MG" ||
            $dados_do_destinatario['uf'] == "MS" ||
            $dados_do_destinatario['uf'] == "MT" ||
            $dados_do_destinatario['uf'] == "PA" ||
            $dados_do_destinatario['uf'] == "PE" ||
            $dados_do_destinatario['uf'] == "RN" ||
            $dados_do_destinatario['uf'] == "SE" ||
            $dados_do_destinatario['uf'] == "SP"
        ) {
            $ide->indFinal = 1; // 0=Consumidor Normal, 1=Consumidor Final
        } else {
            $ide->indFinal = 0; // 0=Consumidor Normal, 1=Consumidor Final
        }
        
		$ide->indPres  = 1;
        $ide->procEmi  = 0;
        $ide->verProc  = "xF 1.0.1";
        $ide->dhCont   = null;
        $ide->xJust    = null;
        $nfe->tagide($ide);

        // ----------- Tag EMITENTE ------------- //
        // -- Emitente -- //
        $emitente        = new stdClass();
        $emitente->CNPJ  = $dados_do_emitente['CNPJ'];
        $emitente->xNome = $dados_do_emitente['xNome'];
        $emitente->xFant = $dados_do_emitente['xFant'];
        $emitente->IE    = $dados_do_emitente['IE'];
        $emitente->CRT   = 1; // 1=Simples Nacional
        $nfe->tagemit($emitente);

        // -- Endereço do emitente -- //
        $endereco_do_emitente          = new stdClass();
        $endereco_do_emitente->xLgr    = $dados_do_emitente['xLgr'];
        $endereco_do_emitente->nro     = $dados_do_emitente['nro'];
        $endereco_do_emitente->xCpl    = $dados_do_emitente['xCpl'];
        $endereco_do_emitente->xBairro = $dados_do_emitente['xBairro'];
        $endereco_do_emitente->cMun    = $dados_do_emitente['codigo'];
        $endereco_do_emitente->xMun    = $dados_do_emitente['municipio'];
        $endereco_do_emitente->UF      = $dados_do_emitente['uf'];
        $endereco_do_emitente->CEP     = $dados_do_emitente['CEP'];
        $endereco_do_emitente->cPais   = "1058";
        $endereco_do_emitente->xPais   = "BRASIL";
        $endereco_do_emitente->fone    = $dados_do_emitente['fone'];
        $nfe->tagenderEmit($endereco_do_emitente);

        // ----------- Tag DESTINATÁRIO ------------- //
        // -- Destinatário -- //
        if($dados_do_destinatario['tipo'] == 1) : // Caso seja pessoa física
            $destinatario        = new stdClass();
            $destinatario->CPF   = $dados_do_destinatario['cpf'];
            $destinatario->xNome = $dados_do_destinatario['nome'];
            
            if($dados_do_destinatario['isento'] == 1) :
				
				// Verifica se está em algum dos estados abaixo, e coloca como não contribuinte
                if (
                    $dados_do_destinatario['uf'] == "AM" || 
                    $dados_do_destinatario['uf'] == "BA" || 
                    $dados_do_destinatario['uf'] == "CE" || 
                    $dados_do_destinatario['uf'] == "GO" || 
                    $dados_do_destinatario['uf'] == "MG" ||
                    $dados_do_destinatario['uf'] == "MS" ||
                    $dados_do_destinatario['uf'] == "MT" ||
                    $dados_do_destinatario['uf'] == "PA" ||
                    $dados_do_destinatario['uf'] == "PE" ||
                    $dados_do_destinatario['uf'] == "RN" ||
                    $dados_do_destinatario['uf'] == "SE" ||
                    $dados_do_destinatario['uf'] == "SP"
                ){
                    $destinatario->indIEDest = 9; // 9=Não Contribuinte
                }
                else
                {
                    $destinatario->indIEDest = 2; // 1=Não Isento, 2=Isento
                }

            else:

                $destinatario->indIEDest = 1; // 1=Não Isento, 2=Isento
                $destinatario->IE = $dados_do_destinatario['ie'];

            endif;
        else : // Caso seja pessoa juridica
            $destinatario        = new stdClass();
            $destinatario->CNPJ  = $dados_do_destinatario['cnpj'];
            $destinatario->xNome = $dados_do_destinatario['razao_social'];

            if($dados_do_destinatario['isento'] == 1) :
                
                $destinatario->indIEDest = 2; // 1=Não Isento, 2=Isento

            else:

                $destinatario->indIEDest = 1; // 1=Não Isento, 2=Isento
                $destinatario->IE = $dados_do_destinatario['ie'];

            endif;
        endif;

        $nfe->tagdest($destinatario);


        // -- Endereço do destinatário -- //
        $endereco_do_destinatario = new stdClass();
        $endereco_do_destinatario->xLgr = $dados_do_destinatario['logradouro'];
        
        if($dados_do_destinatario['numero'] == "" || $dados_do_destinatario['numero'] == 0) :
            $endereco_do_destinatario->nro = "S/N"; 
        else :
            $endereco_do_destinatario->nro = $dados_do_destinatario['numero'];
        endif;

        $endereco_do_destinatario->xCpl    = $dados_do_destinatario['complemento'];
        $endereco_do_destinatario->xBairro = $dados_do_destinatario['bairro'];
        $endereco_do_destinatario->cMun    = $dados_do_destinatario['codigo']; // Código do municipio
        $endereco_do_destinatario->xMun    = $dados_do_destinatario['municipio']; // Nome do municipio
        $endereco_do_destinatario->UF      = $dados_do_destinatario['uf'];
        $endereco_do_destinatario->CEP     = $dados_do_destinatario['cep'];
        $endereco_do_destinatario->cPais   = '1058';
        $endereco_do_destinatario->xPais   = 'BRASIL';
        $endereco_do_destinatario->fone    = $dados_do_destinatario['fone'];
        $nfe->tagenderDest($endereco_do_destinatario);

        // ------------------------------------------- TAG PRODUTOS ---------------------------------------------- //
        $i = 0;
        foreach ($produtos_da_nota as $produto) :
            $i += 1;

            // ----------- Tag PRODUTOS ------------- //
            $std_produto         = new \stdClass();
            $std_produto->item   = $i;
            $std_produto->cProd  = $produto['id_produto'];

            // Verifica e configura o código de barras
            if($produto['codigo_de_barras'] == 0) :
                $codigo_de_barras = "SEM GTIN"; // Caso o produto não possua código de barras
            else :
                $codigo_de_barras = $produto['codigo_de_barras']; // Caso possua
            endif;

            $std_produto->cEAN   = $codigo_de_barras;
            $std_produto->xProd  = $produto['nome'];
            $std_produto->NCM    = $produto['NCM'];

            // Verifica se a operação é fora do estado, se for pega o CFOP_Externo
            if($dados_do_emitente['uf'] != $dados_do_destinatario['uf']) :
                $std_produto->CFOP = $produto['CFOP_Externo'];
            else:
                $std_produto->CFOP = $produto['CFOP_NFe'];
            endif;

            $std_produto->uCom   = $produto['unidade'];
            $std_produto->qCom   = $produto['quantidade']; // QUANTIDADE COMPRADA -----------------------------------------------------------
            $std_produto->vUnCom = format($produto['valor_unitario']); // COLOCAR O VALOR UNITÁRIO DO PRODUTO --------------------------------------------------------------

            // CASO HAJA DESCONTO NO PRODUTO INSERIDO AUTOMATICAMENTE CONDIÇÃO = DIFENTE DE ZERO
            if($produto['desconto'] != 0) :
                $std_produto->vDesc  = format($produto['desconto']); // DESCONTO DO PRODUTO
            endif;

            $subtotal = format($produto['valor_unitario']) * $produto['quantidade'];

            $std_produto->vProd    = format($subtotal); // COLOCAR O VALOR TOTAL QTDxVALOR.UNITARIO --------------------------------------------------------
            $std_produto->cEANTrib = $codigo_de_barras;
            $std_produto->uTrib    = $produto['unidade'];
            $std_produto->qTrib    = $produto['quantidade']; // QUANTIDADE A SER TRIBUTADA ----------------------------------------------------------------------------
            $std_produto->vUnTrib  = format($produto['valor_unitario']); // COLOCAR O VALOR DA UNIDADE -----------------------------------------------------------------------
            $std_produto->indTot   = 1; // Indica se o valor do item (vProd) entra no total da NF-e. 0-não compoe, 1 compoe
            $nfe->tagprod($std_produto);

            // -- Tag imposto -- //
            $std_imposto       = new \stdClass();
            $std_imposto->item = $i;
            $nfe->tagimposto($std_imposto);

            // -- Tag ICMS -- //
            $std_icms       = new \stdClass();
            $std_icms->item = $i;
            $nfe->tagICMS($std_icms);

            // -- Tag ICMSSN -- //
            $std_icmssm                  = new stdClass();
            $std_icmssm->item            = $i; //item da NFe
            $std_icmssm->orig            = 0;
            $std_icmssm->CSOSN           = $produto['CSOSN'];
            $std_icmssm->pCredSN         = '0.00';
            $std_icmssm->vCredICMSSN     = '0.00';
            $std_icmssm->modBCST         = null;
            $std_icmssm->pMVAST          = null;
            $std_icmssm->pRedBCST        = null;
            $std_icmssm->vBCST           = null;
            $std_icmssm->pICMSST         = null;
            $std_icmssm->vICMSST         = null;
            $std_icmssm->vBCFCPST        = null; //incluso no layout 4.00
            $std_icmssm->pFCPST          = null; //incluso no layout 4.00
            $std_icmssm->vFCPST          = null; //incluso no layout 4.00
            $std_icmssm->vBCSTRet        = null;
            $std_icmssm->pST             = null;
            $std_icmssm->vICMSSTRet      = null;
            $std_icmssm->vBCFCPSTRet     = null; //incluso no layout 4.00
            $std_icmssm->pFCPSTRet       = null; //incluso no layout 4.00
            $std_icmssm->vFCPSTRet       = null; //incluso no layout 4.00
            $std_icmssm->modBC           = null;
            $std_icmssm->vBC             = null;
            $std_icmssm->pRedBC          = null;
            $std_icmssm->pICMS           = null;
            $std_icmssm->vICMS           = null;
            $std_icmssm->pRedBCEfet      = null;
            $std_icmssm->vBCEfet         = null;
            $std_icmssm->pICMSEfet       = null;
            $std_icmssm->vICMSEfet       = null;
            $std_icmssm->vICMSSubstituto = null;
            $nfe->tagICMSSN($std_icmssm);

            // -- Tag PIS -- //
            $std_pis = new \stdClass();
            $std_pis->item = $i;
            $std_pis->CST  = '01';
            $std_pis->vBC  = '0.00';
            $std_pis->pPIS = '0.00';
            $std_pis->vPIS = '0.00';
            $nfe->tagPIS($std_pis);

            // -- COFINS -- //
            $std_cofins             = new \stdClass();
            $std_cofins->item       = $i;
            $std_cofins->CST        = '01';
            $std_cofins->vBC        = '0.00';
            $std_cofins->pCOFINS    = '0.0000';
            $std_cofins->vCOFINS    = '00.0';
            // $std_cofins->qBCProd = 0;
            $std_cofins->vAliqProd  = 0;
            $nfe->tagCOFINS($std_cofins);
        endforeach;

        // ----------- Tag ICMS TOTAL ------------- //
        $icms_total             = new stdClass();
        $icms_total->vBC        = null;
        $icms_total->vICMS      = null;
        $icms_total->vICMSDeson = null;
        $icms_total->vFCP       = null;
        $icms_total->vBCST      = null;
        $icms_total->vST        = null;
        $icms_total->vFCPST     = null;
        $icms_total->vFCPSTRet  = null;
        $icms_total->vProd      = null;
        $icms_total->vFrete     = null;
        $icms_total->vSeg       = null;
        $icms_total->vDesc      = null;
        $icms_total->vII        = null;
        $icms_total->vIPI       = null;
        $icms_total->vIPIDevol  = null;
        $icms_total->vPIS       = null;
        $icms_total->vCOFINS    = null;
        $icms_total->vOutro     = null;
        $icms_total->vNF        = null;
        $icms_total->vTotTrib   = null;
        $nfe->tagICMSTot($icms_total);

        // ----------- Tag TRANSPORTE ------------- //
        $transporte           = new stdClass();
        if($dados_da_nota['id_transportadora'] != 0) : // Caso seja diferente de zero então o usuário escolheu uma transportadora
            $transporte->modFrete = 1; // 1=com transporte, 9=Sem transporte
        else:
            $transporte->modFrete = 9; // 1=com transporte, 9=Sem transporte
        endif;
        $nfe->tagtransp($transporte);

        if($dados_da_nota['id_transportadora'] != 0) : // Caso seja diferente de zero então o usuário escolheu uma transportadora
            // Dados da transportadora
            $dados_da_transportadora = $this->transportadora_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->where('id_transportadora', $dados_da_nota['id_transportadora'])
                                            ->join('ufs', 'transportadoras.id_uf = ufs.id_uf')
                                            ->join('municipios', 'transportadoras.id_municipio = municipios.id_municipio')
                                            ->first();
            
            $dados_da_transportadora['CNPJ'] = removeMascaras($dados_da_transportadora['CNPJ']);

            // dd($dados_da_nota);
            $transportadora = new stdClass();
            $transportadora->xNome  = $dados_da_transportadora['xNome'];
            
            // Verifica se a transportadora é isenta
            if($dados_da_transportadora['isento'] == 1) :
                $transportadora->IE = null;
            else:
                $transportadora->IE = $dados_da_transportadora['IE'];
            endif;

            $transportadora->xEnder = $dados_da_transportadora['xEnder'];
            $transportadora->xMun   = $dados_da_transportadora['municipio'];
            $transportadora->UF     = $dados_da_transportadora['uf'];
            $transportadora->CNPJ   = $dados_da_transportadora['CNPJ'];//só pode haver um ou CNPJ ou CPF, se um deles é especificado o outro deverá ser null
            $transportadora->CPF    = null;
            $nfe->tagtransporta($transportadora);

            // Pega os dados da Unidade
            $unidade = $this->unidade_model
                            ->where('id_unidade', $dados_da_nota['id_unidade'])
                            ->first();

            $volume = new stdClass();
            $volume->item  = 1; //indicativo do numero do volume
            $volume->qVol  = $dados_da_nota['qtdVol'];
            $volume->esp   = $unidade['unidade'];
            // $volume->marca = 'OLX';
            // $volume->nVol = '1250';
            $volume->pesoL = $dados_da_nota['qtdLiq'];
            $volume->pesoB = $dados_da_nota['pBruto'];
            $nfe->tagvol($volume);
        endif;
        
        // ----------- Tag PAGAMENTO ------------- //
        $pagamento         = new stdClass();
        $pagamento->vTroco = format($dados_da_nota['troco']);
        $nfe->tagpag($pagamento);

        // -- Tipo de pagamento -- //
        $tipo_de_pagamento            = new stdClass();
        $tipo_de_pagamento->tPag      = $dados_da_nota['tipo_de_pagamento']; // 01=Dinheiro, 02=Cheque, 03=Cartão de Crédito ...
        $tipo_de_pagamento->vPag      = format($dados_da_nota['valor_da_nota']); //Obs: deve ser informado o valor total da nota
        $tipo_de_pagamento->indPag    = $dados_da_nota['forma_de_pagamento']; //0= Pagamento à Vista 1= Pagamento à Prazo
        $nfe->tagdetPag($tipo_de_pagamento);

        // ----------- Tag RESPONSÁVEL TÉCNICO ------------- // 
        $responsavel_tecnico           = new stdClass();
        $responsavel_tecnico->CNPJ     = "34229323000173"; //CNPJ da pessoa jurídica responsável pelo sistema utilizado na emissão do documento fiscal eletrônico
        $responsavel_tecnico->xContato = "NX SISTEMAS"; //Nome da pessoa a ser contatada
        $responsavel_tecnico->email    = "nxsistemas@gmail.com"; //E-mail da pessoa jurídica a ser contatada
        $responsavel_tecnico->fone     = "63992127726"; //Telefone da pessoa jurídica/física a ser contatada
        $responsavel_tecnico->CSRT     = ''; //Código de Segurança do Responsável Técnico
        $responsavel_tecnico->idCSRT   = '0'; //Identificador do CSRT
        $nfe->taginfRespTec($responsavel_tecnico);

        
        // ----------- Tag INFORMAÇÕES ADICIONAIS --------- //
        if($dados_da_nota['informacoes_complementares'] != "" || $dados_da_nota['infomacoes_para_fisco']) :

            if($dados_da_nota['informacoes_complementares'] != "" && $dados_da_nota['infomacoes_para_fisco'] != "") :
                
                $std_informacoes_adicionais = new stdClass();
                $std_informacoes_adicionais->infAdFisco = $dados_da_nota['infomacoes_para_fisco'];
                $std_informacoes_adicionais->infCpl     = $dados_da_nota['informacoes_complementares'];
                $nfe->taginfAdic($std_informacoes_adicionais);

            elseif($dados_da_nota['informacoes_complementares'] != "") :

                $std_informacoes_adicionais = new stdClass();
                $std_informacoes_adicionais->infAdFisco = null;
                $std_informacoes_adicionais->infCpl     = $dados_da_nota['informacoes_complementares'];
                $nfe->taginfAdic($std_informacoes_adicionais);

            elseif($dados_da_nota['infomacoes_para_fisco'] != "") :

                $std_informacoes_adicionais = new stdClass();
                $std_informacoes_adicionais->infAdFisco = $dados_da_nota['infomacoes_para_fisco'];
                $std_informacoes_adicionais->infCpl     = null;
                $nfe->taginfAdic($std_informacoes_adicionais);

            endif;

        endif;

        // Verifica se todos os campos foram preenchidos corretamente e depois gera o XML
        try
        {
            return $nfe; // Retorna a instância da nota
        }
        catch (\Exception $e)
        {
            $erros = $nfe->getErrors();
            exit($nfe->getErrors());
        }
    }

    public function montaXmlDevolucao($dados_do_emitente, $dados_da_nota, $dados_do_destinatario, $produtos_da_nota)
    {
        $nfe = new Make();

        // Verifica se todos os campos foram preenchidos corretamente e depois gera o XML
        try
        {
            // ----------- Tag INFORMAÇÕES ------------- //
            $inf           = new stdClass();
            $inf->versao   = '4.00'; //versão do layout (string)
            $inf->Id       = null; //se o Id de 44 digitos não for passado será gerado automaticamente
            $inf->pk_nItem = null; //deixe essa variavel sempre como NULL
            $nfe->taginfNFe($inf);

            // ----------- Tag IDE ------------- //
            $ide           = new stdClass();
            $ide->cUF      = $dados_do_emitente['codigo_uf'];
            $ide->cNF      = rand(1, 99999999);
            $ide->natOp    = $dados_da_nota['natureza_da_operacao'];
            // $ide->indPag   = 0; //NÃO EXISTE MAIS NA VERSÃO 4.00
            $ide->mod      = 55;
            $ide->serie    = $dados_do_emitente['serie'];

            if($dados_do_emitente['tpAmb_NFe'] == 1) :
                $numero_da_nf = $dados_do_emitente['nNF_producao'];
            else:
                $numero_da_nf = $dados_do_emitente['nNF_homologacao'];
            endif;

            $ide->nNF      = $numero_da_nf;
            $ide->dhEmi    = $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP"); //date('Y-m-d\TH:i:sP');
            $ide->dhSaiEnt = $dados_da_nota['data'].'T'.date("{$dados_da_nota['hora']}:sP"); //date('Y-m-d\TH:i:sP');
            $ide->tpNF     = 1; // Tipo de operação: 0-entrada, 1-saida
            
            if($dados_do_emitente['uf'] != $dados_do_destinatario['uf']) :
                $ide->idDest = 2; // Estadual=1, Interestadual=2
            else:
                $ide->idDest   = 1; // Estadual=1, Interestadual=2
            endif;

            $ide->cMunFG   = $dados_do_emitente['codigo'];
            $ide->tpImp    = 1;
            $ide->tpEmis   = 1;
            $ide->cDV      = 0;
            $ide->tpAmb    = $dados_do_emitente['tpAmb_NFe'];
            $ide->finNFe   = 4;
            $ide->indFinal = 1;
            $ide->indPres  = 1;
            $ide->procEmi  = 0;
            $ide->verProc  = "xF 1.0.1";
            $ide->dhCont   = null;
            $ide->xJust    = null;
            $nfe->tagide($ide);

            // ----------- Tag Referencia da Nota --------------- //
            $nota_referenciada = new stdClass();
            $nota_referenciada->refNFe = removeMascaras($dados_da_nota['chave']);
            $nfe->tagrefNFe($nota_referenciada);

            // ----------- Tag EMITENTE ------------- //
            // -- Emitente -- //
            $emitente        = new stdClass();
            $emitente->CNPJ  = $dados_do_emitente['CNPJ'];
            $emitente->xNome = $dados_do_emitente['xNome'];
            $emitente->xFant = $dados_do_emitente['xFant'];
            $emitente->IE    = $dados_do_emitente['IE'];
            $emitente->CRT   = 1; // 1=Simples Nacional
            $nfe->tagemit($emitente);

            // -- Endereço do emitente -- //
            $endereco_do_emitente          = new stdClass();
            $endereco_do_emitente->xLgr    = $dados_do_emitente['xLgr'];
            $endereco_do_emitente->nro     = $dados_do_emitente['nro'];
            $endereco_do_emitente->xCpl    = $dados_do_emitente['xCpl'];
            $endereco_do_emitente->xBairro = $dados_do_emitente['xBairro'];
            $endereco_do_emitente->cMun    = $dados_do_emitente['codigo'];
            $endereco_do_emitente->xMun    = $dados_do_emitente['municipio'];
            $endereco_do_emitente->UF      = $dados_do_emitente['uf'];
            $endereco_do_emitente->CEP     = $dados_do_emitente['CEP'];
            $endereco_do_emitente->cPais   = "1058";
            $endereco_do_emitente->xPais   = "BRASIL";
            $endereco_do_emitente->fone    = $dados_do_emitente['fone'];
            $nfe->tagenderEmit($endereco_do_emitente);

            // ----------- Tag DESTINATÁRIO ------------- //
            // -- Destinatário -- //
            if($dados_do_destinatario['tipo'] == 1) // Caso seja pessoa física
            {
                $destinatario = new stdClass();
                $destinatario->CPF       = $dados_do_destinatario['cpf'];
                $destinatario->xNome     = $dados_do_destinatario['nome'];
                
                if($dados_do_destinatario['isento'] == 1) :
                    
                    $destinatario->indIEDest = 2; // 1=Não Isento, 2=Isento

                else:

                    $destinatario->indIEDest = 1; // 1=Não Isento, 2=Isento
                    $destinatario->IE = $dados_do_destinatario['ie'];

                endif;
            }
            else // Caso seja pessoa juridica
            {
                $destinatario        = new stdClass();
                $destinatario->CNPJ  = $dados_do_destinatario['cnpj'];
                $destinatario->xNome = $dados_do_destinatario['razao_social'];

                if($dados_do_destinatario['isento'] == 1) :
                    
                    $destinatario->indIEDest = 2; // 1=Não Isento, 2=Isento

                else:

                    $destinatario->indIEDest = 1; // 1=Não Isento, 2=Isento
                    $destinatario->IE = $dados_do_destinatario['ie'];

                endif;
            }

            $nfe->tagdest($destinatario);


            // -- Endereço do destinatário -- //
            $endereco_do_destinatario = new stdClass();
            $endereco_do_destinatario->xLgr = $dados_do_destinatario['logradouro'];
            
            if($dados_do_destinatario['numero'] == "" || $dados_do_destinatario['numero'] == 0)
            {
                $endereco_do_destinatario->nro = "S/N"; 
            }
            else
            {
                $endereco_do_destinatario->nro = $dados_do_destinatario['numero'];
            }

            $endereco_do_destinatario->xCpl    = $dados_do_destinatario['complemento'];
            $endereco_do_destinatario->xBairro = $dados_do_destinatario['bairro'];
            $endereco_do_destinatario->cMun    = $dados_do_destinatario['codigo']; // Código do municipio
            $endereco_do_destinatario->xMun    = $dados_do_destinatario['municipio']; // Nome do municipio
            $endereco_do_destinatario->UF      = $dados_do_destinatario['uf'];
            $endereco_do_destinatario->CEP     = $dados_do_destinatario['cep'];
            $endereco_do_destinatario->cPais   = '1058';
            $endereco_do_destinatario->xPais   = 'BRASIL';
            $nfe->tagenderDest($endereco_do_destinatario);

            // ------------------------------------------- TAG PRODUTOS ---------------------------------------------- //
            $i = 0;
            foreach ($produtos_da_nota as $produto) {
                $i += 1;

                // ----------- Tag PRODUTOS ------------- //
                $std_produto         = new \stdClass();
                $std_produto->item   = $i;
                $std_produto->cProd  = $produto['id_produto'];

                // Verifica e configura o código de barras
                if($produto['codigo_de_barras'] == 0 || $produto['codigo_de_barras'] == "")
                {
                    $codigo_de_barras = "SEM GTIN"; // Caso o produto não possua código de barras
                }
                else
                {
                    $codigo_de_barras = $produto['codigo_de_barras']; // Caso possua
                }

                $std_produto->cEAN   = $codigo_de_barras;
                $std_produto->xProd  = $produto['nome'];
                $std_produto->NCM    = $produto['NCM'];
                $std_produto->CFOP   = $produto['CFOP_NFe'];
                $std_produto->uCom   = $produto['unidade'];
                $std_produto->qCom   = $produto['quantidade']; // QUANTIDADE COMPRADA -----------------------------------------------------------
                $std_produto->vUnCom = format($produto['valor_unitario']); // COLOCAR O VALOR UNITÁRIO DO PRODUTO --------------------------------------------------------------

                if($produto['desconto'] != 0) // CASO HAJA DESCONTO NO PRODUTO INSERIDO AUTOMATICAMENTE CONDIÇÃO = DIFENTE DE ZERO
                {
                    $std_produto->vDesc  = format($produto['desconto']); // DESCONTO DO PRODUTO
                }

                $subtotal = format($produto['valor_unitario']) * $produto['quantidade'];

                $std_produto->vProd    = format($subtotal); // COLOCAR O VALOR TOTAL QTDxVALOR.UNITARIO --------------------------------------------------------
                $std_produto->cEANTrib = $codigo_de_barras;
                $std_produto->uTrib    = $produto['unidade'];
                $std_produto->qTrib    = $produto['quantidade']; // QUANTIDADE A SER TRIBUTADA ----------------------------------------------------------------------------
                $std_produto->vUnTrib  = format($produto['valor_unitario']); // COLOCAR O VALOR DA UNIDADE -----------------------------------------------------------------------
                $std_produto->indTot   = 1; // Indica se o valor do item (vProd) entra no total da NF-e. 0-não compoe, 1 compoe
                $nfe->tagprod($std_produto);

                // -- Tag imposto -- //
                $std_imposto       = new \stdClass();
                $std_imposto->item = $i;
                $nfe->tagimposto($std_imposto);

                // -- Tag ICMS -- //
                $std_icms       = new \stdClass();
                $std_icms->item = $i;
                $nfe->tagICMS($std_icms);

                // -- Tag ICMSSN -- //
                $std_icmssm                  = new stdClass();
                $std_icmssm->item            = $i; //item da NFe
                $std_icmssm->orig            = 0;
                $std_icmssm->CSOSN           = $produto['CSOSN'];
                $std_icmssm->pCredSN         = '0.00';
                $std_icmssm->vCredICMSSN     = '0.00';
                $std_icmssm->modBCST         = null;
                $std_icmssm->pMVAST          = null;
                $std_icmssm->pRedBCST        = null;
                $std_icmssm->vBCST           = null;
                $std_icmssm->pICMSST         = null;
                $std_icmssm->vICMSST         = null;
                $std_icmssm->vBCFCPST        = null; //incluso no layout 4.00
                $std_icmssm->pFCPST          = null; //incluso no layout 4.00
                $std_icmssm->vFCPST          = null; //incluso no layout 4.00
                $std_icmssm->vBCSTRet        = null;
                $std_icmssm->pST             = null;
                $std_icmssm->vICMSSTRet      = null;
                $std_icmssm->vBCFCPSTRet     = null; //incluso no layout 4.00
                $std_icmssm->pFCPSTRet       = null; //incluso no layout 4.00
                $std_icmssm->vFCPSTRet       = null; //incluso no layout 4.00
                $std_icmssm->modBC           = null;
                $std_icmssm->vBC             = null;
                $std_icmssm->pRedBC          = null;
                $std_icmssm->pICMS           = null;
                $std_icmssm->vICMS           = null;
                $std_icmssm->pRedBCEfet      = null;
                $std_icmssm->vBCEfet         = null;
                $std_icmssm->pICMSEfet       = null;
                $std_icmssm->vICMSEfet       = null;
                $std_icmssm->vICMSSubstituto = null;
                $nfe->tagICMSSN($std_icmssm);

                // -- Tag PIS -- //
                $std_pis = new \stdClass();
                $std_pis->item = $i;
                $std_pis->CST  = '01';
                $std_pis->vBC  = '0.00';
                $std_pis->pPIS = '0.00';
                $std_pis->vPIS = '0.00';
                $nfe->tagPIS($std_pis);

                // -- COFINS -- //
                $std_cofins             = new \stdClass();
                $std_cofins->item       = $i;
                $std_cofins->CST        = '01';
                $std_cofins->vBC        = '0.00';
                $std_cofins->pCOFINS    = '0.0000';
                $std_cofins->vCOFINS    = '00.0';
                // $std_cofins->qBCProd = 0;
                $std_cofins->vAliqProd  = 0;
                $nfe->tagCOFINS($std_cofins);
            }

            // ----------- Tag ICMS TOTAL ------------- //
            $icms_total             = new stdClass();
            $icms_total->vBC        = null;
            $icms_total->vICMS      = null;
            $icms_total->vICMSDeson = null;
            $icms_total->vFCP       = null;
            $icms_total->vBCST      = null;
            $icms_total->vST        = null;
            $icms_total->vFCPST     = null;
            $icms_total->vFCPSTRet  = null;
            $icms_total->vProd      = null;
            $icms_total->vFrete     = null;
            $icms_total->vSeg       = null;
            $icms_total->vDesc      = null;
            $icms_total->vII        = null;
            $icms_total->vIPI       = null;
            $icms_total->vIPIDevol  = null;
            $icms_total->vPIS       = null;
            $icms_total->vCOFINS    = null;
            $icms_total->vOutro     = null;
            $icms_total->vNF        = null;
            $icms_total->vTotTrib   = null;
            $nfe->tagICMSTot($icms_total);

            // ----------- Tag TRANSPORTE ------------- //
            $transporte           = new stdClass();
            if($dados_da_nota['id_transportadora'] != 0) : // Caso seja diferente de zero então o usuário escolheu uma transportadora
                $transporte->modFrete = 1; // 1=com transporte, 9=Sem transporte
            else:
                $transporte->modFrete = 9; // 1=com transporte, 9=Sem transporte
            endif;
            $nfe->tagtransp($transporte);

            if($dados_da_nota['id_transportadora'] != 0) : // Caso seja diferente de zero então o usuário escolheu uma transportadora
                // Dados da transportadora
                $dados_da_transportadora = $this->transportadora_model
                                                ->where('id_transportadora', $dados_da_nota['id_transportadora'])
                                                ->join('ufs', 'transportadoras.id_uf = ufs.id_uf')
                                                ->join('municipios', 'transportadoras.id_municipio = municipios.id_municipio')
                                                ->first();
                
                $dados_da_transportadora['CNPJ'] = removeMascaras($dados_da_transportadora['CNPJ']);

                // dd($dados_da_nota);
                $transportadora = new stdClass();
                $transportadora->xNome  = $dados_da_transportadora['xNome'];
                
                // Verifica se a transportadora é isenta
                if($dados_da_transportadora['isento'] == 1) :
                    $transportadora->IE = null;
                else:
                    $transportadora->IE = $dados_da_transportadora['IE'];
                endif;

                $transportadora->xEnder = $dados_da_transportadora['xEnder'];
                $transportadora->xMun   = $dados_da_transportadora['municipio'];
                $transportadora->UF     = $dados_da_transportadora['uf'];
                $transportadora->CNPJ   = $dados_da_transportadora['CNPJ'];//só pode haver um ou CNPJ ou CPF, se um deles é especificado o outro deverá ser null
                $transportadora->CPF    = null;
                $nfe->tagtransporta($transportadora);

                // Pega os dados da Unidade
                $unidade = $this->unidade_model
                                ->where('id_unidade', $dados_da_nota['id_unidade'])
                                ->first();

                $volume = new stdClass();
                $volume->item  = 1; //indicativo do numero do volume
                $volume->qVol  = $dados_da_nota['qtdVol'];
                $volume->esp   = $unidade['unidade'];
                // $volume->marca = 'OLX';
                // $volume->nVol = '1250';
                $volume->pesoL = $dados_da_nota['qtdLiq'];
                $volume->pesoB = $dados_da_nota['pBruto'];
                $nfe->tagvol($volume);
            endif;

            // ----------- Tag PAGAMENTO ------------- //
            $pagamento         = new stdClass();
            $pagamento->vTroco = null;
            $nfe->tagpag($pagamento);

            // -- Tipo de pagamento -- //
            $tipo_de_pagamento            = new stdClass();
            $tipo_de_pagamento->tPag      = '90'; // 90=Sem Pagamento
            $tipo_de_pagamento->vPag      = '0.00'; //Obs: deve ser informado o valor total da nota
            $tipo_de_pagamento->indPag    = '0'; //0= Pagamento à Vista 1= Pagamento à Prazo
            $nfe->tagdetPag($tipo_de_pagamento);

            // ----------- Tag RESPONSÁVEL TÉCNICO ------------- // 
            $responsavel_tecnico           = new stdClass();
            $responsavel_tecnico->CNPJ     = "34229323000173"; //CNPJ da pessoa jurídica responsável pelo sistema utilizado na emissão do documento fiscal eletrônico
            $responsavel_tecnico->xContato = "NX SISTEMAS"; //Nome da pessoa a ser contatada
            $responsavel_tecnico->email    = "nxsistemas@gmail.com"; //E-mail da pessoa jurídica a ser contatada
            $responsavel_tecnico->fone     = "63992069830"; //Telefone da pessoa jurídica/física a ser contatada
            $responsavel_tecnico->CSRT     = ''; //Código de Segurança do Responsável Técnico
            $responsavel_tecnico->idCSRT   = '0'; //Identificador do CSRT
            $nfe->taginfRespTec($responsavel_tecnico);


            // ----------- Tag Informações Adicionais --------- //
            if($dados_da_nota['informacoes_complementares'] != "" || $dados_da_nota['infomacoes_para_fisco']) :

                if($dados_da_nota['informacoes_complementares'] != "" && $dados_da_nota['infomacoes_para_fisco'] != "") :
                    
                    $std_informacoes_adicionais = new stdClass();
                    $std_informacoes_adicionais->infAdFisco = $dados_da_nota['infomacoes_para_fisco'];
                    $std_informacoes_adicionais->infCpl     = $dados_da_nota['informacoes_complementares'];
                    $nfe->taginfAdic($std_informacoes_adicionais);

                elseif($dados_da_nota['informacoes_complementares'] != "") :

                    $std_informacoes_adicionais = new stdClass();
                    $std_informacoes_adicionais->infAdFisco = null;
                    $std_informacoes_adicionais->infCpl     = $dados_da_nota['informacoes_complementares'];
                    $nfe->taginfAdic($std_informacoes_adicionais);

                elseif($dados_da_nota['infomacoes_para_fisco'] != "") :

                    $std_informacoes_adicionais = new stdClass();
                    $std_informacoes_adicionais->infAdFisco = $dados_da_nota['infomacoes_para_fisco'];
                    $std_informacoes_adicionais->infCpl     = null;
                    $nfe->taginfAdic($std_informacoes_adicionais);

                endif;

            endif;

            return $nfe; // Retorna a instância da nota
        }
        catch (\Exception $e)
        {
            exit($nfe->getErrors());
        }
    }

    public function preparaConfigJson($dados_do_emitente)
    {
        // ------------------------------------------------------------ CONFIG
        $config  = [
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb"       => intval($dados_do_emitente['tpAmb_NFe']),
            "razaosocial" => $dados_do_emitente['xNome'],
            "cnpj"        => $dados_do_emitente['CNPJ'], // PRECISA SER VÁLIDO
            "ie"          => $dados_do_emitente['IE'], // PRECISA SER VÁLIDO
            "siglaUF"     => $dados_do_emitente['uf'],
            "schemes"     => "PL_009_V4",
            "versao"      => '4.00',
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => "AD6A9D2E-3F93-437F-BE5B-E8FA800A08F4",
            "CSCid"       => "000001"
        ];

        return json_encode($config);
    }

    public function assinaXML($dados_do_emitente, $config_json, $xml)
    {
        /*---------------------------------------------------------------------------------------------------------------------------------------*/
        $arq_certificado     = WRITEPATH . "uploads/certificados/" . $dados_do_emitente['certificado'];
        $certificado_digital = file_get_contents($arq_certificado);

        $this->tools = new Tools(
            $config_json,
            Certificate::readPfx(
                $certificado_digital,
                $dados_do_emitente['senha_do_certificado']
            )
        );

        try
        {
            $xml_assinado = $this->tools->signNFe($xml); // O conteúdo do XML assinado fica armazenado na variável $xmlAssinado

            return $xml_assinado;
        }
        catch (\Exception $e)
        {
            $this->detalhaRejeicao($e->getMessage());
            exit();
        }
    }

    public function enviaLoteParaSefaz($xml_assinado)
    {
        try
        {
            $id_lote = str_pad(100, 15, '0', STR_PAD_LEFT); // Identificador do lote
            $resp    = $this->tools->sefazEnviaLote([$xml_assinado], $id_lote);

            $st  = new Standardize();
            $std = $st->toStd($resp);
            
            if ($std->cStat != 103)
            {
                //erro registrar e voltar
                exit("[$std->cStat] $std->xMotivo");
            }
            
            $recibo = $std->infRec->nRec; // Vamos usar a variável $recibo para consultar o status da nota

            return $recibo;
        }
        catch (\Exception $e)
        {            
            $this->detalhaRejeicao($e->getMessage());
            exit();
        }
    }

    public function consultaReciboNaSefaz($numero_do_recibo)
    {
        try
        {
            $protocolo = $this->tools->sefazConsultaRecibo($numero_do_recibo);

            return $protocolo;
        }
        catch (\Exception $e)
        {
            $this->detalhaRejeicao($e->getMessage());
            exit();
        }
    }

    public function protocolaXmlNaSefaz($xml_assinado, $protocolo)
    {
        $request  = $xml_assinado;
        $response = $protocolo;

        try
        {
            $xml_protocolado = Complements::toAuthorize($request, $response);

            return $xml_protocolado;

        }
        catch (\Exception $e)
        {
            $this->detalhaRejeicao($e->getMessage());
            exit();
        }
    }

   public function emitirNotaDeSaida()
{
    $this->link = '3';

    $dados_do_emitente = $this->empresa_model
                            ->where('id_empresa', $this->id_empresa)
                            ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                            ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                            ->first();

    $dados_da_nota = $this->request->getvar();

    // Converte de BRL para USD
    $dados_da_nota['troco'] = converteMoney($dados_da_nota['troco']);

    $dados_do_destinatario = $this->cliente_model
                                ->where('id_empresa', $this->id_empresa)
                                ->where('id_cliente', $dados_da_nota['id_cliente'])
                                ->join('ufs', 'clientes.id_uf = ufs.id_uf')
                                ->join('municipios', 'clientes.id_municipio = municipios.id_municipio')
                                ->first();

    $produtos_da_nota = $this->produto_provisorio_model
                            ->where('id_empresa', $this->id_empresa)
                            ->findAll();

    // Prepara a instância da NFe antes do bloco try/catch
    $nfe = $this->montaXml($dados_do_emitente, $dados_da_nota, $dados_do_destinatario, $produtos_da_nota);

    try {
        // ETAPA 1: Tenta gerar o XML.
        $xml = $nfe->getXML();

        // ETAPA 2: PREPARA O CONFIG_JSON
        $config_json = $this->preparaConfigJson($dados_do_emitente);

        // ETAPA 3: ASSINA XML
        $xml_assinado = $this->assinaXML($dados_do_emitente, $config_json, $xml);

        // ETAPA 4: ENVIA LOTE PARA A SEFAZ
        $numero_do_recibo = $this->enviaLoteParaSefaz($xml_assinado);

        // ETAPA 5: CONSULTA RECIBO NA SEFAZ
        $protocolo = $this->consultaReciboNaSefaz($numero_do_recibo);

        // ETAPA 6: AUTORIZA USO DA NOTA - SEFAZ
        $xml_protocolado = $this->protocolaXmlNaSefaz($xml_assinado, $protocolo);

        // ETAPA 7: LÓGICA DE SUCESSO
        if ($dados_do_emitente['tpAmb_NFe'] == 1) {
            $nova_nNF = $dados_do_emitente['nNF_producao'] + 1;
            $this->empresa_model
                ->set('nNF_producao', $nova_nNF)
                ->where('id_empresa', $this->id_empresa)
                ->update();
            $guarda_numero_da_nota = $dados_do_emitente['nNF_producao'];
        } else {
            $nova_nNF = $dados_do_emitente['nNF_homologacao'] + 1;
            $this->empresa_model
                ->set('nNF_homologacao', $nova_nNF)
                ->where('id_empresa', $this->id_empresa)
                ->update();
            $guarda_numero_da_nota = $dados_do_emitente['nNF_homologacao'];
        }

        // Salva os dados da NFe no banco de dados
        $id_nfe = $this->nfe_model->insert([
            'numero'          => $guarda_numero_da_nota,
            'chave'           => $nfe->getChave(),
            'valor_da_nota'   => $dados_da_nota['valor_da_nota'],
            'data'            => $dados_da_nota['data'],
            'hora'            => $dados_da_nota['hora'],
            'xml'             => $xml_protocolado,
            'protocolo'       => $protocolo,
            'status'          => "Emitida",
            'tipo'            => 2, // 2=Saída
            'id_contador'     => $this->id_contador,
            'id_empresa'      => $this->id_empresa
        ]);

        // Credita estoque automaticamente e registra movimentos
        try {
            $produtoModel = new \App\Models\ProdutoModel();
            $movModel = new \App\Models\InventoryMovementModel();
            $creditos = [];
            foreach ($produtos_da_nota as $p) {
                if (!isset($p['id_produto'])) { continue; }
                $qtd = (float) ($p['quantidade'] ?? 0);
                if ($qtd <= 0) { continue; }
                $creditos[] = [ 'id_produto' => (int) $p['id_produto'], 'quantidade' => $qtd ];
                $movModel->insert([
                    'id_produto'  => (int) $p['id_produto'],
                    'tipo'        => 'entrada',
                    'quantidade'  => $qtd,
                    'motivo'      => 'Nota de Entrada #' . $guarda_numero_da_nota,
                    'id_contador' => $this->id_contador,
                    'id_empresa'  => $this->id_empresa,
                ]);
            }
            if (!empty($creditos)) { $produtoModel->estornarEstoque($creditos); }
        } catch (\Throwable $e) {
            log_message('error', 'Falha ao creditar estoque na Nota de Entrada: ' . $e->getMessage());
        }

        // Remove todos os produtos da tabela provisória
        $this->produto_provisorio_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->delete();

        // Cria o alerta para abrir a DANFe
        $this->session->setFlashdata('id_nfe', $id_nfe);

        return redirect()->to("/notaDeSaida/emitir");

    } catch (\Exception $e) {
        // A lógica para pegar e traduzir os erros continua a mesma
    $errosTecnicos = $nfe->getErrors();

    if (empty($errosTecnicos)) {
        $errosTecnicos[] = $e->getMessage();
    }

    $mensagensParaUsuario = [];
    foreach ($errosTecnicos as $erro) {
        $mensagem = $this->traduzirErroNFe($erro);
        if (!in_array($mensagem, $mensagensParaUsuario)) {
            $mensagensParaUsuario[] = $mensagem;
        }
    }

    // Loga o erro técnico para o desenvolvedor
    log_message('error', '[ERRO NFE]: ' . print_r($errosTecnicos, true));

    // NOVO: Salva as mensagens na sessão e redireciona de volta
    $this->session->setFlashdata('error_messages', $mensagensParaUsuario);
    return redirect()->back()->withInput();
    }
}

   public function emitirNotaDeDevolucao()
{
    $this->link = '4';

    $dados_do_emitente = $this->empresa_model
                            ->where('id_empresa', $this->id_empresa)
                            ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                            ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                            ->first();

    $dados_da_nota = $this->request->getvar();

    // Remove mascara da chave
    $dados_da_nota['chave'] = removeMascaras($dados_da_nota['chave']);

    $dados_do_destinatario = $this->fornecedor_model
                                ->where('id_empresa', $this->id_empresa)
                                ->where('id_fornecedor', $dados_da_nota['id_fornecedor'])
                                ->join('ufs', 'fornecedores.id_uf = ufs.id_uf')
                                ->join('municipios', 'fornecedores.id_municipio = municipios.id_municipio')
                                ->first();

    $produtos_da_nota = $this->produto_provisorio_model
                            ->where('id_empresa', $this->id_empresa)
                            ->findAll();

    // Prepara a instância da NFe antes do bloco try/catch
    $nfe = $this->montaXmlDevolucao($dados_do_emitente, $dados_da_nota, $dados_do_destinatario, $produtos_da_nota);

    try {
        // ETAPA 1: Tenta gerar o XML.
        $xml = $nfe->getXML();

        // ETAPA 2: PREPARA O CONFIG_JSON
        $config_json = $this->preparaConfigJson($dados_do_emitente);

        // ETAPA 3: ASSINA XML
        $xml_assinado = $this->assinaXML($dados_do_emitente, $config_json, $xml);

        // ETAPA 4: ENVIA LOTE PARA A SEFAZ
        $numero_do_recibo = $this->enviaLoteParaSefaz($xml_assinado);

        // ETAPA 5: CONSULTA RECIBO NA SEFAZ
        $protocolo = $this->consultaReciboNaSefaz($numero_do_recibo);

        // ETAPA 6: AUTORIZA USO DA NOTA - SEFAZ
        $xml_protocolado = $this->protocolaXmlNaSefaz($xml_assinado, $protocolo);

        // ETAPA 7: LÓGICA DE SUCESSO
        if ($dados_do_emitente['tpAmb_NFe'] == 1) {
            $nova_nNF = $dados_do_emitente['nNF_producao'] + 1;
            $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->set('nNF_producao', $nova_nNF)
                ->update();
            $guarda_numero_da_nota = $dados_do_emitente['nNF_producao'];
        } else {
            $nova_nNF = $dados_do_emitente['nNF_homologacao'] + 1;
            $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->set('nNF_homologacao', $nova_nNF)
                ->update();
            $guarda_numero_da_nota = $dados_do_emitente['nNF_homologacao'];
        }

        // Salta os dados da NFe no banco de dados
        $id_nfe = $this->nfe_model->insert([
            'chave'           => $nfe->getChave(),
            'numero'          => $guarda_numero_da_nota,
            'valor_da_nota'   => $dados_da_nota['valor_da_nota'],
            'data'            => $dados_da_nota['data'],
            'hora'            => $dados_da_nota['hora'],
            'xml'             => $xml_protocolado,
            'protocolo'       => $protocolo,
            'status'          => "Emitida",
            'tipo'            => 3, // 3=Devolução
            'id_contador'     => $this->id_contador,
            'id_empresa'      => $this->id_empresa
        ]);

        // Remove todos os produtos da tabela provisória
        $this->produto_provisorio_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->delete();

        // Cria um alerta para imprimir a DANFe
        $this->session->setFlashdata('id_nfe', $id_nfe);

        return redirect()->to("/notaDeDevolucao/emitir");

    } catch (\Exception $e) {
        // A lógica para pegar e traduzir os erros continua a mesma
    $errosTecnicos = $nfe->getErrors();

    if (empty($errosTecnicos)) {
        $errosTecnicos[] = $e->getMessage();
    }

    $mensagensParaUsuario = [];
    foreach ($errosTecnicos as $erro) {
        $mensagem = $this->traduzirErroNFe($erro);
        if (!in_array($mensagem, $mensagensParaUsuario)) {
            $mensagensParaUsuario[] = $mensagem;
        }
    }

    // Loga o erro técnico para o desenvolvedor
    log_message('error', '[ERRO NFE]: ' . print_r($errosTecnicos, true));

    // NOVO: Salva as mensagens na sessão e redireciona de volta
    $this->session->setFlashdata('error_messages', $mensagensParaUsuario);
    return redirect()->back()->withInput();
    }
}
public function emitirNotaDeEntrada()
{
    $this->link = '2';

    $dados_do_emitente = $this->empresa_model
                            ->where('id_empresa', $this->id_empresa)
                            ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                            ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                            ->first();

    $dados_da_nota = $this->request->getvar();

    $produtos_da_nota = $this->produto_provisorio_model
                            ->where('id_empresa', $this->id_empresa)
                            ->findAll();

    // Prepara a instância da NFe
    $nfe = $this->montaXmlEntrada($dados_do_emitente, $dados_da_nota, $produtos_da_nota);

    try {
        // ETAPA 1: Tenta gerar o XML. Se falhar, pula direto para o bloco "catch".
        $xml = $nfe->getXML();

        // Se o XML foi gerado com SUCESSO, o código continua executando as próximas etapas.

        // ETAPA 2: PREPARA O CONFIG_JSON
        $config_json = $this->preparaConfigJson($dados_do_emitente);

        // ETAPA 3: ASSINA XML
        $xml_assinado = $this->assinaXML($dados_do_emitente, $config_json, $xml);

        // ETAPA 4: ENVIA LOTE PARA A SEFAZ
        $numero_do_recibo = $this->enviaLoteParaSefaz($xml_assinado);

        // ETAPA 5: CONSULTA RECIBO NA SEFAZ
        $protocolo = $this->consultaReciboNaSefaz($numero_do_recibo);

        // ETAPA 6: AUTORIZA USO DA NOTA - SEFAZ
        $xml_protocolado = $this->protocolaXmlNaSefaz($xml_assinado, $protocolo);
        
        // ETAPA 7: LÓGICA DE SUCESSO (AGORA DENTRO DO TRY)
        
        // Adiciona +1 no número da nota
        if($dados_do_emitente['tpAmb_NFe'] == 1) {
            $nova_nNF = $dados_do_emitente['nNF_producao'] + 1;
            $this->empresa_model
                ->set('nNF_producao', $nova_nNF)
                ->where('id_empresa', $this->id_empresa)
                ->update();
            $guarda_numero_da_nota = $dados_do_emitente['nNF_producao'];
        } else {
            $nova_nNF = $dados_do_emitente['nNF_homologacao'] + 1;
            $this->empresa_model
                ->set('nNF_homologacao', $nova_nNF)
                ->where('id_empresa', $this->id_empresa)
                ->update();
            $guarda_numero_da_nota = $dados_do_emitente['nNF_homologacao'];
        }

        // Salva os dados da NFe no banco de dados
        $id_nfe = $this->nfe_model->insert([
            'numero'          => $guarda_numero_da_nota,
            'chave'           => $nfe->getChave(),
            'valor_da_nota'   => $dados_da_nota['valor_da_nota'],
            'data'            => $dados_da_nota['data'],
            'hora'            => $dados_da_nota['hora'],
            'xml'             => $xml_protocolado,
            'protocolo'       => $protocolo,
            'status'          => "Emitida",
            'tipo'            => 1, // 1=Entrada, 2=Saída, 3=Devolução
            'id_contador'     => $this->id_contador,
            'id_empresa'      => $this->id_empresa
        ]);

        // Remove todos os produtos da tabela provisória
        $this->produto_provisorio_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->delete();
        
        // Cria o alerta para abrir a DANFe
        $this->session->setFlashdata('id_nfe', $id_nfe);

        return redirect()->to("/notaDeEntrada/emitir");

    } catch (\Exception $e) {
       // A lógica para pegar e traduzir os erros continua a mesma
    $errosTecnicos = $nfe->getErrors();

    if (empty($errosTecnicos)) {
        $errosTecnicos[] = $e->getMessage();
    }

    $mensagensParaUsuario = [];
    foreach ($errosTecnicos as $erro) {
        $mensagem = $this->traduzirErroNFe($erro);
        if (!in_array($mensagem, $mensagensParaUsuario)) {
            $mensagensParaUsuario[] = $mensagem;
        }
    }

    // Loga o erro técnico para o desenvolvedor
    log_message('error', '[ERRO NFE]: ' . print_r($errosTecnicos, true));

    // NOVO: Salva as mensagens na sessão e redireciona de volta
    $this->session->setFlashdata('error_messages', $mensagensParaUsuario);
    return redirect()->back()->withInput();
    }
}

/**
 * Traduz um erro técnico da biblioteca NFePHP para uma mensagem amigável ao usuário.
 * Este método deve estar DENTRO da classe NFe.
 *
 * @param string $erroTecnico A mensagem de erro original da biblioteca.
 * @return string A mensagem traduzida e pronta para o usuário.
 */
/**
 * Traduz um erro técnico da biblioteca NFePHP para uma mensagem amigável ao usuário.
 * Este método deve estar DENTRO da classe NFe.
 *
 * @param string $erroTecnico A mensagem de erro original da biblioteca.
 * @return string A mensagem traduzida e pronta para o usuário.
 */
private function traduzirErroNFe(string $erroTecnico): string
{
    // Tenta extrair o número do item do erro, se houver
    preg_match('/item:? (\d+)/', $erroTecnico, $matches);
    $item = !empty($matches[1]) ? " (Item {$matches[1]})" : '';

    // --- ERROS DE DADOS GERAIS DA NOTA ---
    // CORRIGIDO: Agora verifica as duas formas de escrita da palavra "Série"
    if (strpos($erroTecnico, 'Serie do Documento Fiscal') !== false || strpos($erroTecnico, 'Série do Documento Fiscal') !== false) {
        return "<b>Dados da Nota:</b> O campo 'Série' da nota fiscal é obrigatório. Verifique as configurações do emissor.";
    }

    // Erro de Duplicidade de Nota
    if (strpos($erroTecnico, 'Duplicidade de NF-e') !== false) {
        return "<b>Dados da Nota:</b> Esta nota fiscal (mesmo número, série e CNPJ) já foi enviada e autorizada pela SEFAZ. Você não pode emitir a mesma nota duas vezes.";
    }

    // --- ERROS DE DESTINATÁRIO ---
    if (strpos($erroTecnico, 'dest/enderDest/nro') !== false) {
        return "<b>Cadastro do Cliente:</b> O campo 'Número' do endereço do destinatário não foi preenchido.";
    }
    if (strpos($erroTecnico, 'dest/enderDest/cMun') !== false) {
        return "<b>Cadastro do Cliente:</b> O 'Código do Município' do destinatário não é válido. Verifique o cadastro.";
    }
    if (strpos($erroTecnico, 'dest/enderDest') !== false) {
        return "<b>Cadastro do Cliente:</b> Há um erro no endereço do destinatário (rua, bairro, cidade, CEP, etc). Por favor, revise o cadastro completo.";
    }
    if (strpos($erroTecnico, 'dest/') !== false) {
        return "<b>Cadastro do Cliente:</b> Há um erro geral no cadastro do destinatário. Verifique todos os campos.";
    }

    // --- ERROS DE PRODUTO ---
    if (strpos($erroTecnico, 'prod / NCM') !== false || strpos($erroTecnico, 'NCM invalido') !== false) {
        return "<b>Produto{$item}:</b> O código NCM do produto é obrigatório e deve ter 8 dígitos. Verifique o cadastro do produto.";
    }
    if (strpos($erroTecnico, 'CFOP') !== false) {
        return "<b>Produto{$item}:</b> O CFOP informado não é válido para a operação.";
    }
    if (strpos($erroTecnico, 'Unidade Comercial do produto') !== false || strpos($erroTecnico, 'uCom') !== false) {
        return "<b>Produto{$item}:</b> A 'Unidade Comercial' do produto (ex: UN, CX, PC) não foi preenchida. Verifique o cadastro do produto.";
    }
    if (strpos($erroTecnico, 'Unidade Tributável do produto') !== false || strpos($erroTecnico, 'uTrib') !== false) {
        return "<b>Produto{$item}:</b> A 'Unidade Tributável' do produto (ex: UN, CX, PC) não foi preenchida. Verifique o cadastro do produto.";
    }
    if (strpos($erroTecnico, 'cEAN') !== false) {
        return "<b>Produto{$item}:</b> O Código de Barras (GTIN) informado é inválido. Ele deve ter 8, 12, 13 ou 14 números, ou ser preenchido como 'SEM GTIN'.";
    }

    // --- ERROS DE PAGAMENTO ---
    if (strpos($erroTecnico, 'pag/detPag') !== false) {
        return "<b>Pagamento:</b> A forma ou o valor do pagamento não foi informado corretamente.";
    }
    if (strpos($erroTecnico, 'Impossível ler o certificado') !== false || strpos($erroTecnico, 'asn1 encoding routines') !== false) {
    return "<b>Certificado Digital:</b> Não foi possível ler o arquivo do Certificado Digital. Verifique se a <strong>senha do certificado</strong> está correta no cadastro da empresa e se o arquivo do certificado não está corrompido ou inválido.";
}
    // --- MENSAGEM GENÉRICA (PLANO B) ---
    // Se o erro não for nenhum dos acima, ele cairá aqui.
    log_message('warning', 'Erro de NFe não traduzido: ' . $erroTecnico);
    return "Foi encontrado um problema nos dados da nota que não pôde ser identificado automaticamente. Por favor, revise todos os campos ou contate o suporte. (Detalhe técnico: {$erroTecnico})";
}

public function cancelar()
{
    // Dados
    $id_nfe = $this->request->getvar('id_nfe');
    $justificativa = $this->request->getvar('justificativa');
    $nfe = $this->nfe_model
                ->where('id_empresa', $this->id_empresa)
                ->where('id_nfe', $id_nfe)
                ->first();
    
    // Pega o numero do protocolo da XML
    $string_1 = explode('<nProt>', $nfe['protocolo']);
    $string_2 = explode('</nProt>', $string_1[1]);
    $num_do_protocolo = $string_2[0];

    try {
        $dados_do_emitente = $this->empresa_model
                                ->where('id_empresa', $this->id_empresa)
                                ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                                ->first();
        $configJson = $this->preparaConfigJson($dados_do_emitente);
        
        $arq_certificado = WRITEPATH . "uploads/certificados/" . $dados_do_emitente['certificado'];
        $certificado_digital = file_get_contents($arq_certificado);
        
        $certificate = Certificate::readPfx($certificado_digital, $dados_do_emitente['senha_do_certificado']);
        $tools = new Tools($configJson, $certificate);
        $tools->model('55');

        $response = $tools->sefazCancela($nfe['chave'], $justificativa, $num_do_protocolo);
        $stdCl = new Standardize($response);
        $std = $stdCl->toStd();
        
        if ($std->cStat != 128) {
            $this->session->setFlashdata('alert', ['type' => 'danger', 'title' => "Erro: {$std->xMotivo}"]);
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
                $xml = Complements::toAuthorize($tools->lastRequest, $response);
                $this->nfe_model->save(['id_nfe' => $id_nfe, 'xml' => $xml, 'status' => 'Cancelada']);
                $this->session->setFlashdata('alert', ['type' => 'success', 'title' => 'Nota cancelada com sucesso!']);
            } else {
                $this->session->setFlashdata('alert', ['type' => 'danger', 'title' => "Erro ao cancelar: {$std->retEvento->infEvento->xMotivo}"]);
            }
        }
    } catch (\Exception $e) {
        $this->detalhaRejeicao($e->getMessage());
    }
    
    return redirect()->to('/emissor/listaXMLsNFe');
}
}
