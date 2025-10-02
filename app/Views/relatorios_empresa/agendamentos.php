<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-cyan text-white">
                <h3 class="card-title mb-0"><i class="fas fa-calendar-alt mr-2"></i>Agendamentos de Relatórios</h3>
            </div>
        </div>
    </div>
</div>

<!-- Botão Novo Agendamento -->
<div class="row mb-3">
    <div class="col-12">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovoAgendamento">
            <i class="fas fa-plus mr-2"></i>Novo Agendamento
        </button>
    </div>
</div>

<!-- Lista de Agendamentos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (empty($agendamentos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nenhum agendamento criado. Clique em "Novo Agendamento" para criar um.
                    </div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tipo de Relatório</th>
                                <th>Frequência</th>
                                <th>Email(s)</th>
                                <th>Próximo Envio</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $ag): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php
                                        $tipos = [
                                            'vendas' => 'Vendas',
                                            'produtos' => 'Produtos',
                                            'turnos' => 'Turnos',
                                            'fiscal' => 'Fiscal',
                                            'estoque' => 'Estoque'
                                        ];
                                        echo $tipos[$ag['report_type']] ?? $ag['report_type'];
                                        ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $freq = [
                                        'daily' => 'Diário',
                                        'weekly' => 'Semanal',
                                        'monthly' => 'Mensal'
                                    ];
                                    echo $freq[$ag['frequency']] ?? $ag['frequency'];
                                    ?>
                                </td>
                                <td><?= esc($ag['email_recipients']) ?></td>
                                <td>
                                    <?php if (!empty($ag['next_run']) && $ag['next_run'] != '0000-00-00 00:00:00'): ?>
                                        <?= date('d/m/Y H:i', strtotime($ag['next_run'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não agendado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ag['is_active']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/relatorios-empresa/agendamentos/excluir/<?= $ag['id_schedule'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Deseja realmente excluir este agendamento?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Agendamento -->
<div class="modal fade" id="modalNovoAgendamento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/relatorios-empresa/agendamentos/salvar">
                <div class="modal-header bg-cyan text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus mr-2"></i>Novo Agendamento</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tipo de Relatório *</label>
                        <select name="report_type" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="vendas">Relatório de Vendas</option>
                            <option value="produtos">Relatório de Produtos</option>
                            <option value="turnos">Relatório de Turnos</option>
                            <option value="fiscal">Relatório Fiscal</option>
                            <option value="estoque">Alertas de Estoque</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Frequência *</label>
                        <select name="frequency" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="daily">Diário</option>
                            <option value="weekly">Semanal (toda Segunda)</option>
                            <option value="monthly">Mensal (dia 1)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Formato *</label>
                        <select name="format" class="form-control" required>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Email(s) dos Destinatários *</label>
                        <input type="text" name="email_recipients" class="form-control" 
                               placeholder="email1@exemplo.com, email2@exemplo.com" required>
                        <small class="form-text text-muted">Separe múltiplos emails com vírgula</small>
                    </div>

                    <div class="form-group">
                        <label>Horário de Envio</label>
                        <input type="time" name="schedule_time" class="form-control" value="08:00">
                    </div>

                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                        <label class="custom-control-label" for="is_active">Ativar agendamento imediatamente</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Salvar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>