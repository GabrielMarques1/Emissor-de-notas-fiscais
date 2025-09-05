<?php

namespace App\Controllers;

use App\Models\UnidadeModel;
use App\Models\ProdutoModel;

use CodeIgniter\Controller;

class Produtos extends Controller
{
    private $tipo = 3;

    private $link = '6';

    private $session;
    private $id_contador;
    private $id_empresa;
    
    private $unidade_model;
    private $produto_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');
        
        $this->unidade_model = new UnidadeModel();
        $this->produto_model = new ProdutoModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Produtos',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Produtos", 'rota'   => "", 'active' => true]
        ];

        $dados = $this->request
                        ->getVar();

        // Caso $dados seja diferente de vazio então o usuário está fazendo uma pesquisa ou querendo
        // Ver uma quantidade especifica de registros
        if(!empty($dados)):

            // Caso esteja escolhando a quantidade de registros a serem mostrados
            if(isset($dados['num_de_registros'])) :

                if($dados['num_de_registros'] == "00") : // 00=Todos
                    
                    $data['produtos'] = $this->produto_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->join('unidades', 'produtos.id_unidade = unidades.id_unidade')
                                            ->findAll();

                else: // Retorna com a quantidade escolhida ex: 10 registros

                    $data['produtos'] = $this->produto_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->limit($dados['num_de_registros'])
                                            ->join('unidades', 'produtos.id_unidade = unidades.id_unidade')
                                            ->find();

                endif;
                
                $data['num_de_registros'] = $dados['num_de_registros'];

            // Pesquisa por Cód. do Produto "id_produto"
            elseif($dados['id_produto'] != "") :

                $data['produtos'] = $this->produto_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('id_produto', $dados['id_produto'])
                                        ->join('unidades', 'produtos.id_unidade = unidades.id_unidade')
                                        ->find();

                $data['id_produto'] = $dados['id_produto'];
            
            // Pesquisa por NOME do produto
            elseif($dados['nome'] != "") :

                $data['produtos'] = $this->produto_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->like('nome', $dados['nome'])
                                        ->join('unidades', 'produtos.id_unidade = unidades.id_unidade')
                                        ->find();
                
                $data['nome'] = $dados['nome'];

            // Pesquisa por CÓD. DE BARRAS do produto
            elseif($dados['codigo_de_barras'] != "") :

                $data['produtos'] = $this->produto_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->where('codigo_de_barras', $dados['codigo_de_barras'])
                                        ->join('unidades', 'produtos.id_unidade = unidades.id_unidade')
                                        ->find();
                
                $data['codigo_de_barras'] = $dados['codigo_de_barras'];

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

            $data['produtos'] = $this->produto_model
                                    ->where('id_contador', $this->id_contador)
                                    ->where('id_empresa', $this->id_empresa)
                                    ->limit(10)
                                    ->orderBy('id_produto', 'DESC')
                                    ->join('unidades', 'produtos.id_unidade = unidades.id_unidade')
                                    ->find();

        endif;

        echo view('templates/header');
        echo view('produtos/index', $data);
        echo view('templates/footer');
    }

    public function show($id_produto)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        $data['titulo'] = [
            'modulo' => 'Novo Produto',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Produtos", 'rota' => "/produtos", 'active' => false],
            ['titulo' => "Novo", 'rota'   => "", 'active' => true]
        ];

        $data['produto'] = $this->produto_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('id_produto', $id_produto)
                                ->first();

        echo view('templates/header');
        echo view('produtos/show', $data);
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
            'modulo' => 'Novo Produto',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Produtos", 'rota' => "/produtos", 'active' => false],
            ['titulo' => "Novo", 'rota'   => "", 'active' => true]
        ];

        $data['unidades'] = $this->unidade_model
                                ->findAll();

        echo view('templates/header');
        echo view('produtos/form', $data);
        echo view('templates/footer');
    }

    public function edit($id_produto)
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;
        
        $data['titulo'] = [
            'modulo' => 'Editar Produto',
            'icone'  => 'fa fa-plus-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
            ['titulo' => "Produtos", 'rota' => "/produtos", 'active' => false],
            ['titulo' => "Editar", 'rota'   => "", 'active' => true]
        ];

        $data['unidades'] = $this->unidade_model
                                ->findAll();

        $data['produto'] = $this->produto_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('id_produto', $id_produto)
                                ->first();

        echo view('templates/header');
        echo view('produtos/form', $data);
        echo view('templates/footer');
    }

    public function store()
    {
        $dados = $this->request
                    ->getvar();

        // Converte de BRL para USD
        $dados['valor_unitario'] = converteMoney($dados['valor_unitario']);

        // Adiciona os IDs
        $dados = insereIDs($dados);    

        // Caso o produto não tenha codigo de barras coloca SEM GTIN
        if(!isset($dados['codigo_de_barras'])) :
            $dados['codigo_de_barras'] = "SEM GTIN";
        endif;
        
        // ----------- REMOVE AS MASCARAS --------------- //
        $dados['CFOP_NFe']     = removeMascaras($dados['CFOP_NFe']);
        $dados['CFOP_NFCe']    = removeMascaras($dados['CFOP_NFCe']);
        $dados['CFOP_Externo'] = removeMascaras($dados['CFOP_Externo']);
        $dados['NCM']          = removeMascaras($dados['NCM']);
        
        // Caso ação seja editar
        if(isset($dados['id_produto'])) :
            $this->produto_model
                ->where('id_contador', $this->id_contador)
                ->where('id_empresa', $this->id_empresa)
                ->save($dados);

            $this->session->setFlashdata(
                'alert',
                [
                    'type' => 'success',
                    'title' => 'Produto atualizado com sucesso!'
                ]
            );

            return redirect()->to("/produtos/edit/{$dados['id_produto']}");
        endif;

        // Caso ação é cadastrar
        $this->produto_model
            ->insert($dados);

        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Produto cadastrado com sucesso!'
            ]
        );

        return redirect()->to('/produtos');
    }

    public function delete($id_produto)
    {
        $this->produto_model
            ->where('id_contador', $this->id_contador)
            ->where('id_empresa', $this->id_empresa)
            ->where('id_produto', $id_produto)
            ->delete();
        
        $this->session->setFlashdata(
            'alert',
            [
                'type' => 'success',
                'title' => 'Produto excluido com sucesso!'
            ]
        );
        
        return redirect()->to('/produtos');
    }

    public function addPorCSV()
    {
        $file = $this->request->getFile('csv');

        if ($file->isValid())
        {
            // Remove todos os registros de produtos
            $this->produto_model
                            ->where('id_contador', $this->id_contador)
                            ->where('id_empresa', $this->id_empresa)
                            ->delete();

            // Converte o CSV em STR
            $str = file_get_contents($file);

            // ----- //
            $linhas = explode("\n", $str);

            // Remove primeira linha (CABECALHO)
            array_shift($linhas);

            // Remove primeira linha (CABECALHO)
            array_pop($linhas);

            foreach($linhas as $linha)
            {
                $colunas = explode(";", $linha);

                $this->produto_model
                                ->insert([
                                    'nome' => $colunas[0],
                                    'codigo_de_barras' => $colunas[1],
                                    'valor_unitario' => converteMoney($colunas[2]),
                                    'CFOP_NFe' => $colunas[3],
                                    'CFOP_NFCe' => $colunas[4],
                                    'CFOP_Externo' => $colunas[5],
                                    'NCM' => $colunas[6],
                                    'CSOSN' => $colunas[7],
                                    'id_unidade' => 1,
                                    'id_contador' => $this->id_contador,
                                    'id_empresa' => $this->id_empresa,
                                ]);
            }

            $this->session
                        ->setFlashdata(
                            'alert',
                            [
                                'type' => 'success',
                                'title' => "Produtos importados com sucesso!"
                            ]
                        );

            return redirect()->to("/produtos");
        }
    }
}
