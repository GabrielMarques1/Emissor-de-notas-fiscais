<?php

namespace App\Controllers;

use App\Models\NFCeModel;
use App\Models\NFeModel;
use App\Models\LoginModel;
use CodeIgniter\Controller;

class Emissor extends Controller
{
    private $tipo = 3; // Tipo de usuário que pode acessar esse Controller

    private $session;
    private $id_contador;
    private $id_empresa;

    private $nfce_model;
    private $nfe_model;
    private $login_model;

    public function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');
        
        $this->nfce_model  = new NFCeModel();
        $this->nfe_model   = new NFeModel();
        $this->login_model = new LoginModel();
    }

    public function listaXMLsNFe()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '9';

        $data['titulo'] = [
            'modulo' => 'NFEs',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "NFEs", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getvar();

        if(isset($dados['data_inicio'])):

            $data['nfes'] = $this->nfe_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
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

        $data['id_empresa'] = $this->id_empresa;

        echo view('templates/header');
        echo view('emissor/lista_xmls_nfe', $data);
        echo view('templates/footer');
    }

    public function listaXMLsNFCe()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '10';
        
        $data['titulo'] = [
            'modulo' => 'NFCEs',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "NFCEs", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getvar();

        if(isset($dados['data_inicio'])):

            $data['nfces'] = $this->nfce_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
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

            $data['nfes'] = [];
            
            $data['titulo_do_filtro'] = "Escolha uma data <b>Inicio</b> e <b>Final</b> para gerar as <b>NFCEs</b>";

        endif;

        $data['id_empresa'] = $this->id_empresa;

        echo view('templates/header');
        echo view('emissor/lista_xmls_nfce', $data);
        echo view('templates/footer');
    }
}