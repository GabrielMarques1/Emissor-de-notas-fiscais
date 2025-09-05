<?php

namespace App\Controllers;

use App\Models\TransportadoraModel;
use App\Models\MunicipioModel;
use App\Models\UfModel;
use CodeIgniter\Controller;

class Transportadoras extends Controller
{
    private $tipo = 3;

    private $link = '8';

    private $session;
    private $id_contador;
    private $id_empresa;
    
    private $municipio_model;
    private $uf_model;
    private $transportadora_model;

    public function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->municipio_model      = new MunicipioModel();
        $this->uf_model             = new UfModel();
        $this->transportadora_model = new TransportadoraModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Transportadoras',
            'icone' => 'fa fa-user-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Transportadoras", 'rota' => "", 'active' => true],
        ];

        $dados = $this->request
                        ->getVar();

        // Caso $dados seja diferente de vazio então o usuário está fazendo uma pesquisa ou querendo
        // Ver uma quantidade especifica de registros
        if(!empty($dados)):

            // Caso esteja escolhando a quantidade de registros a serem mostrados
            if(isset($dados['num_de_registros'])) :

                if($dados['num_de_registros'] == "00") : // 00=Todos
                    
                    $data['transportadoras'] = $this->transportadora_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->findAll();


                else: // Retorna com a quantidade escolhida ex: 10 registros

                    $data['transportadoras'] = $this->transportadora_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->limit($dados['num_de_registros'])
                                            ->find();

                endif;
                
                $data['num_de_registros'] = $dados['num_de_registros'];

            // Pesquisa por Cód. do Transportadora "id_transportadora"
            elseif($dados['id_transportadora'] != "") :

                $data['transportadoras'] = $this->transportadora_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->where('id_transportadora', $dados['id_transportadora'])
                                            ->find();

                $data['id_transportadora'] = $dados['id_transportadora'];
            
            // Pesquisa por RAZÃO SOCIAL
            elseif($dados['razao_social'] != "") :

                $data['transportadoras'] = $this->transportadora_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->like('xNome', $dados['razao_social'])
                                        ->find();
                
                $data['razao_social'] = $dados['razao_social'];
            
            // Pesquisa por CNPJ do Cliente
            elseif($dados['cnpj'] != "") :

                // Remove mascaras
                $dados['cnpj'] = removeMascaras($dados['cnpj']);

                // dd($dados);

                $data['transportadoras'] = $this->transportadora_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('CNPJ', $dados['cnpj'])
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

            $data['transportadoras'] = $this->transportadora_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->limit(10)
                                    ->orderBy('id_transportadora', 'DESC')
                                    ->find();


        endif;

        echo view('templates/header');
        echo view('transportadoras/index', $data);
        echo view('templates/footer');
    }

    public function show($id_transportadora)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Nova Transportadora',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Transportadoras", 'rota' => "/transportadoras", 'active' => false],
            ['titulo' => "Novo", 'rota' => "", 'active' => true],
        ];

        $data['transportadora'] = $this->transportadora_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('id_transportadora', $id_transportadora)
                                        ->join('ufs', 'transportadoras.id_uf = ufs.id_uf')
                                        ->join('municipios', 'transportadoras.id_municipio = municipios.id_municipio')
                                        ->first();

        echo view('templates/header');
        echo view('transportadoras/show', $data);
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
            'modulo' => 'Nova Transportadora',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Transportadoras", 'rota' => "/transportadoras", 'active' => false],
            ['titulo' => "Novo", 'rota' => "", 'active' => true],
        ];

        $data['ufs'] = $this->uf_model
                            ->findAll();

        echo view('templates/header');
        echo view('transportadoras/form', $data);
        echo view('templates/footer');
    }

    public function edit($id_transportadora)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'Editar Transportadora',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Transportadoras", 'rota' => "/transportadoras", 'active' => false],
            ['titulo' => "Editar", 'rota' => "", 'active' => true],
        ];

        $transportadora = $this->transportadora_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('id_transportadora', $id_transportadora)
                                ->first();

        $data['ufs'] = $this->uf_model
            ->findAll();

        $data['municipios'] = $this->municipio_model
            ->where('id_uf', $transportadora['id_uf'])
            ->findAll();

        $data['transportadora'] = $transportadora;

        echo view('templates/header');
        echo view('transportadoras/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {
        $dados = $this->request
                    ->getvar();
        
        // Adiciona IDs ao array
        $dados = insereIDs($dados);

        // Remove Mascaras
        $dados['CNPJ'] = removeMascaras($dados['CNPJ']);

        // Caso seja isento coloca "" no I.E.
        if($dados['isento'] == 1):
            $dados['IE'] = "";
        endif;

        // Remove as mascaras
        $dados['cnpj'] = removeMascaras($dados['CNPJ']);

        // Caso a ação seja editar
        if (isset($dados['id_transportadora'])):
            $this->transportadora_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->save($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type' => 'success',
                    'title' => 'Transportadora atualizada com sucesso!'
                ]
            );

            return redirect()->to("/transportadoras/edit/{$dados['id_transportadora']}");

        endif;

        // Caso ação seja cadastrar
        $this->transportadora_model
            ->insert($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Transportadora cadastrada com sucesso!'
            ]
        );

        return redirect()->to('/transportadoras');
    }

    public function delete($id_transportadora)
    {
        $this->transportadora_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->where('id_transportadora', $id_transportadora)
            ->delete();

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Transportadora excluida com sucesso!'
            ]
        );
        
        return redirect()->to('/transportadoras');
    }

    public function pegaDadosDoCNPJ()
    {
        //Capturar CNPJ
        $cnpj = $this->request
                    ->getvar('cnpj');

        echo conectaReceitaWS($cnpj);
    }
}
