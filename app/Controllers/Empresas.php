<?php

namespace App\Controllers;

use App\Models\PagamentoModel;
use App\Models\ConfiguracaoModel;
use App\Models\MunicipioModel;
use App\Models\UfModel;
use App\Models\NFCeModel;
use App\Models\NFeModel;
use App\Models\LoginModel;
use App\Models\EmpresaModel;

use CodeIgniter\Controller;

class Empresas extends Controller
{
    private $tipo = 2; // Tipo de usuário que pode acessar esse Controller

    private $link = '2';

    private $session;
    private $id_contador;

    private $pagamento_model;
    private $configuracao_model;
    private $municipio_model;
    private $uf_model;
    private $nfce_model;
    private $nfe_model;
    private $login_model;
    private $empresa_model;

    function __construct()
    {
        $this->helpers = ['app']; // Carrega os helpers

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');

        $this->pagamento_model    = new PagamentoModel();
        $this->configuracao_model = new ConfiguracaoModel();
        $this->municipio_model    = new MunicipioModel();
        $this->uf_model           = new UfModel();
        $this->nfce_model         = new NFCeModel();
        $this->nfe_model          = new NFeModel();
        $this->login_model        = new loginModel();
        $this->empresa_model      = new EmpresaModel();
    }

    public function index()
    {       
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Empresas',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Empresas", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getvar();

        if(isset($dados['cnpj'])) :
            
            $cnpj = removeMascaras($dados['cnpj']);

            $data['empresas'] = $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('CNPJ', $cnpj)
                ->findAll();

            $data['cnpj'] = $cnpj;

        else:

            $data['empresas'] = $this->empresa_model
                                    ->where('id_contador', $this->id_contador)
                                    ->findAll();

        endif;

        echo view('templates/header');
        echo view('empresas/index', $data);
        echo view('templates/footer');
    }

