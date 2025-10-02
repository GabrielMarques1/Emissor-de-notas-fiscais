<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-teal text-white">
                <h3 class="card-title mb-0"><i class="fas fa-user-friends mr-2"></i>Clientes Mais Frequentes</h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="form-inline">
                    <label class="mr-2">Data Início:</label>
                    <input type="date" name="data_inicio" class="form-control mr-3" value="<?= $filtros['data_inicio'] ?? '' ?>">
                    <label class="mr-2">Data Fim:</label>
                    <input type="date" name="data_fim" class="form-control mr-3" value="<?= $filtros['data_fim'] ?? '' ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Filtrar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listagem de Clientes</h5>
                <div class="card-tools">
                    <a href="/relatorios-empresa/exportar-clientes-excel<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-success btn-sm mr-2">
                        <i class="fas fa-file-excel mr-1"></i>
                        Exportar Excel
                    </a>
                    <a href="/relatorios-empresa/exportar-clientes-pdf<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf mr-1"></i>
                        Exportar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($clientes)): ?>
                    <div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>Nenhum cliente encontrado.</div>
                <?php else: ?>
                    <table id="tabelaClientes" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Total de Compras</th>
                                <th>Valor Total Gasto</th>
                                <th>Ticket Médio</th>
                                <th>Última Compra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><strong><?= esc($cliente['nome']) ?></strong></td>
                                <td><?= esc($cliente['documento']) ?></td>
                                <td><span class="badge badge-primary"><?= $cliente['total_compras'] ?></span></td>
                                <td><strong>R$ <?= number_format($cliente['total_gasto'], 2, ',', '.') ?></strong></td>
                                <td>R$ <?= number_format($cliente['ticket_medio'], 2, ',', '.') ?></td>
                                <td><?= date('d/m/Y', strtotime($cliente['ultima_compra'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#tabelaClientes').DataTable({
        "language": {"url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"},
        "order": [[2, "desc"]]
    });
});
</script>

<?= $this->endSection() ?>
