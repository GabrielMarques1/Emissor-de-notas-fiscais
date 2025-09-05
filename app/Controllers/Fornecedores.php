<?php

namespace App\Controllers;

use App\Models\FornecedorModel;
use App\Models\MunicipioModel;
use App\Models\UfModel;
use CodeIgniter\Controller;

class Fornecedores extends Controller
{
    private $tipo = 3;

    private $link = '7';

    private $session;
    private $id_contador;
    private $id_empresa;

    private $municipio_model;
    private $uf_model;
    private $fornecedor_model;

    public function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');
        
        $this->municipio_model  = new MunicipioModel();
        $this->uf_model         = new UfModel();
        $this->fornecedor_model = new FornecedorModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Forncedores',
            'icone' => 'fa fa-user-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Fornecedores", 'rota' => "", 'active' => true],
        ];

        $dados = $this->request
                        ->getVar();

        // Caso $dados seja diferente de vazio então o usuário está fazendo uma pesquisa ou querendo
        // Ver uma quantidade especifica de registros
        if(!empty($dados)):

            // Caso esteja escolhando a quantidade de registros a serem mostrados
            if(isset($dados['num_de_registros'])) :

                if($dados['num_de_registros'] == "00") : // 00=Todos
                    
                    $data['fornecedores'] = $this->fornecedor_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->findAll();


                else: // Retorna com a quantidade escolhida ex: 10 registros

                    $data['fornecedores'] = $this->fornecedor_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->limit($dados['num_de_registros'])
                                            ->find();

                endif;
                
                $data['num_de_registros'] = $dados['num_de_registros'];

            // Pesquisa por Cód. do Fornecedor "id_fornecedor"
            elseif($dados['id_fornecedor'] != "") :

                $data['fornecedores'] = $this->fornecedor_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->where('id_fornecedor', $dados['id_fornecedor'])
                                            ->find();

                $data['id_fornecedor'] = $dados['id_fornecedor'];
            
            // Pesquisa por NOME do produto
            elseif($dados['nome'] != "") :

                $data['fornecedores'] = $this->fornecedor_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->like('nome', $dados['nome'])
                                            ->find();
                
                $data['nome'] = $dados['nome'];

            // Pesquisa por RAZÃO SOCIAL do Cliente
            elseif($dados['razao_social'] != "") :

                $data['fornecedores'] = $this->fornecedor_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->like('razao_social', $dados['razao_social'])
                                        ->find();
                
                $data['razao_social'] = $dados['razao_social'];
            
            // Pesquisa por CPF do Cliente
            elseif($dados['cpf'] != "") :

                // Remove mascaras
                $dados['cpf'] = removeMascaras($dados['cpf']);

                $data['fornecedores'] = $this->fornecedor_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('cpf', $dados['cpf'])
                                        ->find();
                
                $data['cpf'] = $dados['cpf'];
            
            // Pesquisa por CNPJ do Cliente
            elseif($dados['cnpj'] != "") :

                // Remove mascaras
                $dados['cnpj'] = removeMascaras($dados['cnpj']);

                $data['fornecedores'] = $this->fornecedor_model
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

            $data['fornecedores'] = $this->fornecedor_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->limit(10)
                                    ->orderBy('id_fornecedor', 'DESC')
                                    ->find();


        endif;

        echo view('templates/header');
        echo view('fornecedores/index', $data);
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
            'modulo' => 'Novo Fornecedor',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Fornecedores", 'rota' => "/fornecedores", 'active' => false],
            ['titulo' => "Novo", 'rota' => "", 'active' => true],
        ];

        $data['ufs'] = $this->uf_model
                                ->findAll();

        echo view('templates/header');
        echo view('fornecedores/form', $data);
        echo view('templates/footer');
    }

    public function show($id_fornecedor)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Novo Fornecedor',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Fornecedores", 'rota' => "/fornecedores", 'active' => false],
            ['titulo' => "Novo", 'rota' => "", 'active' => true],
        ];

        $data['fornecedor'] = $this->fornecedor_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->where('id_fornecedor', $id_fornecedor)
                                    ->join('ufs', 'fornecedores.id_uf = ufs.id_uf')
                                    ->join('municipios', 'fornecedores.id_municipio = municipios.id_municipio')
                                    ->first();

        echo view('templates/header');
        echo view('fornecedores/show', $data);
        echo view('templates/footer');
    }

    public function edit($id_fornecedor)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'Editar Fornecedor',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Fornecedores", 'rota' => "/fornecedores", 'active' => false],
            ['titulo' => "Editar", 'rota' => "", 'active' => true],
        ];

        $fornecedor = $this->fornecedor_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('id_fornecedor', $id_fornecedor)
                        ->first();

        $data['ufs'] = $this->uf_model
                                ->findAll();

        $data['municipios'] = $this->municipio_model
                                    ->where('id_uf', $fornecedor['id_uf'])
                                    ->findAll();

        $data['fornecedor'] = $fornecedor;

        echo view('templates/header');
        echo view('fornecedores/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {
        $dados = $this->request
                        ->getvar();
        
        // Adiciona os IDs
        $dados = insereIDs($dados);

        // Remove as mascaras
        if ($dados['tipo'] == 1):
            $dados['cpf'] = removeMascaras($dados['cpf']);
        else:
            $dados['cnpj'] = removeMascaras($dados['cnpj']);
        endif;

        $dados['cep'] = removeMascaras($dados['cep']);

        // Se for fornecedor isento então coloca "" no I.E.
        if($dados['isento'] == 1) :
            $dados['ie'] = "";
        endif;

        // Caso exista o campo então o usuário digitou um número
        if (isset($dados['numero'])):
            
            if ($dados['numero'] == "" || $dados['numero'] == "0"): // Valida
                $dados['numero'] = "S/N";
            endif;

        else: // Caso não exista então é sem número
            
            $dados['numero'] = "S/N";

        endif;

        // Caso ação seja editar
        if (isset($dados['id_fornecedor'])): // Caso exista o id_fornecedor então a ação é aditar
            $this->fornecedor_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->save($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type' => 'success',
                    'title' => 'Fornecedor atualizado com sucesso!'
                ]
            );

            return redirect()->to("/fornecedores/edit/{$dados['id_fornecedor']}");
        endif;

        // Caso ação seja cadastrar
        $this->fornecedor_model
            ->insert($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Fornecedor cadastrado com sucesso!'
            ]
        );
        
        return redirect()->to('/fornecedores');
    }


    public function delete($id_fornecedor)
    {
        $this->fornecedor_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->where('id_fornecedor', $id_fornecedor)
            ->delete();

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Fornecedor excluido com sucesso!'
            ]
        );
        
        return redirect()->to('/fornecedores');
    }
}