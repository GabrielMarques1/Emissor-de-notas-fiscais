<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use CodeIgniter\Controller;

class Configuracoes extends Controller
{
    private $tipo = 1;

    private $link = '4';

    private $session;
    private $configuracao_model;
    
    public function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();        
        $this->configuracao_model = new ConfiguracaoModel();
    }

    public function edit($id_config = 1)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Configurações',
            'icone' => 'fa fa-plus-circle',
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio", 'active' => false],
            ['titulo' => "Configurações", 'rota' => "/clientes", 'active' => false],
            ['titulo' => "Editar", 'rota' => "", 'active' => true],
        ];

        $data['config'] = $this->configuracao_model
                                ->where('id_config', 1)
                                ->first();

        echo view('templates/header');
        echo view('configuracoes/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {
        $dados = $this->request
                        ->getvar();

        $dados['id_config'] = 1;

        $this->configuracao_model
            ->save($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Configurações atualizadas com sucesso!'
            ]
        );

        return redirect()->to('/configuracoes/edit');
    }
}