    public function create()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Nova Empresa',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Empresas", 'rota' => "/empresas", 'active' => false],
            ['titulo' => "Novo", 'rota'   => "", 'active' => true]
        ];

        $data['ufs'] = $this->uf_model
                            ->findAll();

        echo view('templates/header');
        echo view('empresas/form', $data);
        echo view('templates/footer');
    }

    public function show($id_empresa)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Dados da Empresa',
            'icone'  => 'fa fa-edit'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Empresas", 'rota' => "/empresas", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        if ((int) $this->session->get('tipo') === 1) {
            $empresa = $this->empresa_model
                ->where('id_empresa', $id_empresa)
                ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                ->first();
        } else {
            $empresa = $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $id_empresa)
                ->join('ufs', 'empresas.id_uf = ufs.id_uf')
                ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
                ->first();
        }

        if (!$empresa) {
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Empresa não encontrada.'
            ]);
            return redirect()->to(($this->session->get('tipo') === 1) ? '/admin/empresas' : '/empresas');
        }

        $pagamentos_da_empresa = $this->pagamento_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $id_empresa)
                                    ->findAll();

        $login = $this->login_model
                    ->where('id_login', $empresa['id_login'])
                    ->first();

        $data['empresa']    = $empresa;
        $data['pagamentos'] = $pagamentos_da_empresa;
        $data['login']      = $login;

        $data['id_empresa'] = $id_empresa;

        echo view('templates/header');
        echo view('empresas/show', $data);
        echo view('templates/footer');
    }

    public function edit($id_empresa)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Editar Empresa',
            'icone'  => 'fa fa-edit'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Empresas", 'rota' => "/empresas", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        if ((int) $this->session->get('tipo') === 1) {
            $empresa = $this->empresa_model->find((int) $id_empresa);
        } else {
            $empresa = $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $id_empresa)
                ->first();
        }

        if (!$empresa) {
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Empresa não encontrada.'
            ]);
            return redirect()->to(($this->session->get('tipo') === 1) ? '/admin/empresas' : '/empresas');
        }

        $login = $this->login_model
            ->where('id_login', $empresa['id_login'])
            ->first();

        $ufs = $this->uf_model
                    ->findAll();

        $municipios = $this->municipio_model
                            ->where('id_uf', $empresa['id_uf'])
                            ->find();

        $data['empresa']    = $empresa;
        $data['login']      = $login;
        $data['ufs']        = $ufs;
        $data['municipios'] = $municipios;

        echo view('templates/header');
        echo view('empresas/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {        
        $dados = $this->request
                        ->getVar();

        $tipoSessao = (int) $this->session->get('tipo');

        // Contador não pode alterar status da empresa no update (master pode)
        if (isset($dados['id_login']) && $tipoSessao !== 1) {
            unset($dados['status']);
        }

        // Restrição: no update, apenas MASTER (tipo 1) pode alterar dia_do_pagamento
        if (isset($dados['id_login']) && isset($dados['dia_do_pagamento']) && $tipoSessao !== 1) {
            unset($dados['dia_do_pagamento']);
        }

        // REMOVE AS MASCARAS
        $dados['CNPJ'] = removeMascaras($dados['CNPJ']);
        $dados['CEP']  = removeMascaras($dados['CEP']);

        // Trata 'nro' apenas se o campo veio no POST (evita sobrescrever quando input está desabilitado)
        if(array_key_exists('nro', $dados)) :
            if($dados['nro'] == "" || $dados['nro'] == "0") :
                $dados['nro'] = "S/N";
            endif;
        endif;

        // Caso exista o id_login então a ação é editar
        if(isset($dados['id_login'])) :

            // Atualiza os dados do login (hash de senha se enviado)
            $loginUpdate = [];
            if (isset($dados['id_login'])) {
                $loginUpdate['id_login'] = (int) $dados['id_login'];
            }
            if (!empty($dados['usuario'])) {
                $loginUpdate['usuario'] = $dados['usuario'];
            }
            if (!empty($dados['senha'])) {
                $loginUpdate['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }
            if (!empty($loginUpdate)) {
                $this->login_model->save($loginUpdate);
            }

            // Prepara apenas os campos pertencentes à empresa para evitar ruído
            $empresaFields = [
                'status','CNPJ','xNome','xFant','IE','dia_do_pagamento','CRT','CEP','xLgr','nro','xCpl','xBairro','fone','natOp','serie','verProc','nNF_homologacao','nNF_producao','tpAmb_NFe','nNFC_homologacao','nNFC_producao','tpAmb_NFCe','CSC_Id','CSC','certificado','senha_do_certificado','id_uf','id_municipio','id_login','id_contador','valor_mensalidade','data_bloqueio','motivo_bloqueio','stripe_customer_id','stripe_subscription_id','stripe_price_id','stripe_product_id','stripe_status','trial_ends_at','current_period_end'
            ];
            $empresaUpdate = [];
            foreach ($empresaFields as $field) {
                if (array_key_exists($field, $dados)) {
                    $empresaUpdate[$field] = $dados[$field];
                }
            }
            // Normaliza dia_do_pagamento se presente
            if (array_key_exists('dia_do_pagamento', $empresaUpdate)) {
                $empresaUpdate['dia_do_pagamento'] = max(1, min(28, (int) $empresaUpdate['dia_do_pagamento']));
            }

            // Atualiza os dados da empresa
            if ($tipoSessao === 1) {
                // MASTER pode editar qualquer empresa, sem escopo do contador
                $empresaUpdate['id_empresa'] = (int) $dados['id_empresa'];
                $this->empresa_model->save($empresaUpdate);
            } else {
                // Contador só edita suas empresas
                $empresaUpdate['id_empresa'] = (int) $dados['id_empresa'];
                $empresaUpdate['id_contador'] = $this->id_contador;
                $this->empresa_model->save($empresaUpdate);
            }

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Empresa atualizada com sucesso!'
                ]
            );

            $tipoSessao = $this->session->get('tipo');
            if ($tipoSessao === 1) {
                return redirect()->to("/admin/empresas/edit/{$dados['id_empresa']}");
            }
            return redirect()->to("/empresas/edit/{$dados['id_empresa']}");
        
        else : // Caso não exista id_login então a ação é create

            // ----------------------- UPLOAD DO CERTIFICADO (opcional) ----------------------- //
            $file = $this->request->getFile('file');
            if ($file && $file->isValid() && $file->getSize() > 0) {
                $ext = strtolower($file->getClientExtension());
                if (!in_array($ext, ['pfx', 'p12'])) {
                    $this->session->setFlashdata('alert', ['type' => 'error', 'title' => 'Extensão não permitida.']);
                    return redirect()->back();
                }
                if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
                    $this->session->setFlashdata('alert', ['type' => 'error', 'title' => 'Arquivo muito grande.']);
                    return redirect()->back();
                }

                $name = date("dmY").date("His").rand(1, 99999999).".pfx";
                $local = "../../writable/uploads/certificados";

                if (!$file->store($local, $name)) {
                    $this->session->setFlashdata('alert', ['type' => 'error', 'title' => 'Falha ao salvar o certificado.']);
                    return redirect()->back();
                }
                $dados['certificado'] = $name;
            } else {
                // sem certificado enviado agora
                $dados['certificado'] = null;
                $dados['senha_do_certificado'] = null;
            }

            // --------------------------------------------------------------------- //

            $dados['tipo'] = 3; // Informa o tipo. 3=empresa
            $dados['status'] = 'Ativo'; // Define status inicial sempre Ativo (contador não escolhe)

            // Dia do pagamento escolhido na criação não poderá ser alterado pelo contador futuramente
            if (isset($dados['dia_do_pagamento'])) {
                $dados['dia_do_pagamento'] = max(1, min(28, (int) $dados['dia_do_pagamento']));
            }

            // Hash de senha
            $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            $id_login = $this->login_model->insert($dados);

            $dados['id_login'] = $id_login;

            $this->empresa_model
                ->insert($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Empresa cadastrada com sucesso!'
                ]
            );

            return redirect()->to('/empresas');
        endif;
    }

    public function delete($id_empresa)
    {
        $tipoSessao = $this->session->get('tipo');
        // Pega os dados da empresa
        if ($tipoSessao === 1) {
            $empresa = $this->empresa_model
                ->where('id_empresa', $id_empresa)
                ->first();
        } else {
            $empresa = $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $id_empresa)
                ->first();
        }

        // Apaga o arquivo .pfx - Certificado
        $local = WRITEPATH . "uploads/certificados/" . $empresa['certificado'];
        if (is_file($local)) {
            @unlink($local);
        }

        // Apaga o registro da empresa
        if ($tipoSessao === 1) {
            $this->empresa_model
                ->where('id_empresa', $id_empresa)
                ->delete();
        } else {
            $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $id_empresa)
                ->delete();
        }

        // Apaga o login da empresa
        $this->login_model
            ->where('id_login', $empresa['id_login'])
            ->delete();

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Empresa excluida com sucesso!'
            ]
        );

        return ($tipoSessao === 1) ? redirect()->to('/admin/empresas') : redirect()->to('/empresas');
    }

    public function baixarCertificado($nome_do_certificado)
    {
        $local = WRITEPATH . "uploads/certificados/" . $nome_do_certificado;
        return $this->response->download($local, NULL);
    }

    public function trocarCertificado()
    {
        // Pega os dados do formulário
        $id_empresa = $this->request->getvar('id_empresa');
        $file       = $this->request->getFile('file');

        if ((int) $this->session->get('tipo') === 1) {
            $empresa = $this->empresa_model
                            ->where('id_empresa', $id_empresa)
                            ->first();
        } else {
            $empresa = $this->empresa_model
                            ->where('id_contador', $this->id_contador)
                            ->where('id_empresa', $id_empresa)
                            ->first();
        }

        if (!$empresa) {
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Empresa não encontrada.'
            ]);
            return redirect()->to(($this->session->get('tipo') === 1) ? '/admin/empresas' : '/empresas');
        }

        // Apaga o arquivo .pfx - Certificado
        $local = WRITEPATH . "uploads/certificados/" . $empresa['certificado'];
        if (is_file($local)) {
            @unlink($local);
        }

        // UPLOAD DO NOVO CERTIFICADO //
        $name = date("dmY").date("His").rand(1, 99999999).".pfx";
        $local = "../../writable/uploads/certificados";

        if (!$file->store($local, $name)) {
            $this->session->setFlashdata('alert', ['type' => 'error', 'title' => 'Falha ao salvar o certificado.']);
            return redirect()->back();
        }
        // --------------------- //

        // MUDA O NOME DO CERTIFICADO NO BANCO DE DADOS //
        if ($this->session->get('tipo') === 1) {
            $this->empresa_model
                ->set('certificado', $name)
                ->where('id_empresa', $id_empresa)
                ->update();
        } else {
            $this->empresa_model
                ->set('certificado', $name)
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $id_empresa)
                ->update();
        }

        // Retorna e mostra o alerta
        $this->session->setFlashdata(
            'alert', 
            [
                'type'  => 'success',
                'title' => 'Certificado atualizado com sucesso!'
            ]
        );

        return redirect()->to("/empresas/edit/$id_empresa");
    }

    public function listaXMLsNFe($id_empresa)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'NFEs',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "NFEs", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getvar();

        if(isset($dados['data_inicio'])):

            $data['nfes'] = $this->nfe_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $id_empresa)
                                ->where('data >=', $dados['data_inicio'])
                                ->where('data <=', $dados['data_final'])
                                ->orderBy('id_nfe', 'DESC')
                                ->find();

            $data['data_inicio'] = $dados['data_inicio'];
            $data['data_final']  = $dados['data_final'];

            $data['titulo_do_filtro'] = "<b>NFEs</b> emitidas durante <b>" . date('d/m/Y', strtotime($dados['data_inicio'])) . "</b> até <b>" . date('d/m/Y', strtotime($dados['data_final'])) . "</b>";

            $this->session->setFlashdata(
                'alert', 
                [
                    'type'  => 'success',
                    'title' => 'Relatório gerado com sucesso!'
                ]
            );

        else:

            $data['nfes'] = [];
            
            $data['titulo_do_filtro'] = "Escolha uma data <b>Inicio</b> e <b>Final</b> para gerar as <b>NFEs</b>";

        endif;

        $data['id_empresa'] = $id_empresa;

        echo view('templates/header');
        echo view('empresas/lista_nfes', $data);
        echo view('templates/footer');
    }

    public function listaXMLsNFCe($id_empresa)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'NFCEs',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "NFCEs", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getvar();

        if(isset($dados['data_inicio'])):

            $data['nfces'] = $this->nfce_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $id_empresa)
                                ->where('data >=', $dados['data_inicio'])
                                ->where('data <=', $dados['data_final'])
                                ->orderBy('id_nfce', 'DESC')
                                ->find();

            $data['data_inicio'] = $dados['data_inicio'];
            $data['data_final']  = $dados['data_final'];

            $data['titulo_do_filtro'] = "<b>NFCEs</b> emitidas durante <b>" . date('d/m/Y', strtotime($dados['data_inicio'])) . "</b> até <b>" . date('d/m/Y', strtotime($dados['data_final'])) . "</b>";

            $this->session->setFlashdata(
                'alert', 
                [
                    'type'  => 'success',
                    'title' => 'Relatório gerado com sucesso!'
                ]
            );

        else:

            $data['nfces'] = [];
            
            $data['titulo_do_filtro'] = "Escolha uma data <b>Inicio</b> e <b>Final</b> para gerar as <b>NFCEs</b>";

        endif;

        $data['id_empresa'] = $id_empresa;

        echo view('templates/header');
        echo view('empresas/lista_nfces', $data);
        echo view('templates/footer');
    }

    // ------------------------ PAGAMENTO ---------------------------- //
    public function novoPagamento($id_empresa)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Novo Pagamento',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Novo Pagamento", 'rota'   => "", 'active' => true]
        ];

        $data['id_empresa'] = $id_empresa;

        echo view('templates/header');
        echo view('empresas/form_pagamento', $data);
        echo view('templates/footer');
    }

    public function editPagamento($id_empresa, $id_pagamento)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Editar Pagamento',
            'icone'  => 'fa fa-edit'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Pagamentos", 'rota' => "/empresas/show/$id_empresa", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        $data['pagamento'] = $this->pagamento_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $id_empresa)
                                ->where('id_pagamento', $id_pagamento)
                                ->first();

        $data['id_empresa'] = $id_empresa;

        echo view('templates/header');
        echo view('empresas/form_pagamento', $data);
        echo view('templates/footer');
    }

    public function storePagamento($id_empresa)
    {
        $dados = $this->request
                        ->getvar();

        // Converte de BRL para USD
        $dados['valor'] = converteMoney($dados['valor']);

        $dados['id_empresa'] = $id_empresa;
        
        // Caso a ação seja editar
        if(isset($dados['id_pagamento'])) :
            $this->pagamento_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $id_empresa)
                ->save($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Pagamento atualizado com sucesso!'
                ]
            );

            return redirect()->to("/empresas/editPagamento/$id_empresa/{$dados['id_pagamento']}");
        endif;

        // Caso a ação seja cadastrar
        // Insere os IDs
        $dados['id_contador'] = $this->id_contador;

        $this->pagamento_model
            ->insert($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Pagamento cadastrado com sucesso!'
            ]
        );

        return redirect()->to("/empresas/show/$id_empresa");
    }

    public function deletePagamento($id_empresa, $id_pagamento)
    {
        $this->pagamento_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $id_empresa)
            ->where('id_pagamento', $id_pagamento)
            ->delete();
        
        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Pagamento excluido com sucesso!'
            ]
        );

        return redirect()->to("/empresas/show/$id_empresa");
    }

    public function adminIndex()
    {
        // Apenas master
        if($retorno = verificaPermissaoDeAcesso(1)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '6';
        $data['titulo'] = [
            'modulo' => 'Empresas (Master)',
            'icone'  => 'fa fa-city'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Empresas", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request->getvar();
        if(isset($dados['cnpj'])) {
            $cnpj = removeMascaras($dados['cnpj']);
            $data['empresas'] = $this->empresa_model
                ->where('CNPJ', $cnpj)
                ->findAll();
            $data['cnpj'] = $cnpj;
        } else {
            $data['empresas'] = $this->empresa_model->findAll();
        }

        echo view('templates/header');
        echo view('empresas/index', $data);
        echo view('templates/footer');
    }

    public function adminEdit($id_empresa)
    {
        // Apenas master
        if($retorno = verificaPermissaoDeAcesso(1)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '6';
        $data['titulo'] = [
            'modulo' => 'Editar Empresa (Master)',
            'icone'  => 'fa fa-edit'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Empresas", 'rota' => "/admin/empresas", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        $empresa = $this->empresa_model->where('id_empresa', $id_empresa)->first();
        if (!$empresa) {
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Empresa não encontrada.'
            ]);
            return redirect()->to('/admin/empresas');
        }
        $login = $this->login_model->where('id_login', $empresa['id_login'])->first();
        $ufs = $this->uf_model->findAll();
        $municipios = $this->municipio_model->where('id_uf', $empresa['id_uf'])->find();

        $data['empresa']    = $empresa;
        $data['login']      = $login;
        $data['ufs']        = $ufs;
        $data['municipios'] = $municipios;

        echo view('templates/header');
        echo view('empresas/form', $data);
        echo view('templates/footer');
    }

    public function adminStore()
    {
        // Apenas master
        if($retorno = verificaPermissaoDeAcesso(1)) :
            return redirect()->to($retorno);
        endif;

        // Reaproveita store normal, mas como master
        return $this->store();
    }
}
