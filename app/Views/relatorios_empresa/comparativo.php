<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-purple text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Comparativo entre Períodos
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter mr-2"></i>
                    Selecione os Períodos
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="/relatorios-empresa/comparativo">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Período 1</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Data Início</label>
                                        <input type="date" name="periodo1_inicio" class="form-control" 
                                               value="<?= $filtros['periodo1_inicio'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Data Fim</label>
                                        <input type="date" name="periodo1_fim" class="form-control" 
                                               value="<?= $filtros['periodo1_fim'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Período 2</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Data Início</label>
                                        <input type="date" name="periodo2_inicio" class="form-control" 
                                               value="<?= $filtros['periodo2_inicio'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Data Fim</label>
                                        <input type="date" name="periodo2_fim" class="form-control" 
                                               value="<?= $filtros['periodo2_fim'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Comparar Períodos
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($periodo1 && $periodo2): ?>
<!-- Resultados -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5>Total de Vendas</h5>
                <h3 class="text-primary">R$ <?= number_format($periodo1['total_vendas'], 2, ',', '.') ?></h3>
                <small class="text-muted">Período 1</small>
                <hr>
                <h3 class="text-info">R$ <?= number_format($periodo2['total_vendas'], 2, ',', '.') ?></h3>
                <small class="text-muted">Período 2</small>
                <hr>
                <?php
                $var = $comparacao['variacao_total'];
                $class = $var >= 0 ? 'success' : 'danger';
                $icon = $var >= 0 ? 'up' : 'down';
                ?>
                <h4 class="text-<?= $class ?>">
                    <i class="fas fa-arrow-<?= $icon ?>"></i>
                    <?= number_format(abs($var), 2) ?>%
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5>Quantidade de Vendas</h5>
                <h3 class="text-primary"><?= $periodo1['quantidade_vendas'] ?></h3>
                <small class="text-muted">Período 1</small>
                <hr>
                <h3 class="text-info"><?= $periodo2['quantidade_vendas'] ?></h3>
                <small class="text-muted">Período 2</small>
                <hr>
                <?php
                $var = $comparacao['variacao_quantidade'];
                $class = $var >= 0 ? 'success' : 'danger';
                $icon = $var >= 0 ? 'up' : 'down';
                ?>
                <h4 class="text-<?= $class ?>">
                    <i class="fas fa-arrow-<?= $icon ?>"></i>
                    <?= number_format(abs($var), 2) ?>%
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5>Ticket Médio</h5>
                <h3 class="text-primary">R$ <?= number_format($periodo1['ticket_medio'], 2, ',', '.') ?></h3>
                <small class="text-muted">Período 1</small>
                <hr>
                <h3 class="text-info">R$ <?= number_format($periodo2['ticket_medio'], 2, ',', '.') ?></h3>
                <small class="text-muted">Período 2</small>
                <hr>
                <?php
                $var = $comparacao['variacao_ticket'];
                $class = $var >= 0 ? 'success' : 'danger';
                $icon = $var >= 0 ? 'up' : 'down';
                ?>
                <h4 class="text-<?= $class ?>">
                    <i class="fas fa-arrow-<?= $icon ?>"></i>
                    <?= number_format(abs($var), 2) ?>%
                </h4>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>
    Selecione dois períodos para comparar.
</div>
<?php endif; ?>

<?= $this->endSection() ?>
