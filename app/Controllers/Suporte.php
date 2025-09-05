<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;

use CodeIgniter\Controller;

class Suporte extends Controller
{
    private $tipo = 2; // 2=Contador

    private $session;

    private $configuracao_model;

    private $link = '5';

    function __construct()
    {
        $this->session = session();

        $this->helpers = ['app'];

        $this->configuracao_model = new ConfiguracaoModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Suporte',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Novo Pagamento", 'rota'   => "", 'active' => true]
        ];

        $data['configuracao'] = $this->configuracao_model
                                    ->where('id_config', 1)
                                    ->first();

        echo view('templates/header');
        echo view('suporte/index', $data);
        echo view('templates/footer');
    }
}

?>