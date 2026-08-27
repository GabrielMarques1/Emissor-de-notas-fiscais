<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-box mr-2"></i>
                    Relatório de Produtos
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
                <form method="GET" action="/relatorios-empresa/produtos">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Nome do Produto</label>
                                <input type="text" name="nome" class="form-control" 
                                       placeholder="Digite o nome do produto..."
                                       value="<?= $filtros['nome'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control" 
                                       placeholder="Digite o código de barras..."
                                       value="<?= $filtros['codigo_barras'] ?? '' ?>">
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

<!-- Tabela de Produtos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listagem de Produtos</h5>
                <div class="card-tools">
                    <a href="/relatorios-empresa/exportar-produtos-excel<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-success btn-sm mr-2">
                        <i class="fas fa-file-excel mr-1"></i>
                        Exportar Excel
                    </a>
                    <a href="/relatorios-empresa/exportar-produtos-pdf<?= !empty($filtros) ? '?' . http_build_query($filtros) : '' ?>" 
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf mr-1"></i>
                        Exportar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($produtos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nenhum produto encontrado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabelaProdutos" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nome</th>
                                    <th>Código de Barras</th>
                                    <th>Valor Unitário</th>
                                    <th>Estoque</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><strong>#<?= esc($produto['id_produto']) ?></strong></td>
                                    <td><?= esc($produto['nome']) ?></td>
                                    <td><?= esc($produto['codigo_barras'] ?? 'N/A') ?></td>
                                    <td><strong>R$ <?= number_format($produto['valor_unitario'] ?? 0, 2, ',', '.') ?></strong></td>
                                    <td>
                                        <?php 
                                        $estoque = $produto['estoque'] ?? 0;
                                        $class = $estoque > 10 ? 'success' : ($estoque > 0 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge badge-<?= $class ?>"><?= $estoque ?> un</span>
                                    </td>
                                    <td>
                                        <?= ($produto['status'] ?? 'ativo') == 'ativo' 
                                            ? '<span class="badge badge-success">Ativo</span>' 
                                            : '<span class="badge badge-secondary">Inativo</span>' ?>
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
    $('#tabelaProdutos').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "pageLength": 25
    });
});
</script>

<?= $this->endSection() ?>
