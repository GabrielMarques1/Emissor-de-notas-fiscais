<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\ContadorModel;
use App\Models\EmpresaModel;
use App\Models\LoginModel;
use App\Models\UfModel;
use App\Models\MunicipioModel;

use CodeIgniter\Controller;

class Admin extends Controller
{
    private $tipo = 1; // Apenas administradores podem acessar

    private $link = '1';

    private $session;

    private $configuracao_model;
    private $contador_model;
    private $empresa_model;
    private $login_model;
    private $uf_model;
    private $municipio_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();

        $this->configuracao_model = new ConfiguracaoModel();
        $this->contador_model     = new ContadorModel();
        $this->empresa_model      = new EmpresaModel();
        $this->login_model        = new LoginModel();
        $this->uf_model           = new UfModel();
        $this->municipio_model    = new MunicipioModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Dashboard Administrativo',
            'icone'  => 'fa fa-tachometer-alt'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Dashboard", 'rota' => "", 'active' => true]
        ];

        // Estatísticas Gerais
        $data['total_contadores'] = count($this->contador_model->findAll());
        $data['contadores_ativos'] = count($this->contador_model->where('status', 'Ativado')->findAll());
        $data['total_empresas'] = count($this->empresa_model->findAll());
        $data['empresas_ativas'] = count($this->empresa_model->where('status', 'Ativado')->findAll());

        echo view('templates/header');
        echo view('admin/dashboard', $data);
        echo view('templates/footer');
    }

    public function contadores()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Gerenciar Contadores',
            'icone'  => 'fa fa-users'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Contadores", 'rota' => "", 'active' => true]
        ];

        $dados = $this->request->getvar();

        // Filtros de pesquisa
        if(isset($dados['nome']) && !empty($dados['nome'])) :
            $data['contadores'] = $this->contador_model
                                    ->like('nome', $dados['nome'])
                                    ->findAll();
            $data['nome'] = $dados['nome'];
        elseif(isset($dados['cnpj']) && !empty($dados['cnpj'])) :
            $cnpj = removeMascaras($dados['cnpj']);
            $data['contadores'] = $this->contador_model
                                    ->where('cnpj', $cnpj)
                                    ->findAll();
            $data['cnpj'] = $dados['cnpj'];
        elseif(isset($dados['status']) && !empty($dados['status'])) :
            $data['contadores'] = $this->contador_model
                                    ->where('status', $dados['status'])
                                    ->findAll();
            $data['status'] = $dados['status'];
        else :
            $data['contadores'] = $this->contador_model
                                    ->orderBy('id_contador', 'DESC')
                                    ->findAll();
        endif;

        echo view('templates/header');
        echo view('admin/contadores', $data);
        echo view('templates/footer');
    }

    public function novoContador()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Novo Contador',
            'icone'  => 'fa fa-user-plus'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Contadores", 'rota' => "/admin/contadores", 'active' => false],
            ['titulo' => "Novo", 'rota' => "", 'active' => true]
        ];

        $data['ufs'] = $this->uf_model->findAll();

        echo view('templates/header');
        echo view('admin/form_contador', $data);
        echo view('templates/footer');
    }

    public function editarContador($id_contador)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Editar Contador',
            'icone'  => 'fa fa-user-edit'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Contadores", 'rota' => "/admin/contadores", 'active' => false],
            ['titulo' => "Editar", 'rota' => "", 'active' => true]
        ];

        $contador = $this->contador_model
                          ->where('id_contador', $id_contador)
                          ->first();

        if(!$contador) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Contador não encontrado!'
            ]);
            return redirect()->to('/admin/contadores');
        endif;

        $login = $this->login_model
                      ->where('id_login', $contador['id_login'])
                      ->first();

        $data['contador'] = $contador;
        $data['login'] = $login;
        $data['ufs'] = $this->uf_model->findAll();
        
        if($contador['id_uf']) :
            $data['municipios'] = $this->municipio_model
                                        ->where('id_uf', $contador['id_uf'])
                                        ->findAll();
        endif;

        echo view('templates/header');
        echo view('admin/form_contador', $data);
        echo view('templates/footer');
    }

    public function salvarContador()
    {
        $dados = $this->request->getVar();

        // Remove máscaras
        $dados['cnpj'] = removeMascaras($dados['cnpj']);
        $dados['cep'] = removeMascaras($dados['cep']);

        // Valida campos obrigatórios
        if(empty($dados['nome']) || empty($dados['usuario']) || empty($dados['cnpj'])) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Preencha todos os campos obrigatórios!'
            ]);
            return redirect()->back()->withInput();
        endif;

        // Verifica se o usuário já existe (exceto na edição)
        $usuario_existente = $this->login_model->where('usuario', $dados['usuario']);
        if(isset($dados['id_login'])) :
            $usuario_existente->where('id_login !=', $dados['id_login']);
        endif;
        $usuario_existente = $usuario_existente->first();

        if($usuario_existente) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Usuário já existe! Escolha outro nome de usuário.'
            ]);
            return redirect()->back()->withInput();
        endif;

        // Caso seja edição
        if(isset($dados['id_login'])) :
            
            // Atualiza dados do login
            $this->login_model->save([
                'id_login' => $dados['id_login'],
                'usuario' => $dados['usuario'],
                'senha' => $dados['senha'],
                'tipo' => 2
            ]);

            // Atualiza dados do contador
            $this->contador_model->save($dados);

            $this->session->setFlashdata('alert', [
                'type' => 'success',
                'title' => 'Contador atualizado com sucesso!'
            ]);

            return redirect()->to("/admin/editarContador/{$dados['id_contador']}");

        else :
            // Novo cadastro
            
            // Criar login
            $id_login = $this->login_model->insert([
                'usuario' => $dados['usuario'],
                'senha' => $dados['senha'],
                'tipo' => 2 // Tipo contador
            ]);

            // Criar contador
            $dados['id_login'] = $id_login;
            $dados['status'] = 'Ativado'; // Ativo por padrão
            
            $this->contador_model->insert($dados);

            $this->session->setFlashdata('alert', [
                'type' => 'success',
                'title' => 'Contador cadastrado com sucesso!'
            ]);

            return redirect()->to('/admin/contadores');
        endif;
    }

    public function alterarStatus($id_contador)
    {
        $contador = $this->contador_model
                          ->where('id_contador', $id_contador)
                          ->first();

        if(!$contador) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Contador não encontrado!'
            ]);
            return redirect()->to('/admin/contadores');
        endif;

        $novo_status = ($contador['status'] == 'Ativado') ? 'Desativado' : 'Ativado';

        $this->contador_model
              ->set('status', $novo_status)
              ->where('id_contador', $id_contador)
              ->update();

        $this->session->setFlashdata('alert', [
            'type' => 'success',
            'title' => "Contador {$novo_status} com sucesso!"
        ]);

        return redirect()->to('/admin/contadores');
    }

    public function excluirContador($id_contador)
    {
        $contador = $this->contador_model
                          ->where('id_contador', $id_contador)
                          ->first();

        if(!$contador) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Contador não encontrado!'
            ]);
            return redirect()->to('/admin/contadores');
        endif;

        // Verifica se tem empresas vinculadas
        $empresas = $this->empresa_model
                          ->where('id_contador', $id_contador)
                          ->findAll();

        if(!empty($empresas)) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Não é possível excluir! Contador possui empresas vinculadas.'
            ]);
            return redirect()->to('/admin/contadores');
        endif;

        // Exclui login
        $this->login_model
              ->where('id_login', $contador['id_login'])
              ->delete();

        // Exclui contador
        $this->contador_model
              ->where('id_contador', $id_contador)
              ->delete();

        $this->session->setFlashdata('alert', [
            'type' => 'success',
            'title' => 'Contador excluído com sucesso!'
        ]);

        return redirect()->to('/admin/contadores');
    }

    public function verEmpresas($id_contador)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $contador = $this->contador_model
                          ->where('id_contador', $id_contador)
                          ->first();

        if(!$contador) :
            $this->session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Contador não encontrado!'
            ]);
            return redirect()->to('/admin/contadores');
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => "Empresas - {$contador['nome_fantasia']}",
            'icone'  => 'fa fa-building'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Contadores", 'rota' => "/admin/contadores", 'active' => false],
            ['titulo' => "Empresas", 'rota' => "", 'active' => true]
        ];

        $data['contador'] = $contador;
        $data['empresas'] = $this->empresa_model
                                  ->where('id_contador', $id_contador)
                                  ->findAll();

        echo view('templates/header');
        echo view('admin/empresas_contador', $data);
        echo view('templates/footer');
    }
}