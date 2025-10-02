<?php

namespace App\Controllers;

use App\Models\PosSaleModel;
use App\Models\PosSaleItemModel;
use App\Models\ShiftModel;
use App\Models\NFeModel;
use App\Models\NFCeModel;
use App\Models\ClienteModel;
use App\Models\ProdutoModel;
use App\Models\CashRegisterModel;
use App\Models\ReportScheduleModel;
use App\Models\DashboardConfigModel;
use App\Models\StockAlertModel;
use CodeIgniter\Controller;

class RelatoriosEmpresa extends Controller
{
    private $tipo = 3; // Apenas empresas/gerentes

    private $session;
    private $id_contador;
    private $id_empresa;

    private $posSaleModel;
    private $posSaleItemModel;
    private $shiftModel;
    private $nfeModel;
    private $nfceModel;
    private $clienteModel;
    private $produtoModel;
    private $cashRegisterModel;
    private $reportScheduleModel;
    private $dashboardConfigModel;
    private $stockAlertModel;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->posSaleModel = new PosSaleModel();
        $this->posSaleItemModel = new PosSaleItemModel();
        $this->shiftModel = new ShiftModel();
        $this->nfeModel = new NFeModel();
        $this->nfceModel = new NFCeModel();
        $this->clienteModel = new ClienteModel();
        $this->produtoModel = new ProdutoModel();
        $this->cashRegisterModel = new CashRegisterModel();
        $this->reportScheduleModel = new ReportScheduleModel();
        $this->dashboardConfigModel = new DashboardConfigModel();
        $this->stockAlertModel = new StockAlertModel();
    }

    public function index()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';

        $data['titulo'] = [
            'modulo' => 'Relatórios Gerenciais',
            'icone'  => 'fas fa-chart-line'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota'   => "", 'active' => true]
        ];

        // Dashboard com estatísticas do mês atual
        $mesAtual = date('Y-m');
        $data['estatisticas'] = $this->getDashboardStats($mesAtual);

        return view('relatorios_empresa/index', $data);
    }

    public function vendas()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Relatório de Vendas',
            'icone'  => 'fas fa-shopping-cart'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Vendas", 'rota' => "", 'active' => true]
        ];

        $filtros = $this->request->getGet();
        $data['filtros'] = $filtros;

        // Aplica filtros
        $vendas = $this->getVendasComFiltros($filtros);
        $data['vendas'] = $vendas;
        $data['totalizadores'] = $this->calcularTotalizadores($vendas);

        return view('relatorios_empresa/vendas', $data);
    }

    public function produtos()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Relatório de Produtos',
            'icone'  => 'fas fa-box'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Produtos", 'rota' => "", 'active' => true]
        ];

        $filtros = $this->request->getGet();
        $data['filtros'] = $filtros;
        $data['produtos'] = $this->getProdutosComFiltros($filtros);

        return view('relatorios_empresa/produtos', $data);
    }

    public function turnos()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Relatório de Turnos',
            'icone'  => 'fas fa-clock'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Turnos", 'rota' => "", 'active' => true]
        ];

        $filtros = $this->request->getGet();
        $data['filtros'] = $filtros;
        $data['turnos'] = $this->getTurnosComFiltros($filtros);

        return view('relatorios_empresa/turnos', $data);
    }

    public function fiscal()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Relatório Fiscal',
            'icone'  => 'fas fa-file-invoice'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Fiscal", 'rota' => "", 'active' => true]
        ];

        $filtros = $this->request->getGet();
        $data['filtros'] = $filtros;
        $data['notas_fiscais'] = $this->getNotasFiscaisComFiltros($filtros);

        return view('relatorios_empresa/fiscal', $data);
    }

    // ==================== MÉTODOS DE DADOS ==================== //

    private function getDashboardStats($periodo)
    {
        $inicio = $periodo . '-01';
        $fim = date('Y-m-t', strtotime($inicio));

        $db = \Config\Database::connect();

        // Total de vendas
        $totalVendas = $db->table('pos_sales')
            ->selectSum('total', 'total_vendas')
            ->where('id_empresa', $this->id_empresa)
            ->where('status', 'finalized')
            ->where('DATE(created_at) >=', $inicio)
            ->where('DATE(created_at) <=', $fim)
            ->get()
            ->getRowArray();

        // Quantidade de vendas
        $qtdVendas = $db->table('pos_sales')
            ->where('id_empresa', $this->id_empresa)
            ->where('status', 'finalized')
            ->where('DATE(created_at) >=', $inicio)
            ->where('DATE(created_at) <=', $fim)
            ->countAllResults();

        // Ticket médio
        $ticketMedio = $qtdVendas > 0 ? ($totalVendas['total_vendas'] ?? 0) / $qtdVendas : 0;

        // Vendas por forma de pagamento
        $vendasPorPagamento = $db->table('pos_sales')
            ->select('payment_type, COUNT(*) as quantidade, SUM(total) as valor')
            ->where('id_empresa', $this->id_empresa)
            ->where('status', 'finalized')
            ->where('DATE(created_at) >=', $inicio)
            ->where('DATE(created_at) <=', $fim)
            ->groupBy('payment_type')
            ->get()
            ->getResultArray();

        // Produtos mais vendidos
        $produtosMaisVendidos = $this->posSaleItemModel
            ->select('pos_sale_items.nome as product_name, SUM(pos_sale_items.quantidade) as total_vendido, SUM(pos_sale_items.quantidade * pos_sale_items.valor_unitario) as valor_total')
            ->join('pos_sales', 'pos_sales.id_pos_sale = pos_sale_items.id_pos_sale')
            ->where('pos_sales.id_empresa', $this->id_empresa)
            ->where('pos_sales.status', 'finalized')
            ->where('DATE(pos_sales.created_at) >=', $inicio)
            ->where('DATE(pos_sales.created_at) <=', $fim)
            ->groupBy('pos_sale_items.nome')
            ->orderBy('total_vendido', 'DESC')
            ->limit(10)
            ->findAll();

        return [
            'total_vendas' => $totalVendas['total_vendas'] ?? 0,
            'quantidade_vendas' => $qtdVendas,
            'ticket_medio' => $ticketMedio,
            'vendas_por_pagamento' => $vendasPorPagamento,
            'produtos_mais_vendidos' => $produtosMaisVendidos
        ];
    }

    private function getVendasComFiltros($filtros)
    {
        $db = \Config\Database::connect();
        $query = $db->table('pos_sales')
            ->select('pos_sales.*, clientes.nome as cliente_nome, 
                      CASE 
                          WHEN clientes.tipo = 1 THEN clientes.cpf 
                          WHEN clientes.tipo = 2 THEN clientes.cnpj 
                          ELSE ""
                      END as cliente_documento', false)
            ->join('clientes', 'clientes.id_cliente = pos_sales.id_cliente', 'left')
            ->where('pos_sales.id_empresa', $this->id_empresa);

        if (!empty($filtros['data_inicio'])) {
            $query->where('DATE(pos_sales.created_at) >=', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $query->where('DATE(pos_sales.created_at) <=', $filtros['data_fim']);
        }
        if (!empty($filtros['status'])) {
            $query->where('pos_sales.status', $filtros['status']);
        }
        if (!empty($filtros['payment_type'])) {
            $query->where('pos_sales.payment_type', $filtros['payment_type']);
        }
        if (!empty($filtros['id_cliente'])) {
            $query->where('pos_sales.id_cliente', $filtros['id_cliente']);
        }

        return $query->orderBy('pos_sales.created_at', 'DESC')->get()->getResultArray();
    }

    private function getProdutosComFiltros($filtros)
    {
        $query = $this->produtoModel
            ->where('id_empresa', $this->id_empresa);

        if (!empty($filtros['nome'])) {
            $query->like('nome', $filtros['nome']);
        }
        if (!empty($filtros['codigo_barras'])) {
            $query->like('codigo_barras', $filtros['codigo_barras']);
        }

        return $query->orderBy('nome', 'ASC')->findAll();
    }

    private function getTurnosComFiltros($filtros)
    {
        $query = $this->shiftModel
            ->select('shifts.*, cash_registers.name as caixa_nome, l1.usuario as aberto_por_usuario, l2.usuario as fechado_por_usuario')
            ->join('cash_registers', 'cash_registers.id_cash_register = shifts.id_cash_register')
            ->join('logins l1', 'l1.id_login = shifts.opened_by', 'left')
            ->join('logins l2', 'l2.id_login = shifts.closed_by', 'left')
            ->where('shifts.id_empresa', $this->id_empresa);

        if (!empty($filtros['data_inicio'])) {
            $query->where('DATE(shifts.opened_at) >=', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $query->where('DATE(shifts.opened_at) <=', $filtros['data_fim']);
        }
        if (!empty($filtros['status'])) {
            $query->where('shifts.status', $filtros['status']);
        }

        return $query->orderBy('shifts.opened_at', 'DESC')->findAll();
    }

    private function getNotasFiscaisComFiltros($filtros)
    {
        $notas = [];

        // NFe
        $queryNFe = $this->nfeModel
            ->where('id_empresa', $this->id_empresa);
        
        if (!empty($filtros['data_inicio'])) {
            $queryNFe->where('data >=', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $queryNFe->where('data <=', $filtros['data_fim']);
        }

        $nfes = $queryNFe->orderBy('data', 'DESC')->findAll();
        foreach ($nfes as $nfe) {
            $notas[] = array_merge((array)$nfe, ['tipo_nota' => 'NFe']);
        }

        // NFCe
        $queryNFCe = $this->nfceModel
            ->where('id_empresa', $this->id_empresa);
        
        if (!empty($filtros['data_inicio'])) {
            $queryNFCe->where('data >=', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $queryNFCe->where('data <=', $filtros['data_fim']);
        }

        $nfces = $queryNFCe->orderBy('data', 'DESC')->findAll();
        foreach ($nfces as $nfce) {
            $notas[] = array_merge((array)$nfce, ['tipo_nota' => 'NFCe']);
        }

        // Ordena por data
        usort($notas, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });

        return $notas;
    }

    private function calcularTotalizadores($vendas)
    {
        $total = 0;
        $totalDescontos = 0;
        $quantidadeVendas = count($vendas);
        $formasPagamento = [];

        foreach ($vendas as $venda) {
            $total += $venda['total'] ?? 0;
            $totalDescontos += $venda['discount'] ?? 0;
            
            $forma = $venda['payment_type'] ?? 'Não informado';
            if (!isset($formasPagamento[$forma])) {
                $formasPagamento[$forma] = ['quantidade' => 0, 'valor' => 0];
            }
            $formasPagamento[$forma]['quantidade']++;
            $formasPagamento[$forma]['valor'] += $venda['total'] ?? 0;
        }

        return [
            'total' => $total,
            'total_descontos' => $totalDescontos,
            'quantidade_vendas' => $quantidadeVendas,
            'ticket_medio' => $quantidadeVendas > 0 ? $total / $quantidadeVendas : 0,
            'formas_pagamento' => $formasPagamento
        ];
    }

    // ==================== COMPARATIVOS ENTRE PERÍODOS ==================== //

    public function comparativo()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Comparativo entre Períodos',
            'icone'  => 'fas fa-chart-line'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Comparativo", 'rota' => "", 'active' => true]
        ];

        $filtros = $this->request->getGet();
        $data['filtros'] = $filtros;

        if (!empty($filtros['periodo1_inicio']) && !empty($filtros['periodo2_inicio'])) {
            $data['periodo1'] = $this->getDadosPeriodo(
                $filtros['periodo1_inicio'],
                $filtros['periodo1_fim'] ?? $filtros['periodo1_inicio']
            );
            $data['periodo2'] = $this->getDadosPeriodo(
                $filtros['periodo2_inicio'],
                $filtros['periodo2_fim'] ?? $filtros['periodo2_inicio']
            );
            $data['comparacao'] = $this->calcularComparacao($data['periodo1'], $data['periodo2']);
        } else {
            $data['periodo1'] = null;
            $data['periodo2'] = null;
            $data['comparacao'] = null;
        }

        return view('relatorios_empresa/comparativo', $data);
    }

    private function getDadosPeriodo($inicio, $fim)
    {
        $db = \Config\Database::connect();
        $vendas = $db->table('pos_sales')
            ->where('id_empresa', $this->id_empresa)
            ->where('status', 'finalized')
            ->where('DATE(created_at) >=', $inicio)
            ->where('DATE(created_at) <=', $fim)
            ->get()
            ->getResultArray();

        $total = array_sum(array_column($vendas, 'total'));
        $quantidade = count($vendas);

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'total_vendas' => $total,
            'quantidade_vendas' => $quantidade,
            'ticket_medio' => $quantidade > 0 ? $total / $quantidade : 0,
            'vendas' => $vendas
        ];
    }

    private function calcularComparacao($periodo1, $periodo2)
    {
        if (!$periodo1 || !$periodo2) return null;

        return [
            'variacao_total' => $this->calcularVariacao($periodo1['total_vendas'], $periodo2['total_vendas']),
            'variacao_quantidade' => $this->calcularVariacao($periodo1['quantidade_vendas'], $periodo2['quantidade_vendas']),
            'variacao_ticket' => $this->calcularVariacao($periodo1['ticket_medio'], $periodo2['ticket_medio']),
        ];
    }

    private function calcularVariacao($valor1, $valor2)
    {
        if ($valor1 == 0) return $valor2 > 0 ? 100 : 0;
        return (($valor2 - $valor1) / $valor1) * 100;
    }

    // ==================== EVOLUÇÃO TEMPORAL ==================== //

    public function evolucao()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Evolução Temporal',
            'icone'  => 'fas fa-chart-area'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Evolução", 'rota' => "", 'active' => true]
        ];

        $periodo = $this->request->getGet('periodo') ?? 'month';
        $data['periodo'] = $periodo;
        $data['evolucao'] = $this->getEvolucaoTemporal($periodo);

        return view('relatorios_empresa/evolucao', $data);
    }

    private function getEvolucaoTemporal($periodo)
    {
        $db = \Config\Database::connect();
        
        if ($periodo === 'day') {
            // Últimos 30 dias
            $query = $db->table('pos_sales')
                ->select('DATE(created_at) as periodo, COUNT(*) as quantidade, SUM(total) as valor')
                ->where('id_empresa', $this->id_empresa)
                ->where('status', 'finalized')
                ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                ->groupBy('DATE(created_at)')
                ->orderBy('DATE(created_at)', 'ASC')
                ->get()
                ->getResultArray();
        } elseif ($periodo === 'week') {
            // Últimas 12 semanas
            $query = $db->table('pos_sales')
                ->select('YEARWEEK(created_at) as periodo, COUNT(*) as quantidade, SUM(total) as valor')
                ->where('id_empresa', $this->id_empresa)
                ->where('status', 'finalized')
                ->where('created_at >=', date('Y-m-d', strtotime('-84 days')))
                ->groupBy('YEARWEEK(created_at)')
                ->orderBy('YEARWEEK(created_at)', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            // Últimos 12 meses
            $query = $db->table('pos_sales')
                ->select('DATE_FORMAT(created_at, "%Y-%m") as periodo, COUNT(*) as quantidade, SUM(total) as valor')
                ->where('id_empresa', $this->id_empresa)
                ->where('status', 'finalized')
                ->where('created_at >=', date('Y-m-d', strtotime('-365 days')))
                ->groupBy('periodo')
                ->orderBy('periodo', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $query;
    }

    // ==================== CLIENTES MAIS FREQUENTES ==================== //

    public function clientes()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Clientes Mais Frequentes',
            'icone'  => 'fas fa-users'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Clientes", 'rota' => "", 'active' => true]
        ];

        $filtros = $this->request->getGet();
        $data['filtros'] = $filtros;
        $data['clientes'] = $this->getClientesMaisFrequentes($filtros);

        return view('relatorios_empresa/clientes', $data);
    }

    private function getClientesMaisFrequentes($filtros)
    {
        $db = \Config\Database::connect();
        $query = $db->table('pos_sales')
            ->select('pos_sales.id_cliente, clientes.nome, 
                      CASE 
                          WHEN clientes.tipo = 1 THEN clientes.cpf 
                          WHEN clientes.tipo = 2 THEN clientes.cnpj 
                          ELSE ""
                      END as documento,
                      clientes.fone as telefone, 
                      COUNT(*) as total_compras, SUM(pos_sales.total) as total_gasto, AVG(pos_sales.total) as ticket_medio,
                      MAX(pos_sales.created_at) as ultima_compra', false)
            ->join('clientes', 'clientes.id_cliente = pos_sales.id_cliente')
            ->where('pos_sales.id_empresa', $this->id_empresa)
            ->where('pos_sales.status', 'finalized')
            ->where('pos_sales.id_cliente IS NOT NULL');

        if (!empty($filtros['data_inicio'])) {
            $query->where('DATE(pos_sales.created_at) >=', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $query->where('DATE(pos_sales.created_at) <=', $filtros['data_fim']);
        }

        return $query
            ->groupBy('pos_sales.id_cliente')
            ->orderBy('total_compras', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();
    }

    // ==================== ALERTAS DE ESTOQUE ==================== //

    public function alertasEstoque()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Alertas de Estoque',
            'icone'  => 'fas fa-exclamation-triangle'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Alertas", 'rota' => "", 'active' => true]
        ];

        // Atualizar alertas
        $this->atualizarAlertasEstoque();

        $data['alertas'] = $this->stockAlertModel
            ->select('stock_alerts.*, produtos.nome as produto_nome, produtos.codigo_de_barras')
            ->join('produtos', 'produtos.id_produto = stock_alerts.id_produto')
            ->where('stock_alerts.id_empresa', $this->id_empresa)
            ->where('stock_alerts.status', 'active')
            ->orderBy('stock_alerts.alert_type', 'ASC')
            ->orderBy('stock_alerts.current_stock', 'ASC')
            ->findAll();

        return view('relatorios_empresa/alertas_estoque', $data);
    }

    private function atualizarAlertasEstoque()
    {
        $produtos = $this->produtoModel
            ->where('id_empresa', $this->id_empresa)
            ->findAll();

        foreach ($produtos as $produto) {
            $estoque = $produto['estoque'] ?? 0;
            $estoqueMinimo = $produto['estoque_minimo'] ?? 5;

            // Remove alertas antigos deste produto
            $this->stockAlertModel
                ->where('id_empresa', $this->id_empresa)
                ->where('id_produto', $produto['id_produto'])
                ->delete();

            // Cria novo alerta se necessário
            if ($estoque == 0) {
                $this->stockAlertModel->insert([
                    'id_empresa' => $this->id_empresa,
                    'id_produto' => $produto['id_produto'],
                    'alert_type' => 'out_of_stock',
                    'threshold' => $estoqueMinimo,
                    'current_stock' => $estoque,
                    'status' => 'active'
                ]);
            } elseif ($estoque <= $estoqueMinimo) {
                $this->stockAlertModel->insert([
                    'id_empresa' => $this->id_empresa,
                    'id_produto' => $produto['id_produto'],
                    'alert_type' => 'low_stock',
                    'threshold' => $estoqueMinimo,
                    'current_stock' => $estoque,
                    'status' => 'active'
                ]);
            }
        }
    }

    // ==================== AGENDAMENTO DE RELATÓRIOS ==================== //

    public function agendamentos()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Agendamentos de Relatórios',
            'icone'  => 'fas fa-calendar-alt'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Agendamentos", 'rota' => "", 'active' => true]
        ];

        $data['agendamentos'] = $this->reportScheduleModel
            ->where('id_empresa', $this->id_empresa)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('relatorios_empresa/agendamentos', $data);
    }

    public function salvarAgendamento()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $dados = $this->request->getPost();
        
        // Calcular próximo envio
        $nextRun = $this->calcularProximoEnvio(
            $dados['frequency'], 
            $dados['schedule_time'] ?? '08:00'
        );
        
        $agendamento = [
            'id_empresa' => $this->id_empresa,
            'id_contador' => $this->id_contador,
            'report_type' => $dados['report_type'],
            'frequency' => $dados['frequency'],
            'format' => $dados['format'] ?? 'excel',
            'email_recipients' => trim($dados['email_recipients']),
            'schedule_time' => $dados['schedule_time'] ?? '08:00',
            'next_run' => $nextRun,
            'is_active' => !empty($dados['is_active']) ? 1 : 0
        ];

        if (!empty($dados['id_schedule'])) {
            $this->reportScheduleModel->update($dados['id_schedule'], $agendamento);
            $mensagem = 'Agendamento atualizado com sucesso!';
        } else {
            $this->reportScheduleModel->insert($agendamento);
            $mensagem = 'Agendamento criado com sucesso!';
        }

        $this->session->setFlashdata('alert', [
            'type' => 'success',
            'title' => $mensagem
        ]);

        return redirect()->to('/relatorios-empresa/agendamentos');
    }

    private function calcularProximoEnvio($frequency, $time)
    {
        $hora = explode(':', $time)[0];
        $minuto = explode(':', $time)[1] ?? '00';
        
        switch ($frequency) {
            case 'daily':
                $next = date('Y-m-d') . ' ' . $time . ':00';
                if (strtotime($next) < time()) {
                    $next = date('Y-m-d', strtotime('+1 day')) . ' ' . $time . ':00';
                }
                return $next;
                
            case 'weekly':
                // Próxima segunda-feira
                $next = date('Y-m-d', strtotime('next Monday')) . ' ' . $time . ':00';
                return $next;
                
            case 'monthly':
                // Dia 1 do próximo mês
                $next = date('Y-m-01', strtotime('first day of next month')) . ' ' . $time . ':00';
                return $next;
                
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
    }

    public function excluirAgendamento($id)
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $this->reportScheduleModel
            ->where('id_schedule', $id)
            ->where('id_empresa', $this->id_empresa)
            ->delete();

        $this->session->setFlashdata('alert', [
            'type' => 'success',
            'title' => 'Agendamento excluído com sucesso!'
        ]);

        return redirect()->to('/relatorios-empresa/agendamentos');
    }

    // ==================== DASHBOARD CUSTOMIZÁVEL ==================== //

    public function customizarDashboard()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $idLogin = $this->session->get('id_login');

        $config = $this->dashboardConfigModel
            ->where('id_empresa', $this->id_empresa)
            ->where('id_login', $idLogin)
            ->first();

        if ($this->request->getMethod() === 'post') {
            $dados = $this->request->getPost();
            
            $configData = [
                'id_empresa' => $this->id_empresa,
                'id_login' => $idLogin,
                'widgets' => json_encode($dados['widgets'] ?? []),
                'layout' => $dados['layout'] ?? 'default',
                'theme' => $dados['theme'] ?? 'default',
                'default_period' => $dados['default_period'] ?? 'month',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($config) {
                $this->dashboardConfigModel->update($config['id_config'], $configData);
            } else {
                $this->dashboardConfigModel->insert($configData);
            }

            $this->session->setFlashdata('alert', [
                'type' => 'success',
                'title' => 'Configurações salvas com sucesso!'
            ]);

            return redirect()->to('/relatorios-empresa');
        }

        $data['link'] = 'relatorios';
        $data['titulo'] = [
            'modulo' => 'Customizar Dashboard',
            'icone'  => 'fas fa-palette'
        ];
        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/painel/empresa", 'active' => false],
            ['titulo' => "Relatórios", 'rota' => "/relatorios-empresa", 'active' => false],
            ['titulo' => "Customizar", 'rota' => "", 'active' => true]
        ];
        
        // Decodificar widgets de JSON para array e garantir valores padrão
        if ($config) {
            $config['widgets'] = !empty($config['widgets']) ? json_decode($config['widgets'], true) : [];
            $config['theme'] = $config['theme'] ?? 'default';
            $config['layout'] = $config['layout'] ?? 'default';
            $config['default_period'] = $config['default_period'] ?? 'month';
        } else {
            $config = [
                'widgets' => [],
                'theme' => 'default',
                'layout' => 'default',
                'default_period' => 'month'
            ];
        }
        
        $data['config'] = $config;

        return view('relatorios_empresa/customizar', $data);
    }

    // ==================== EXPORTAÇÃO DE RELATÓRIOS ==================== //

    public function exportarVendasExcel()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        // Verificar se PHPSpreadsheet está instalado
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca PHPSpreadsheet não instalada. Execute: composer require phpoffice/phpspreadsheet'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $vendas = $this->getVendasComFiltros($filtros);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Data/Hora');
        $sheet->setCellValue('C1', 'Cliente');
        $sheet->setCellValue('D1', 'CPF/CNPJ');
        $sheet->setCellValue('E1', 'Valor Total');
        $sheet->setCellValue('F1', 'Tipo de Pagamento');
        $sheet->setCellValue('G1', 'Status');

        // Estilo do cabeçalho
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4CAF50');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Dados
        $row = 2;
        foreach ($vendas as $venda) {
            $sheet->setCellValue('A' . $row, $venda['id_pos_sale']);
            $sheet->setCellValue('B' . $row, date('d/m/Y H:i', strtotime($venda['created_at'])));
            $sheet->setCellValue('C' . $row, $venda['cliente_nome'] ?? 'Sem cadastro');
            $sheet->setCellValue('D' . $row, $venda['cliente_documento'] ?? '-');
            $sheet->setCellValue('E' . $row, 'R$ ' . number_format($venda['total'], 2, ',', '.'));
            $sheet->setCellValue('F' . $row, $this->formatarTipoPagamento($venda['payment_type']));
            $sheet->setCellValue('G' . $row, $this->formatarStatus($venda['status']));
            $row++;
        }

        // Auto-ajustar colunas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $filename = 'relatorio_vendas_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportarVendasPDF()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        // Verificar se TCPDF está instalado
        if (!class_exists('\TCPDF')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca TCPDF não instalada. Execute: composer require tecnickcom/tcpdf'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $vendas = $this->getVendasComFiltros($filtros);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        // Informações do documento
        $pdf->SetCreator('xFiscal ERP');
        $pdf->SetAuthor($this->session->get('xFant'));
        $pdf->SetTitle('Relatório de Vendas');
        
        // Configurações
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELATÓRIO DE VENDAS', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        // Cabeçalho da tabela
        $pdf->SetFillColor(76, 175, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->Cell(20, 7, 'ID', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Data/Hora', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Cliente', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'CPF/CNPJ', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Valor', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Pagamento', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Status', 1, 1, 'C', true);

        // Dados
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        
        $total = 0;
        foreach ($vendas as $venda) {
            $pdf->Cell(20, 6, $venda['id_pos_sale'], 1, 0, 'C');
            $pdf->Cell(35, 6, date('d/m/Y H:i', strtotime($venda['created_at'])), 1, 0, 'C');
            $pdf->Cell(60, 6, substr($venda['cliente_nome'] ?? 'Sem cadastro', 0, 30), 1, 0, 'L');
            $pdf->Cell(35, 6, $venda['cliente_documento'] ?? '-', 1, 0, 'C');
            $pdf->Cell(30, 6, 'R$ ' . number_format($venda['total'], 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(40, 6, $this->formatarTipoPagamento($venda['payment_type']), 1, 0, 'C');
            $pdf->Cell(30, 6, $this->formatarStatus($venda['status']), 1, 1, 'C');
            
            $total += $venda['total'];
        }

        // Total
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(150, 7, 'TOTAL', 1, 0, 'R');
        $pdf->Cell(30, 7, 'R$ ' . number_format($total, 2, ',', '.'), 1, 1, 'R');

        // Output
        $filename = 'relatorio_vendas_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    private function formatarTipoPagamento($tipo)
    {
        $tipos = [
            'dinheiro' => 'Dinheiro',
            'debito' => 'Débito',
            'credito' => 'Crédito',
            'pix' => 'PIX',
            'boleto' => 'Boleto'
        ];
        return $tipos[$tipo] ?? $tipo;
    }

    private function formatarStatus($status)
    {
        $statuses = [
            'finalized' => 'Finalizada',
            'pending' => 'Pendente',
            'cancelled' => 'Cancelada'
        ];
        return $statuses[$status] ?? $status;
    }

    public function exportarProdutosExcel()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        // Verificar se PHPSpreadsheet está instalado
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca PHPSpreadsheet não instalada. Execute: composer require phpoffice/phpspreadsheet'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $produtos = $this->getProdutosComFiltros($filtros);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Nome');
        $sheet->setCellValue('C1', 'Código de Barras');
        $sheet->setCellValue('D1', 'Valor Unitário');
        $sheet->setCellValue('E1', 'Estoque');
        $sheet->setCellValue('F1', 'Estoque Mínimo');
        $sheet->setCellValue('G1', 'Status');

        // Estilo do cabeçalho
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4CAF50');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Dados
        $row = 2;
        foreach ($produtos as $produto) {
            $sheet->setCellValue('A' . $row, $produto['id_produto']);
            $sheet->setCellValue('B' . $row, $produto['nome']);
            $sheet->setCellValue('C' . $row, $produto['codigo_barras'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, 'R$ ' . number_format($produto['valor_unitario'] ?? 0, 2, ',', '.'));
            $sheet->setCellValue('E' . $row, $produto['estoque'] ?? 0);
            $sheet->setCellValue('F' . $row, $produto['estoque_minimo'] ?? 5);
            $sheet->setCellValue('G' . $row, ($produto['status'] ?? 'ativo') == 'ativo' ? 'Ativo' : 'Inativo');
            $row++;
        }

        // Auto-ajustar colunas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $filename = 'relatorio_produtos_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportarProdutosPDF()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        // Verificar se TCPDF está instalado
        if (!class_exists('\TCPDF')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca TCPDF não instalada. Execute: composer require tecnickcom/tcpdf'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $produtos = $this->getProdutosComFiltros($filtros);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        // Informações do documento
        $pdf->SetCreator('xFiscal ERP');
        $pdf->SetAuthor($this->session->get('xFant'));
        $pdf->SetTitle('Relatório de Produtos');
        
        // Configurações
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELATÓRIO DE PRODUTOS', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        // Cabeçalho da tabela
        $pdf->SetFillColor(76, 175, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->Cell(20, 7, 'Código', 1, 0, 'C', true);
        $pdf->Cell(70, 7, 'Nome', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Código Barras', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Valor', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Estoque', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Mínimo', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Status', 1, 1, 'C', true);

        // Dados
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        
        foreach ($produtos as $produto) {
            $pdf->Cell(20, 6, $produto['id_produto'], 1, 0, 'C');
            $pdf->Cell(70, 6, substr($produto['nome'], 0, 40), 1, 0, 'L');
            $pdf->Cell(40, 6, $produto['codigo_barras'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell(30, 6, 'R$ ' . number_format($produto['valor_unitario'] ?? 0, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, 6, $produto['estoque'] ?? 0, 1, 0, 'C');
            $pdf->Cell(30, 6, $produto['estoque_minimo'] ?? 5, 1, 0, 'C');
            $pdf->Cell(35, 6, ($produto['status'] ?? 'ativo') == 'ativo' ? 'Ativo' : 'Inativo', 1, 1, 'C');
        }

        // Output
        $filename = 'relatorio_produtos_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    // ==================== EXPORTAÇÃO DE TURNOS ==================== //

    public function exportarTurnosExcel()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca PHPSpreadsheet não instalada.'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $turnos = $this->getTurnosComFiltros($filtros);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $sheet->setCellValue('A1', 'Caixa');
        $sheet->setCellValue('B1', 'Aberto Por');
        $sheet->setCellValue('C1', 'Data/Hora Abertura');
        $sheet->setCellValue('D1', 'Fechado Por');
        $sheet->setCellValue('E1', 'Data/Hora Fechamento');
        $sheet->setCellValue('F1', 'Valor Inicial');
        $sheet->setCellValue('G1', 'Valor Final');
        $sheet->setCellValue('H1', 'Diferença');
        $sheet->setCellValue('I1', 'Status');

        // Estilo do cabeçalho
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF17A2B8');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Dados
        $row = 2;
        foreach ($turnos as $turno) {
            $diferenca = ($turno['closing_amount'] ?? 0) - ($turno['opening_amount'] ?? 0);
            
            $sheet->setCellValue('A' . $row, $turno['caixa_nome'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $turno['aberto_por_usuario'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, !empty($turno['opened_at']) ? date('d/m/Y H:i', strtotime($turno['opened_at'])) : 'N/A');
            $sheet->setCellValue('D' . $row, $turno['fechado_por_usuario'] ?? '-');
            $sheet->setCellValue('E' . $row, !empty($turno['closed_at']) ? date('d/m/Y H:i', strtotime($turno['closed_at'])) : '-');
            $sheet->setCellValue('F' . $row, 'R$ ' . number_format($turno['opening_amount'] ?? 0, 2, ',', '.'));
            $sheet->setCellValue('G' . $row, 'R$ ' . number_format($turno['closing_amount'] ?? 0, 2, ',', '.'));
            $sheet->setCellValue('H' . $row, 'R$ ' . number_format($diferenca, 2, ',', '.'));
            $sheet->setCellValue('I' . $row, $turno['status'] == 'open' ? 'Aberto' : 'Fechado');
            $row++;
        }

        // Auto-ajustar colunas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $filename = 'relatorio_turnos_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportarTurnosPDF()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        if (!class_exists('\TCPDF')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca TCPDF não instalada.'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $turnos = $this->getTurnosComFiltros($filtros);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        $pdf->SetCreator('xFiscal ERP');
        $pdf->SetAuthor($this->session->get('xFant'));
        $pdf->SetTitle('Relatório de Turnos');
        
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELATÓRIO DE TURNOS', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFillColor(23, 162, 184);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        
        $pdf->Cell(35, 7, 'Caixa', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Abertura', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Fechamento', 1, 0, 'C', true);
        $pdf->Cell(28, 7, 'Valor Inicial', 1, 0, 'C', true);
        $pdf->Cell(28, 7, 'Valor Final', 1, 0, 'C', true);
        $pdf->Cell(28, 7, 'Diferença', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Status', 1, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 7);
        
        foreach ($turnos as $turno) {
            $diferenca = ($turno['closing_amount'] ?? 0) - ($turno['opening_amount'] ?? 0);
            
            $pdf->Cell(35, 6, substr($turno['caixa_nome'] ?? 'N/A', 0, 20), 1, 0, 'L');
            $pdf->Cell(30, 6, !empty($turno['opened_at']) ? date('d/m/Y H:i', strtotime($turno['opened_at'])) : 'N/A', 1, 0, 'C');
            $pdf->Cell(30, 6, !empty($turno['closed_at']) ? date('d/m/Y H:i', strtotime($turno['closed_at'])) : '-', 1, 0, 'C');
            $pdf->Cell(28, 6, 'R$ ' . number_format($turno['opening_amount'] ?? 0, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(28, 6, 'R$ ' . number_format($turno['closing_amount'] ?? 0, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(28, 6, 'R$ ' . number_format($diferenca, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, 6, $turno['status'] == 'open' ? 'Aberto' : 'Fechado', 1, 1, 'C');
        }

        $filename = 'relatorio_turnos_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    // ==================== EXPORTAÇÃO DE FISCAL ==================== //

    public function exportarFiscalExcel()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca PHPSpreadsheet não instalada.'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $notas = $this->getNotasFiscaisComFiltros($filtros);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $sheet->setCellValue('A1', 'Tipo');
        $sheet->setCellValue('B1', 'Número');
        $sheet->setCellValue('C1', 'Chave');
        $sheet->setCellValue('D1', 'Data');
        $sheet->setCellValue('E1', 'Hora');
        $sheet->setCellValue('F1', 'Valor');
        $sheet->setCellValue('G1', 'Status');

        // Estilo do cabeçalho
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFC107');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FF000000');

        // Dados
        $row = 2;
        $totalValor = 0;
        foreach ($notas as $nota) {
            $sheet->setCellValue('A' . $row, $nota['tipo_nota']);
            $sheet->setCellValue('B' . $row, $nota['numero'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $nota['chave'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, !empty($nota['data']) ? date('d/m/Y', strtotime($nota['data'])) : 'N/A');
            $sheet->setCellValue('E' . $row, $nota['hora'] ?? 'N/A');
            $sheet->setCellValue('F' . $row, 'R$ ' . number_format($nota['valor_da_nota'] ?? 0, 2, ',', '.'));
            
            $status = $nota['status'] ?? 'N/A';
            if ($status == 'Autorizada' || $status == 100) {
                $sheet->setCellValue('G' . $row, 'Autorizada');
            } elseif ($status == 'Cancelada') {
                $sheet->setCellValue('G' . $row, 'Cancelada');
            } else {
                $sheet->setCellValue('G' . $row, $status);
            }
            
            $totalValor += $nota['valor_da_nota'] ?? 0;
            $row++;
        }

        // Totais
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, 'R$ ' . number_format($totalValor, 2, ',', '.'));
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);

        // Auto-ajustar colunas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $filename = 'relatorio_fiscal_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportarFiscalPDF()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        if (!class_exists('\TCPDF')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca TCPDF não instalada.'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $notas = $this->getNotasFiscaisComFiltros($filtros);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        $pdf->SetCreator('xFiscal ERP');
        $pdf->SetAuthor($this->session->get('xFant'));
        $pdf->SetTitle('Relatório Fiscal');
        
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELATÓRIO FISCAL', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFillColor(255, 193, 7);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->Cell(20, 7, 'Tipo', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Número', 1, 0, 'C', true);
        $pdf->Cell(90, 7, 'Chave', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Data/Hora', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Valor', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Status', 1, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 7);
        
        $totalValor = 0;
        foreach ($notas as $nota) {
            $status = $nota['status'] ?? 'N/A';
            if ($status == 'Autorizada' || $status == 100) {
                $statusText = 'Autorizada';
            } elseif ($status == 'Cancelada') {
                $statusText = 'Cancelada';
            } else {
                $statusText = $status;
            }
            
            $pdf->Cell(20, 6, $nota['tipo_nota'], 1, 0, 'C');
            $pdf->Cell(25, 6, $nota['numero'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell(90, 6, substr($nota['chave'] ?? 'N/A', 0, 44), 1, 0, 'L');
            $pdf->Cell(30, 6, (!empty($nota['data']) ? date('d/m/Y', strtotime($nota['data'])) : 'N/A') . ' ' . ($nota['hora'] ?? ''), 1, 0, 'C');
            $pdf->Cell(30, 6, 'R$ ' . number_format($nota['valor_da_nota'] ?? 0, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(35, 6, $statusText, 1, 1, 'C');
            
            $totalValor += $nota['valor_da_nota'] ?? 0;
        }

        // Total
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(165, 7, 'TOTAL', 1, 0, 'R');
        $pdf->Cell(30, 7, 'R$ ' . number_format($totalValor, 2, ',', '.'), 1, 1, 'R');

        $filename = 'relatorio_fiscal_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    // ==================== EXPORTAÇÃO DE CLIENTES ==================== //

    public function exportarClientesExcel()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca PHPSpreadsheet não instalada.'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $clientes = $this->getClientesMaisFrequentes($filtros);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $sheet->setCellValue('A1', 'Cliente');
        $sheet->setCellValue('B1', 'Documento');
        $sheet->setCellValue('C1', 'Telefone');
        $sheet->setCellValue('D1', 'Total de Compras');
        $sheet->setCellValue('E1', 'Valor Total Gasto');
        $sheet->setCellValue('F1', 'Ticket Médio');
        $sheet->setCellValue('G1', 'Última Compra');

        // Estilo do cabeçalho
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF20C997');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Dados
        $row = 2;
        foreach ($clientes as $cliente) {
            $sheet->setCellValue('A' . $row, $cliente['nome']);
            $sheet->setCellValue('B' . $row, $cliente['documento']);
            $sheet->setCellValue('C' . $row, $cliente['telefone'] ?? '-');
            $sheet->setCellValue('D' . $row, $cliente['total_compras']);
            $sheet->setCellValue('E' . $row, 'R$ ' . number_format($cliente['total_gasto'], 2, ',', '.'));
            $sheet->setCellValue('F' . $row, 'R$ ' . number_format($cliente['ticket_medio'], 2, ',', '.'));
            $sheet->setCellValue('G' . $row, date('d/m/Y', strtotime($cliente['ultima_compra'])));
            $row++;
        }

        // Auto-ajustar colunas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $filename = 'relatorio_clientes_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportarClientesPDF()
    {
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        if (!class_exists('\TCPDF')) {
            $this->session->setFlashdata('alert', [
                'type' => 'warning',
                'title' => 'Biblioteca TCPDF não instalada.'
            ]);
            return redirect()->back();
        }

        $filtros = $this->request->getGet();
        $clientes = $this->getClientesMaisFrequentes($filtros);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        $pdf->SetCreator('xFiscal ERP');
        $pdf->SetAuthor($this->session->get('xFant'));
        $pdf->SetTitle('Relatório de Clientes');
        
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELATÓRIO DE CLIENTES MAIS FREQUENTES', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFillColor(32, 201, 151);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        
        $pdf->Cell(60, 7, 'Cliente', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Documento', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Compras', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Total Gasto', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Ticket Médio', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Última Compra', 1, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 7);
        
        foreach ($clientes as $cliente) {
            $pdf->Cell(60, 6, substr($cliente['nome'], 0, 35), 1, 0, 'L');
            $pdf->Cell(40, 6, $cliente['documento'], 1, 0, 'C');
            $pdf->Cell(25, 6, $cliente['total_compras'], 1, 0, 'C');
            $pdf->Cell(35, 6, 'R$ ' . number_format($cliente['total_gasto'], 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(35, 6, 'R$ ' . number_format($cliente['ticket_medio'], 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, date('d/m/Y', strtotime($cliente['ultima_compra'])), 1, 1, 'C');
        }

        $filename = 'relatorio_clientes_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
}
