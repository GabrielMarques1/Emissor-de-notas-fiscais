<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<style>
.stat-card {
    border-left: 4px solid;
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.stat-card-primary { border-color: #007bff; }
.stat-card-success { border-color: #28a745; }
.stat-card-warning { border-color: #ffc107; }
.stat-card-info { border-color: #17a2b8; }
.stat-card-danger { border-color: #dc3545; }

.report-menu-card {
    transition: all 0.3s;
    cursor: pointer;
    border-top: 3px solid transparent;
}
.report-menu-card:hover {
    border-top-color: #007bff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-3px);
}
.report-icon {
    font-size: 3rem;
    opacity: 0.7;
}
.chart-container {
    position: relative;
    height: 300px;
}
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-chart-line mr-2"></i>
                    Dashboard de Relatórios Gerenciais
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Acompanhe o desempenho do seu negócio com relatórios detalhados e visualizações interativas.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas Rápidas do Mês -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card stat-card-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total de Vendas</p>
                        <h3 class="mb-0">R$ <?= number_format($estatisticas['total_vendas'], 2, ',', '.') ?></h3>
                        <small class="text-muted">Mês atual</small>
                    </div>
                    <div>
                        <i class="fas fa-dollar-sign fa-3x text-primary" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card stat-card stat-card-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Quantidade de Vendas</p>
                        <h3 class="mb-0"><?= number_format($estatisticas['quantidade_vendas'], 0, ',', '.') ?></h3>
                        <small class="text-muted">Transações</small>
                    </div>
                    <div>
                        <i class="fas fa-shopping-cart fa-3x text-success" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card stat-card stat-card-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Ticket Médio</p>
                        <h3 class="mb-0">R$ <?= number_format($estatisticas['ticket_medio'], 2, ',', '.') ?></h3>
                        <small class="text-muted">Por venda</small>
                    </div>
                    <div>
                        <i class="fas fa-chart-bar fa-3x text-info" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card stat-card stat-card-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Formas de Pagamento</p>
                        <h3 class="mb-0"><?= count($estatisticas['vendas_por_pagamento']) ?></h3>
                        <small class="text-muted">Utilizadas</small>
                    </div>
                    <div>
                        <i class="fas fa-credit-card fa-3x text-warning" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Vendas por Forma de Pagamento
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartPagamentos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Produtos Mais Vendidos
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartProdutos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Menu de Relatórios -->
<div class="row">
    <div class="col-12 mb-3">
        <h4><i class="fas fa-file-alt mr-2"></i>Relatórios Disponíveis</h4>
        <hr>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/vendas" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart report-icon text-primary"></i>
                    <h5 class="mt-3 mb-2">Relatório de Vendas</h5>
                    <p class="text-muted small">
                        Análise completa de todas as vendas com filtros avançados
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/produtos" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-box report-icon text-success"></i>
                    <h5 class="mt-3 mb-2">Relatório de Produtos</h5>
                    <p class="text-muted small">
                        Gestão de estoque e performance de produtos
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/turnos" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-clock report-icon text-info"></i>
                    <h5 class="mt-3 mb-2">Relatório de Turnos</h5>
                    <p class="text-muted small">
                        Acompanhamento de caixas e turnos de trabalho
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/fiscal" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice report-icon text-warning"></i>
                    <h5 class="mt-3 mb-2">Relatório Fiscal</h5>
                    <p class="text-muted small">
                        NFe, NFCe e documentos fiscais emitidos
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/comparativo" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-exchange-alt report-icon text-purple"></i>
                    <h5 class="mt-3 mb-2">Comparativo de Períodos</h5>
                    <p class="text-muted small">
                        Compare desempenho entre diferentes períodos
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/evolucao" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-chart-area report-icon text-indigo"></i>
                    <h5 class="mt-3 mb-2">Evolução Temporal</h5>
                    <p class="text-muted small">
                        Gráficos de evolução ao longo do tempo
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/clientes" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-user-friends report-icon text-teal"></i>
                    <h5 class="mt-3 mb-2">Clientes Frequentes</h5>
                    <p class="text-muted small">
                        Ranking dos clientes mais assíduos
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/alertas-estoque" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle report-icon text-danger"></i>
                    <h5 class="mt-3 mb-2">Alertas de Estoque</h5>
                    <p class="text-muted small">
                        Produtos com estoque baixo ou zerado
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/agendamentos" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt report-icon text-cyan"></i>
                    <h5 class="mt-3 mb-2">Agendar Relatórios</h5>
                    <p class="text-muted small">
                        Configure envio automático por email
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <a href="/relatorios-empresa/customizar" class="text-decoration-none">
            <div class="card report-menu-card">
                <div class="card-body text-center">
                    <i class="fas fa-palette report-icon text-pink"></i>
                    <h5 class="mt-3 mb-2">Customizar Dashboard</h5>
                    <p class="text-muted small">
                        Personalize a aparência e widgets
                    </p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Dados para os gráficos
const vendasesByPaymentType = <?= json_encode($estatisticas['vendas_por_pagamento']) ?>;
const topProducts = <?= json_encode($estatisticas['produtos_mais_vendidos']) ?>;

// Gráfico de Pagamentos
const ctxPagamentos = document.getElementById('chartPagamentos').getContext('2d');
const paymentLabels = vendasesByPaymentType.map(item => {
    const labels = {
        'cash': 'Dinheiro',
        'debit': 'Débito',
        'credit': 'Crédito',
        'pix': 'PIX',
        'voucher': 'Voucher'
    };
    return labels[item.payment_type] || item.payment_type;
});
const paymentData = vendasesByPaymentType.map(item => parseFloat(item.valor));

new Chart(ctxPagamentos, {
    type: 'pie',
    data: {
        labels: paymentLabels,
        datasets: [{
            data: paymentData,
            backgroundColor: [
                '#28a745',
                '#007bff',
                '#ffc107',
                '#17a2b8',
                '#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': R$ ' + context.parsed.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    }
                }
            }
        }
    }
});

// Gráfico de Produtos
const ctxProdutos = document.getElementById('chartProdutos').getContext('2d');
const productLabels = topProducts.map(item => item.product_name);
const productData = topProducts.map(item => parseFloat(item.total_vendido));

new Chart(ctxProdutos, {
    type: 'bar',
    data: {
        labels: productLabels,
        datasets: [{
            label: 'Quantidade Vendida',
            data: productData,
            backgroundColor: '#007bff',
            borderColor: '#0056b3',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?= $this->endSection() ?>
