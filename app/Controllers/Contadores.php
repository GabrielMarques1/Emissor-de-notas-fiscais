<?php

namespace App\Controllers;

use App\Models\MunicipioModel;
use App\Models\UfModel;
use App\Models\LoginModel;
use App\Models\EmpresaModel;
use App\Models\ContadorModel;

use CodeIgniter\Controller;

class Contadores extends Controller
{
    private $tipo = 1; // Tipo de usuário que pode acessar esse Controller

    private $link = '2';

    private $session;

    private $municipio_model;
    private $uf_model;
    private $login_model;
    private $empresa_model;
    private $contador_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();

        $this->municipio_model = new MunicipioModel();
        $this->uf_model        = new UfModel();
        $this->login_model     = new LoginModel();
        $this->empresa_model   = new EmpresaModel();
        $this->contador_model  = new ContadorModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Contadores',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio", 'active' => false],
            ['titulo' => "Contadores", 'rota'   => "", 'active' => true]
        ];

        $data['contadores'] = $this->contador_model
                                            ->findAll();

        echo view('templates/header');
        echo view('contador/index', $data);
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
            'modulo' => 'Novo Contador',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio", 'active' => false],
            ['titulo' => "Contadores", 'rota' => "/contadores", 'active' => false],
            ['titulo' => "Novo", 'rota'   => "", 'active' => true]
        ];

        $data['ufs'] = $this->uf_model
                            ->findAll();

        echo view('templates/header');
        echo view('contador/form', $data);
        echo view('templates/footer');
    }

    public function edit($id_contador)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Editar Contador',
            'icone'  => 'fa fa-edit'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio", 'active' => false],
            ['titulo' => "Contadores", 'rota' => "/contadores", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        $contador = $this->contador_model
                        ->where('id_contador', $id_contador)
                        ->first();

        $login = $this->login_model
                    ->where('id_login', $contador['id_login'])
                    ->first();

        $data['ufs'] = $this->uf_model
                            ->findAll();

        $data['municipios'] = $this->municipio_model
                                    ->where('id_uf', $contador['id_uf'])
                                    ->findAll();

        $data['contador'] = $contador;
        $data['login']    = $login;

        echo view('templates/header');
        echo view('contador/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {
        $dados = $this->request
                        ->getVar();

        // REMOVE MASCARAS
        $dados['cnpj']      = removeMascaras($dados['cnpj']);
        $dados['cep']       = removeMascaras($dados['cep']);
        $dados['fixo']      = removeMascaras($dados['fixo']);
        $dados['celular_1'] = removeMascaras($dados['celular_1']);
        $dados['celular_2'] = removeMascaras($dados['celular_2']);

        if(isset($dados['id_login'])) : // Caso exista o id_login então a ação é editar
        
            $this->login_model
                ->save($dados);

            $this->contador_model
                ->save($dados);

            $this->session->setFlashdata(
                'alert', 
                [
                    'type'  => 'success',
                    'title' => 'Contador atualizado com sucesso!'
                ]
            );

            return redirect()->to("/contadores/edit/{$dados['id_contador']}");
        
        else : // Caso não exista id_login então a ação é create

            $dados['data_de_cadastro'] = date('Y-m-d'); // Informa a data de cadastro
            
            $dados['tipo'] = 2; // Informa o tipo. 2=contador

            $dados['status'] = "Ativo"; // Informa que o usuário é ativo

            $id_login = $this->login_model
                            ->insert($dados);

            $dados['id_login'] = $id_login;

            $this->contador_model
                ->insert($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Contador cadastrado com sucesso!'
                ]
            );
            
            return redirect()->to('/contadores');
        
        endif;
    }

    public function delete($id_contador)
    {
        // Retorna todas as empresas desse contador
        $empresas = $this->empresa_model
            ->where('id_contador', $id_contador)
            ->findAll();

        foreach($empresas as $empresa) :
            // Apaga os logins das empresas. Assim apaga automaticamente os registros vinculados a ele
            $this->login_model
                ->where('id_login', $empresa['id_login'])
                ->delete();
        endforeach;

        // Pega o contador
        $contador = $this->contador_model
            ->where('id_contador', $id_contador)
            ->first();

        // Apaga o login do contador assim apaga todos os registros vinculados a ele
        $this->login_model
            ->where('id_login', $contador['id_login'])
            ->delete();

        $this->session->setFlashdata(
            'alert', 
            [
                'type'  => 'success',
                'title' => 'Contador excluido com sucesso!'
            ]
        );

        return redirect()->to('/contadores');
    }
}
