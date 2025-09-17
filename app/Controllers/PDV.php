<?php

namespace App\Controllers;

use App\Models\ProdutoModel;
use App\Models\ProdutoProvisorioModel;
use CodeIgniter\Controller;

class PDV extends Controller
{
    private $tipo = 3;
    private $link = '7';

    private $session;
    private $id_contador;
    private $id_empresa;

    private $produto_model;
    private $produto_provisorio_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->produto_model = new ProdutoModel();
        $this->produto_provisorio_model = new ProdutoProvisorioModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'PDV',
            'icone'  => 'fas fa-cash-register'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "PDV", 'rota'   => "", 'active' => true]
        ];

        $itens = $this->produto_provisorio_model
                            ->where('id_contador', $this->id_contador)
                            ->where('id_empresa', $this->id_empresa)
                            ->findAll();

        $data['itens'] = $itens;

        $total = 0.0;
        foreach($itens as $item) {
            $valorUnitario = (float)($item['valor_unitario'] ?? 0);
            $quantidade    = (float)($item['quantidade'] ?? 0);
            $desconto      = (float)($item['desconto'] ?? 0);
            $subtotal      = ($valorUnitario * $quantidade) - $desconto;
            $total += $subtotal;
        }
        $data['total'] = $total;

        echo view('templates/header');
        echo view('pdv/index', $data);
        echo view('templates/footer');
    }

    public function adicionar()
    {
        // Scaffolding: implementação será feita no próximo passo
        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'info',
                'title' => 'Funcionalidade em preparação',
                'message' => 'Adicionar item ao PDV será implementado no próximo passo.'
            ]
        );

        return redirect()->to('/pdv');
    }

    public function remover($id_produto_provisorio)
    {
        $this->produto_provisorio_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->where('id_produto_provisorio', $id_produto_provisorio)
                ->delete();

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Item removido do PDV!'
            ]
        );

        return redirect()->to('/pdv');
    }

    public function limpar()
    {
        $this->produto_provisorio_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->delete();

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Carrinho do PDV limpo!'
            ]
        );

        return redirect()->to('/pdv');
    }

    public function finalizar()
    {
        // Scaffolding: integração com NFC-e será adicionada em breve
        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'info',
                'title' => 'Fluxo de finalização em preparação',
                'message' => 'Integração com emissão NFC-e será adicionada nos próximos passos.'
            ]
        );
        return redirect()->to('/pdv');
    }

    public function buscarPorBarras($codigo_de_barras)
    {
        $produto = $this->produto_model
                            ->where('id_contador', $this->id_contador)
                            ->where('id_empresa', $this->id_empresa)
                            ->where('codigo_de_barras', $codigo_de_barras)
                            ->first();

        return $this->response->setJSON($produto ?: []);
    }
}


