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

        $empresa = $this->empresa_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $id_empresa)
            ->join('ufs', 'empresas.id_uf = ufs.id_uf')
            ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
            ->first();

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

        $empresa = $this->empresa_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $id_empresa)
            ->first();

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

        // REMOVE AS MASCARAS
        $dados['CNPJ'] = removeMascaras($dados['CNPJ']);
        $dados['CEP']  = removeMascaras($dados['CEP']);

        if(isset($dados['nro'])): // Caso exista o campo então o usuário digitou um número
            if($dados['nro'] == "" || $dados['nro'] == "0") : // Valida
                $dados['nro'] = "S/N";
            endif;
        else: // Caso não exista então é sem número
            $dados['nro'] = "S/N";
        endif;

        // Caso exista o id_login então a ação é editar
        if(isset($dados['id_login'])) :

            // Atualiza os dados do login
            $this->login_model
                ->save($dados);

            // Atualiza os dados da empresa
            $this->empresa_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $dados['id_empresa'])
                ->save($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Empresa atualizada com sucesso!'
                ]
            );

            return redirect()->to("/empresas/edit/{$dados['id_empresa']}");
        
        else : // Caso não exista id_login então a ação é create

            // ----------------------- UPLOAD DO CERTIFICADO ----------------------- //
            $file = $this->request
                        ->getFile('file');

            $name = date("dmY").date("His").rand(1, 99999999).".pfx";
            $local = "../../writable/uploads/certificados";

            $file->store($local, $name);
            $dados['certificado'] = $name;

            // --------------------------------------------------------------------- //

            $dados['tipo'] = 3; // Informa o tipo. 3=empresa

            $id_login = $this->login_model
                            ->insert($dados);

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
        // Pega os dados da empresa
        $empresa = $this->empresa_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $id_empresa)
            ->first();

        // Apaga o arquivo .pfx - Certificado
        $local = WRITEPATH . "uploads/certificados/" . $empresa['certificado'];
        unlink($local);

        // Apaga o registro da empresa
        $this->empresa_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $id_empresa)
            ->delete();

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

        return redirect()->to('/empresas');
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

        $empresa = $this->empresa_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $id_empresa)
                        ->first();

        // Apaga o arquivo .pfx - Certificado
        $local = WRITEPATH . "uploads/certificados/" . $empresa['certificado'];
        unlink($local);

        // UPLOAD DO NOVO CERTIFICADO //
        $name = date("dmY").date("His").rand(1, 99999999).".pfx";
        $local = "../../writable/uploads/certificados";

        $file->store($local, $name);
        // --------------------- //

        // MUDA O NOME DO CERTIFICADO NO BANCO DE DADOS //
        $this->empresa_model
            ->set('certificado', $name)
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $id_empresa)
            ->update();

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
}
