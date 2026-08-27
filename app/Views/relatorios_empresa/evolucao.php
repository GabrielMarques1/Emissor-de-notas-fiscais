<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-indigo text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-chart-area mr-2"></i>
                    Evolução Temporal
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <a href="?periodo=day" class="btn btn-outline-primary <?= $periodo == 'day' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-day mr-1"></i>
                        Diário (30 dias)
                    </a>
                    <a href="?periodo=week" class="btn btn-outline-primary <?= $periodo == 'week' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-week mr-1"></i>
                        Semanal (12 semanas)
                    </a>
                    <a href="?periodo=month" class="btn btn-outline-primary <?= $periodo == 'month' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        Mensal (12 meses)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <canvas id="chartEvolucao" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const evolucaoData = <?= json_encode($evolucao) ?>;
const labels = evolucaoData.map(item => item.periodo);
const valores = evolucaoData.map(item => parseFloat(item.valor || 0));
const quantidades = evolucaoData.map(item => parseInt(item.quantidade || 0));

const ctx = document.getElementById('chartEvolucao').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Valor Total (R$)',
                data: valores,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                yAxisID: 'y',
                tension: 0.3
            },
            {
                label: 'Quantidade de Vendas',
                data: quantidades,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                yAxisID: 'y1',
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Valor (R$)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Quantidade'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>

<?= $this->endSection() ?>
