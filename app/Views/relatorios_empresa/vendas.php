<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Relatório de Vendas
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros Avançados -->
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
                <form method="GET" action="/relatorios-empresa/vendas">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="finalized" <?= ($filtros['status'] ?? '') == 'finalized' ? 'selected' : '' ?>>Finalizadas</option>
                                    <option value="cancelled" <?= ($filtros['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Canceladas</option>
                                    <option value="draft" <?= ($filtros['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Rascunho</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Forma de Pagamento</label>
                                <select name="payment_type" class="form-control">
                                    <option value="">Todas</option>
                                    <option value="cash" <?= ($filtros['payment_type'] ?? '') == 'cash' ? 'selected' : '' ?>>Dinheiro</option>
                                    <option value="debit" <?= ($filtros['payment_type'] ?? '') == 'debit' ? 'selected' : '' ?>>Débito</option>
                                    <option value="credit" <?= ($filtros['payment_type'] ?? '') == 'credit' ? 'selected' : '' ?>>Crédito</option>
                                    <option value="pix" <?= ($filtros['payment_type'] ?? '') == 'pix' ? 'selected' : '' ?>>PIX</option>
                                    <option value="voucher" <?= ($filtros['payment_type'] ?? '') == 'voucher' ? 'selected' : '' ?>>Voucher</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
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

<!-- Botões de Exportação -->
<?php if (!empty($vendas)): ?>
<div class="row mb-3">
    <div class="col-12 text-right">
        <div class="btn-group" role="group">
            <a href="/relatorios-empresa/exportar-vendas-excel?<?= http_build_query($filtros) ?>" 
               class="btn btn-success" target="_blank">
                <i class="fas fa-file-excel mr-2"></i>
                Exportar Excel
            </a>
            <a href="/relatorios-empresa/exportar-vendas-pdf?<?= http_build_query($filtros) ?>" 
               class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf mr-2"></i>
                Exportar PDF
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Totalizadores -->
<?php if (!empty($vendas)): ?>
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>R$ <?= number_format($totalizadores['total'], 2, ',', '.') ?></h3>
                <p>Total em Vendas</p>
            </div>
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $totalizadores['quantidade_vendas'] ?></h3>
                <p>Quantidade de Vendas</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>R$ <?= number_format($totalizadores['ticket_medio'], 2, ',', '.') ?></h3>
                <p>Ticket Médio</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>R$ <?= number_format($totalizadores['total_descontos'], 2, ',', '.') ?></h3>
                <p>Total em Descontos</p>
            </div>
            <div class="icon">
                <i class="fas fa-percent"></i>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabela de Vendas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Listagem de Vendas</h5>
                <div class="card-tools">
                    <button class="btn btn-success btn-sm" onclick="exportarExcel()">
                        <i class="fas fa-file-excel mr-1"></i>
                        Exportar Excel
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="exportarPDF()">
                        <i class="fas fa-file-pdf mr-1"></i>
                        Exportar PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($vendas)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nenhuma venda encontrada. Use os filtros acima para buscar vendas.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabelaVendas" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nº Venda</th>
                                    <th>Data/Hora</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Desconto</th>
                                    <th>Pagamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendas as $venda): ?>
                                <tr>
                                    <td><strong>#<?= esc($venda['sale_number'] ?? $venda['id_pos_sale']) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($venda['created_at'])) ?></td>
                                    <td>
                                        <?php if (!empty($venda['cliente_nome'])): ?>
                                            <?= esc($venda['cliente_nome']) ?><br>
                                            <small class="text-muted"><?= esc($venda['cliente_documento']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Consumidor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>R$ <?= number_format($venda['total'], 2, ',', '.') ?></strong></td>
                                    <td>R$ <?= number_format($venda['discount'] ?? 0, 2, ',', '.') ?></td>
                                    <td>
                                        <?php
                                        $paymentLabels = [
                                            'cash' => '<span class="badge badge-success"><i class="fas fa-money-bill"></i> Dinheiro</span>',
                                            'debit' => '<span class="badge badge-primary"><i class="fas fa-credit-card"></i> Débito</span>',
                                            'credit' => '<span class="badge badge-warning"><i class="fas fa-credit-card"></i> Crédito</span>',
                                            'pix' => '<span class="badge badge-info"><i class="fas fa-qrcode"></i> PIX</span>',
                                            'voucher' => '<span class="badge badge-secondary"><i class="fas fa-ticket-alt"></i> Voucher</span>'
                                        ];
                                        echo $paymentLabels[$venda['payment_type']] ?? '<span class="badge badge-secondary">N/A</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusLabels = [
                                            'finalized' => '<span class="badge badge-success">Finalizada</span>',
                                            'cancelled' => '<span class="badge badge-danger">Cancelada</span>',
                                            'draft' => '<span class="badge badge-secondary">Rascunho</span>'
                                        ];
                                        echo $statusLabels[$venda['status']] ?? '<span class="badge badge-secondary">N/A</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="verDetalhes(<?= $venda['id_pos_sale'] ?>)">
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
    $('#tabelaVendas').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "order": [[1, "desc"]],
        "pageLength": 25
    });
});

function verDetalhes(idVenda) {
    alert('Funcionalidade de detalhes em desenvolvimento - Venda #' + idVenda);
}

function exportarExcel() {
    window.location.href = '/relatorios-empresa/exportar-vendas-excel<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>';
}

function exportarPDF() {
    window.location.href = '/relatorios-empresa/exportar-vendas-pdf<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>';
}
</script>

<?= $this->endSection() ?>
