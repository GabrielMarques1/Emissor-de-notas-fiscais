<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\FornecedorModel;
use App\Models\TransportadoraModel;
use App\Models\UnidadeModel;
use App\Models\ProdutoProvisorioModel;
use App\Models\ProdutoModel;

use CodeIgniter\Controller;

class NotaDeDevolucao extends Controller
{
    private $tipo = 3;

    private $link = '4';

    private $session;
    private $id_contador;
    private $id_empresa;
    
    private $configuracao_model;
    private $fornecedor_model;
    private $transportadora_model;
    private $unidade_model;
    private $produto_provisorio_model;
    private $produto_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->configuracao_model       = new ConfiguracaoModel();
        $this->fornecedor_model         = new FornecedorModel();
        $this->transportadora_model     = new TransportadoraModel();
        $this->unidade_model            = new UnidadeModel();
        $this->produto_provisorio_model = new ProdutoProvisorioModel();
        $this->produto_model            = new ProdutoModel();
    }

    public function emitir()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'Emitir Nota de Devolução',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Emitir Nota de Devolução", 'rota'   => "", 'active' => true]
        ];

        $data['produtos'] = $this->produto_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->findAll();

        $data['fornecedores'] = $this->fornecedor_model
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
        echo view('emissor/emitir_nota_de_devolucao', $data);
        echo view('templates/footer');
    }

    public function adicionaProduto()
    {
        $dados = $this->request
                        ->getvar();

        // Caso a busca seja pelo código de barras
        if($dados['codigo_de_barras'] != "" || $dados['codigo_de_barras'] != 0) :
            $produto = $this->produto_model
                            ->where('id_contador', $this->id_contador)
                            ->where('id_empresa', $this->id_empresa)
                            ->where('codigo_de_barras', $dados['codigo_de_barras'])
                            ->first();

            // Caso o produto seja encontrado
            if(!empty($produto)) :
                $produto['CFOP_NFe'] = '5202'; // CFOP padrão para devolução
                
                $produto['quantidade'] = $dados['quantidade'];

                $this->produto_provisorio_model
                    ->insert($produto);

                $this->session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'success',
                        'title' => 'Produto adicionado com sucesso!' 
                    ]    
                );
            else :
                $this->session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'danger',
                        'title' => 'Produto não localizado, verifique o código de barras!' 
                    ]    
                );
            endif;

            return redirect()->to('/notaDeDevolucao/emitir');
        endif;

        $produto = $this->produto_model
                        ->where('id_contador', $this->id_contador)
                        ->where('id_empresa', $this->id_empresa)
                        ->where('id_produto', $dados['id_produto'])
                        ->first();
        
        $produto['CFOP_NFe'] = '5202'; // CFOP padrão para devolução

        // Para pegar a UN do produto
        $unidade_do_produto = $this->unidade_model
                                    ->where('id_unidade', $produto['id_unidade'])
                                    ->first();

        // Adiciona a unidade, quantidade e o id_produto ao produto.
        $produto['unidade']    = $unidade_do_produto['unidade'];
        $produto['quantidade'] = $dados['quantidade'];
        $produto['id_produto'] = $dados['id_produto'];

        $this->produto_provisorio_model
            ->insert($produto);

        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Produto adicionado com sucesso!' 
            ]    
        );

        return redirect()->to('/notaDeDevolucao/emitir');
    }

    public function alteraDadosDoProduto()
    {
        $dados = $this->request
                    ->getvar();

        // Converte de BRL para USD
        $dados['valor_unitario'] = converteMoney($dados['valor_unitario']);
        $dados['desconto']       = converteMoney($dados['desconto']);

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
                'type' => 'success',
                'title' => 'Produto atualizado com sucesso!',
            ]
        );
        
        return redirect()->to('/notaDeDevolucao/emitir');
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
                'title' => 'Produto excluido com sucesso!',
            ]
        );


        return redirect()->to('/notaDeDevolucao/emitir');
    }

    public function preparaEmissao()
    {
        // Remove todos os produtos da tabela provisória para não dar conflito de CFOP
        $this->produto_provisorio_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->delete();

        return redirect()->to('/notaDeDevolucao/emitir');
    }
}
