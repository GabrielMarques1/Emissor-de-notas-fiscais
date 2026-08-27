<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-pink text-white">
                <h3 class="card-title mb-0"><i class="fas fa-palette mr-2"></i>Customizar Dashboard</h3>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="/relatorios-empresa/customizar">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-th mr-2"></i>Widgets Disponíveis</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Selecione os widgets que deseja exibir no seu dashboard:</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_vendas" 
                                       name="widgets[]" value="vendas" 
                                       <?= in_array('vendas', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_vendas">
                                    <strong>💰 Total de Vendas</strong>
                                    <br><small class="text-muted">Exibe o total de vendas do período</small>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_ticket" 
                                       name="widgets[]" value="ticket" 
                                       <?= in_array('ticket', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_ticket">
                                    <strong>🎯 Ticket Médio</strong>
                                    <br><small class="text-muted">Valor médio por venda</small>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_produtos" 
                                       name="widgets[]" value="produtos" 
                                       <?= in_array('produtos', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_produtos">
                                    <strong>📦 Produtos Mais Vendidos</strong>
                                    <br><small class="text-muted">Top 10 produtos por quantidade</small>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_pagamentos" 
                                       name="widgets[]" value="pagamentos" 
                                       <?= in_array('pagamentos', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_pagamentos">
                                    <strong>💳 Vendas por Forma de Pagamento</strong>
                                    <br><small class="text-muted">Distribuição por tipo de pagamento</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_turnos" 
                                       name="widgets[]" value="turnos" 
                                       <?= in_array('turnos', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_turnos">
                                    <strong>🕐 Turnos Ativos</strong>
                                    <br><small class="text-muted">Status dos turnos de caixa</small>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_estoque" 
                                       name="widgets[]" value="estoque" 
                                       <?= in_array('estoque', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_estoque">
                                    <strong>⚠️ Alertas de Estoque</strong>
                                    <br><small class="text-muted">Produtos com estoque baixo</small>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_grafico" 
                                       name="widgets[]" value="grafico" 
                                       <?= in_array('grafico', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_grafico">
                                    <strong>📈 Gráfico de Evolução</strong>
                                    <br><small class="text-muted">Evolução das vendas nos últimos 30 dias</small>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="widget_clientes" 
                                       name="widgets[]" value="clientes" 
                                       <?= in_array('clientes', $config['widgets'] ?? []) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="widget_clientes">
                                    <strong>👥 Top Clientes</strong>
                                    <br><small class="text-muted">Clientes que mais compraram</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-paint-brush mr-2"></i>Tema do Dashboard</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Esquema de Cores</label>
                        <select name="theme" class="form-control">
                            <option value="default" <?= ($config['theme'] ?? 'default') == 'default' ? 'selected' : '' ?>>Padrão (Azul)</option>
                            <option value="dark" <?= ($config['theme'] ?? '') == 'dark' ? 'selected' : '' ?>>Escuro</option>
                            <option value="success" <?= ($config['theme'] ?? '') == 'success' ? 'selected' : '' ?>>Verde</option>
                            <option value="purple" <?= ($config['theme'] ?? '') == 'purple' ? 'selected' : '' ?>>Roxo</option>
                            <option value="orange" <?= ($config['theme'] ?? '') == 'orange' ? 'selected' : '' ?>>Laranja</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar mr-2"></i>Período Padrão</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Período de Dados</label>
                        <select name="default_period" class="form-control">
                            <option value="today" <?= ($config['default_period'] ?? 'today') == 'today' ? 'selected' : '' ?>>Hoje</option>
                            <option value="week" <?= ($config['default_period'] ?? '') == 'week' ? 'selected' : '' ?>>Últimos 7 dias</option>
                            <option value="month" <?= ($config['default_period'] ?? '') == 'month' ? 'selected' : '' ?>>Últimos 30 dias</option>
                            <option value="year" <?= ($config['default_period'] ?? '') == 'year' ? 'selected' : '' ?>>Último ano</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-2"></i>Salvar Configurações
            </button>
            <a href="/relatorios-empresa" class="btn btn-secondary btn-lg">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </div>
</form>

<?= $this->endSection() ?>