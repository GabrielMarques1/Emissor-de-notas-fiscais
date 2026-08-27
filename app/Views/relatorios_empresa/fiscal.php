<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title mb-0">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Relatório Fiscal
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
                <form method="GET" action="/relatorios-empresa/fiscal">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Data Início</label>
                                <input type="date" name="data_inicio" class="form-control" 
                                       value="<?= $filtros['data_inicio'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Data Fim</label>
                                <input type="date" name="data_fim" class="form-control" 
                                       value="<?= $filtros['data_fim'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
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

<!-- Tabela de Notas Fiscais -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listagem de Notas Fiscais</h5>
                <div class="card-tools">
                    <a href="/relatorios-empresa/exportar-fiscal-excel<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-success btn-sm mr-2">
                        <i class="fas fa-file-excel mr-1"></i>
                        Exportar Excel
                    </a>
                    <a href="/relatorios-empresa/exportar-fiscal-pdf<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf mr-1"></i>
                        Exportar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($notas_fiscais)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nenhuma nota fiscal encontrada. Use os filtros acima para buscar notas.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabelaFiscal" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Número</th>
                                    <th>Chave</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notas_fiscais as $nota): ?>
                                <tr>
                                    <td>
                                        <?php if ($nota['tipo_nota'] == 'NFe'): ?>
                                            <span class="badge badge-primary"><i class="fas fa-file-invoice"></i> NFe</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><i class="fas fa-receipt"></i> NFCe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= esc($nota['numero'] ?? 'N/A') ?></strong></td>
                                    <td>
                                        <small class="text-monospace"><?= substr(esc($nota['chave'] ?? 'N/A'), 0, 20) ?>...</small>
                                    </td>
                                    <td><?= !empty($nota['data']) ? date('d/m/Y', strtotime($nota['data'])) : 'N/A' ?></td>
                                    <td><?= esc($nota['hora'] ?? 'N/A') ?></td>
                                    <td><strong>R$ <?= number_format($nota['valor_da_nota'] ?? 0, 2, ',', '.') ?></strong></td>
                                    <td>
                                        <?php
                                        $status = $nota['status'] ?? 'N/A';
                                        if ($status == 'Autorizada' || $status == 100) {
                                            echo '<span class="badge badge-success"><i class="fas fa-check"></i> Autorizada</span>';
                                        } elseif ($status == 'Cancelada') {
                                            echo '<span class="badge badge-danger"><i class="fas fa-times"></i> Cancelada</span>';
                                        } else {
                                            echo '<span class="badge badge-secondary">' . esc($status) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($nota['xml'])): ?>
                                            <button class="btn btn-sm btn-success" title="Download XML">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
    $('#tabelaFiscal').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "order": [[3, "desc"]],
        "pageLength": 25
    });
});
</script>

<?= $this->endSection() ?>
