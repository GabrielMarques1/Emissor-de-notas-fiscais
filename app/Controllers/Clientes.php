<?php

namespace App\Controllers;

use App\Models\MunicipioModel;
use App\Models\UfModel;
use App\Models\ClienteModel;

use CodeIgniter\Controller;

class Clientes extends Controller
{
    private $tipo = 3;

    private $link = '5';

    private $session;
    private $id_contador;
    private $id_empresa;

    private $municipio_model;
    private $uf_model;
    private $cliente_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->municipio_model = new MunicipioModel();
        $this->uf_model        = new UfModel();
        $this->cliente_model   = new ClienteModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'Clientes',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Clientes", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getVar();

        // Caso $dados seja diferente de vazio então o usuário está fazendo uma pesquisa ou querendo
        // Ver uma quantidade especifica de registros
        if(!empty($dados)):

            // Caso esteja escolhando a quantidade de registros a serem mostrados
            if(isset($dados['num_de_registros'])) :

                if($dados['num_de_registros'] == "00") : // 00=Todos
                    
                    $data['clientes'] = $this->cliente_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->findAll();


                else: // Retorna com a quantidade escolhida

                    $data['clientes'] = $this->cliente_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->limit($dados['num_de_registros'])
                                            ->find();

                endif;
                
                $data['num_de_registros'] = $dados['num_de_registros'];

            // Pesquisa por Cód. do Cliente "id_cliente"
            elseif($dados['id_cliente'] != "") :

                $data['clientes'] = $this->cliente_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->where('id_cliente', $dados['id_cliente'])
                                            ->find();

                $data['id_cliente'] = $dados['id_cliente'];
            
            // Pesquisa por NOME do produto
            elseif($dados['nome'] != "") :

                $data['clientes'] = $this->cliente_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->like('nome', $dados['nome'])
                                            ->find();
                
                $data['nome'] = $dados['nome'];

            // Pesquisa por RAZÃO SOCIAL do Cliente
            elseif($dados['razao_social'] != "") :

                $data['clientes'] = $this->cliente_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->like('razao_social', $dados['razao_social'])
                                        ->find();
                
                $data['razao_social'] = $dados['razao_social'];
            
            // Pesquisa por CPF do Cliente
            elseif($dados['cpf'] != "") :

                // Remove mascaras
                $dados['cpf'] = removeMascaras($dados['cpf']);

                $data['clientes'] = $this->cliente_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('cpf', $dados['cpf'])
                                        ->find();
                
                $data['cpf'] = $dados['cpf'];
            
            // Pesquisa por CNPJ do Cliente
            elseif($dados['cnpj'] != "") :

                // Remove mascaras
                $dados['cnpj'] = removeMascaras($dados['cnpj']);

                $data['clientes'] = $this->cliente_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('cnpj', $dados['cnpj'])
                                        ->find();
                
                $data['cnpj'] = $dados['cnpj'];

            endif;

            // Retorna uma mensagem para o usuário
            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Filtro aplicado com sucesso!'
                ]
            );

        else:

            $data['clientes'] = $this->cliente_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->limit(10)
                                    ->orderBy('id_cliente', 'DESC')
                                    ->find();


        endif;

        echo view('templates/header');
        echo view('clientes/index', $data);
        echo view('templates/footer');
    }

    public function show($id_cliente)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Novo Cliente',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Clientes", 'rota' => "/clientes", 'active' => false],
            ['titulo' => "Novo", 'rota'   => "", 'active' => true]
        ];

        $data['cliente'] = $this->cliente_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('id_cliente', $id_cliente)
                                ->join('ufs', 'clientes.id_uf = ufs.id_uf')
                                ->join('municipios', 'clientes.id_municipio = municipios.id_municipio')
                                ->first();

        echo view('templates/header');
        echo view('clientes/show', $data);
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
            'modulo' => 'Novo Cliente',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Clientes", 'rota' => "/clientes", 'active' => false],
            ['titulo' => "Novo", 'rota'   => "", 'active' => true]
        ];

        $data['ufs'] = $this->uf_model
                            ->findAll();

        echo view('templates/header');
        echo view('clientes/form', $data);
        echo view('templates/footer');
    }

    public function edit($id_cliente)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'Editar Cliente',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Clientes", 'rota' => "/clientes", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        $cliente = $this->cliente_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('id_cliente', $id_cliente)
                        ->first();

        $data['ufs'] = $this->uf_model
                            ->findAll();

        $data['municipios'] = $this->municipio_model
                                ->where('id_uf', $cliente['id_uf'])
                                ->findAll();

        $data['cliente'] = $cliente;

        echo view('templates/header');
        echo view('clientes/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {
        $dados = $this->request
                    ->getvar();
        
        // Insere os IDs
        $dados = insereIDs($dados);

        // ------------- Remove as mascaras ------------------ //
        if($dados['tipo'] == 1) :
            $dados['cpf'] = removeMascaras($dados['cpf']);
        else:
            $dados['cnpj'] = removeMascaras($dados['cnpj']);
        endif;

        $dados['cep'] = removeMascaras($dados['cep']);

        // Se for cliente isento então coloca "" no I.E.
        if($dados['isento'] == 1) :
            $dados['ie'] = "";
        endif;

        // Caso exista o campo então o usuário digitou um número
        if(isset($dados['numero'])):
            if($dados['numero'] == "" || $dados['numero'] == "0") : // Valida
                $dados['numero'] = "S/N";
            endif;
        else: // Caso não exista então é sem número
            $dados['numero'] = "S/N";
        endif;
        
        // Caso ação seja editar
        if(isset($dados['id_cliente'])) :
            $this->cliente_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->save($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Cliente atualizado com sucesso!',
                ]
            );

            return redirect()->to("/clientes/edit/{$dados['id_cliente']}");

        endif;

        // Caso ação seja cadastrar
        $this->cliente_model
            ->insert($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Cliente cadastrado com sucesso!',
            ]
        );

        return redirect()->to('/clientes');
    }

    public function delete($id_cliente)
    {
        $this->cliente_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->where('id_cliente', $id_cliente)
            ->delete();
        
        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Cliente excluido com sucesso!',
            ]
        );
        
        return redirect()->to('/clientes');
    }
}
