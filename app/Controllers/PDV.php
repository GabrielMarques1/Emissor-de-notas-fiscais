<?php

namespace App\Controllers;

use App\Models\ProdutoModel;
use App\Models\ProdutoProvisorioModel;
use CodeIgniter\Controller;

class PDV extends Controller
{
    private $tipo = 3;
    private $link = 'pdv';

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
        log_message('debug', '=== PDV Controller INÍCIO ===');
        
        // Verifica se o usuário tem permissão - permite tipo 3 (gerentes) e tipo 4 (caixas)
        $session = session();
        $tipoUsuario = (int) ($session->get('tipo') ?? 0);
        $status = $session->get('status');
        $usuario = $session->get('usuario');
        $idEmpresa = $session->get('id_empresa');
        $idContador = $session->get('id_contador');
        
        log_message('debug', 'PDV Controller - Dados da sessão:');
        log_message('debug', '  - tipo: ' . $tipoUsuario);
        log_message('debug', '  - status: ' . ($status ?? 'null'));
        log_message('debug', '  - usuario: ' . ($usuario ?? 'null'));
        log_message('debug', '  - id_empresa: ' . ($idEmpresa ?? 'null'));
        log_message('debug', '  - id_contador: ' . ($idContador ?? 'null'));
        
        if (!in_array($tipoUsuario, [3, 4]) || $status === "Desativado") {
            log_message('error', 'PDV Controller - ACESSO NEGADO!');
            log_message('error', '  - Tipo inválido: ' . (!in_array($tipoUsuario, [3, 4]) ? 'SIM' : 'NÃO'));
            log_message('error', '  - Status desativado: ' . ($status === "Desativado" ? 'SIM' : 'NÃO'));
            
            $session->setFlashdata('alert', [
                'type' => 'error',
                'title' => 'Você não tem permissão de acessar essa funcionalidade!'
            ]);
            return redirect()->to('/index.php/login-pdv');
        }
        
        log_message('debug', 'PDV Controller - Permissões OK, carregando dados...');

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

        log_message('debug', 'PDV Controller - Dados preparados:');
        log_message('debug', '  - Total de itens: ' . count($itens));
        log_message('debug', '  - Total geral: R$ ' . number_format($total, 2, ',', '.'));
        log_message('debug', '=== PDV Controller SUCESSO === Renderizando view');

        echo view('templates/header');
        // Nova UI moderna baseada no layout solicitado
        echo view('pdv/index_modern', $data);
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

        return redirect()->to('/index.php/pdv');
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

        return redirect()->to('/index.php/pdv');
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

        return redirect()->to('/index.php/pdv');
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
        return redirect()->to('/index.php/pdv');
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


