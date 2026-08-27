<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-clock mr-2"></i>
                    Relatório de Turnos
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
                    Filtros de Pesquisa
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="/relatorios-empresa/turnos">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Data Início</label>
                                <input type="date" name="data_inicio" class="form-control" 
                                       value="<?= $filtros['data_inicio'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Data Fim</label>
                                <input type="date" name="data_fim" class="form-control" 
                                       value="<?= $filtros['data_fim'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="open" <?= ($filtros['status'] ?? '') == 'open' ? 'selected' : '' ?>>Aberto</option>
                                    <option value="closed" <?= ($filtros['status'] ?? '') == 'closed' ? 'selected' : '' ?>>Fechado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search mr-1"></i>
                                    Pesquisar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Turnos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listagem de Turnos</h5>
                <div class="card-tools">
                    <a href="/relatorios-empresa/exportar-turnos-excel<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-success btn-sm mr-2">
                        <i class="fas fa-file-excel mr-1"></i>
                        Exportar Excel
                    </a>
                    <a href="/relatorios-empresa/exportar-turnos-pdf<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf mr-1"></i>
                        Exportar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($turnos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nenhum turno encontrado. Use os filtros acima para buscar turnos.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabelaTurnos" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Caixa</th>
                                    <th>Aberto Por</th>
                                    <th>Abertura</th>
                                    <th>Fechado Por</th>
                                    <th>Fechamento</th>
                                    <th>Valor Inicial</th>
                                    <th>Valor Final</th>
                                    <th>Diferença</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($turnos as $turno): ?>
                                <tr>
                                    <td><strong><?= esc($turno['caixa_nome'] ?? 'N/A') ?></strong></td>
                                    <td><?= esc($turno['aberto_por_usuario'] ?? 'N/A') ?></td>
                                    <td><?= !empty($turno['opened_at']) ? date('d/m/Y H:i', strtotime($turno['opened_at'])) : 'N/A' ?></td>
                                    <td><?= esc($turno['fechado_por_usuario'] ?? '-') ?></td>
                                    <td><?= !empty($turno['closed_at']) ? date('d/m/Y H:i', strtotime($turno['closed_at'])) : '-' ?></td>
                                    <td>R$ <?= number_format($turno['opening_amount'] ?? 0, 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($turno['closing_amount'] ?? 0, 2, ',', '.') ?></td>
                                    <td>
                                        <?php 
                                        $diferenca = ($turno['closing_amount'] ?? 0) - ($turno['opening_amount'] ?? 0);
                                        $class = $diferenca >= 0 ? 'success' : 'danger';
                                        ?>
                                        <strong class="text-<?= $class ?>">
                                            R$ <?= number_format($diferenca, 2, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?= $turno['status'] == 'open' 
                                            ? '<span class="badge badge-success"><i class="fas fa-lock-open"></i> Aberto</span>' 
                                            : '<span class="badge badge-secondary"><i class="fas fa-lock"></i> Fechado</span>' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#tabelaTurnos').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "order": [[2, "desc"]],
        "pageLength": 25
    });
});
</script>

<?= $this->endSection() ?>
