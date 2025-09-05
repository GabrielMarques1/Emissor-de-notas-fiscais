<?php

namespace App\Controllers;

use App\Models\TransportadoraModel;
use App\Models\ConfiguracaoModel;
use App\Models\UnidadeModel;
use App\Models\ProdutoProvisorioModel;
use App\Models\ProdutoModel;
use App\Models\ClienteModel;

use CodeIgniter\Controller;

class NotaDeEntrada extends Controller
{
    private $tipo = 3;

    private $link = '2';

    private $session;
    private $id_contador;
    private $id_empresa;
        
    private $transportadora_model;
    private $configuracao_model;
    private $unidade_model;
    private $produto_provisorio_model;
    private $produto_model;
    private $cliente_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');
        
        $this->transportadora_model     = new TransportadoraModel();
        $this->configuracao_model       = new ConfiguracaoModel();
        $this->unidade_model            = new UnidadeModel();
        $this->produto_provisorio_model = new ProdutoProvisorioModel();
        $this->produto_model            = new ProdutoModel();
        $this->cliente_model            = new ClienteModel();
    }

    public function emitir()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Emitir Nota de Entrada',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Emitir Nota de Entrada", 'rota'   => "", 'active' => true]
        ];

        $data['produtos'] = $this->produto_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->findAll();

        $data['clientes'] = $this->cliente_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->findAll();

        $data['produtos_provisorios'] = $this->produto_provisorio_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->findAll();

        $data['unidades'] = $this->unidade_model
                                        ->findAll();

        $data['transportadoras'] = $this->transportadora_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->findAll();

        echo view('templates/header');
        echo view('emissor/emitir_nota_de_entrada', $data);
        echo view('templates/footer');
    }

    public function adicionaProduto()
    {
        $dados = $this->request
                    ->getvar();

        // Caso a busca seja pelo código de barras
        if($dados['codigo_de_barras'] != "") :
            $produto = $this->produto_model
                            ->where('id_contador', $this->id_contador)
                            ->where('id_empresa', $this->id_empresa)
                            ->where('codigo_de_barras', $dados['codigo_de_barras'])
                            ->first();

            // Caso o produto seja encontrado
            if(!empty($produto)) :
                
                $produto['quantidade'] = $dados['quantidade'];

                // Coloca 1102 como CFOP de entrada
                $produto['CFOP'] = '1102';

                $this->produto_provisorio_model
                    ->insert($produto);

                $this->session->setFlashdata(
                    'alert',
                    [
                        'type' => 'success',
                        'title' => 'Produto adicionado com sucesso!'
                    ]
                );

            else :
                
                $this->session->setFlashdata(
                    'alert',
                    [
                        'type' => 'warning',
                        'title' => 'Produto não localizado, verifique o código de barras!'
                    ]
                );

            endif;

            return redirect()->to('/notaDeEntrada/emitir');
        endif;

        $produto = $this->produto_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('id_produto', $dados['id_produto'])
                        ->first();

        // Para pegar a UN do produto
        $unidade_do_produto = $this->unidade_model
                                    ->where('id_unidade', $produto['id_unidade'])
                                    ->first();

        // Adiciona a unidade, quantidade e o id_produto ao produto.
        $produto['unidade']    = $unidade_do_produto['unidade'];
        $produto['quantidade'] = $dados['quantidade'];
        $produto['id_produto'] = $dados['id_produto'];

        // Coloca 1102 como CFOP de entrada
        $produto['CFOP_NFe'] = '1102';

        $this->produto_provisorio_model
            ->insert($produto);

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Produto adicionado com sucesso!'
            ]
        );

        return redirect()->to('/notaDeEntrada/emitir');
    }

    public function alteraDadosDoProduto()
    {
        $dados = $this->request
                        ->getvar();

        // Converte de BRL para USD
        $dados['valor_unitario'] = converteMoney($dados['valor_unitario']);

        // REMOVE MASCARAS
        $dados['NCM']      = removeMascaras($dados['NCM']);
        $dados['CFOP_NFe'] = removeMascaras($dados['CFOP_NFe']);

        $this->produto_provisorio_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->save($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Produto alterado com sucesso!'
            ]
        );

        return redirect()->to('/notaDeEntrada/emitir');
    }

    public function removeProduto($id_produto_provisorio)
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
                'title' => 'Produto removido com sucesso!'
            ]
        );

        return redirect()->to('/notaDeEntrada/emitir');
    }

    public function preparaEmissao()
    {
        // Remove todos os produtos da tabela provisória para não dar conflito de CFOP
        $this->produto_provisorio_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->delete();

        return redirect()->to('/notaDeEntrada/emitir');
    }
}