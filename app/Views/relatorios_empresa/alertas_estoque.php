<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $titulo['modulo'] ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Alertas de Estoque</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (empty($alertas)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Nenhum alerta de estoque!</div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Produto</th>
                                <th>Código</th>
                                <th>Estoque Atual</th>
                                <th>Estoque Mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertas as $alerta): ?>
                            <tr>
                                <td>
                                    <?php if ($alerta['alert_type'] == 'out_of_stock'): ?>
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> SEM ESTOQUE</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i class="fas fa-exclamation"></i> ESTOQUE BAIXO</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= esc($alerta['produto_nome']) ?></strong></td>
                                <td><?= esc($alerta['codigo_de_barras']) ?></td>
                                <td><span class="badge badge-danger"><?= $alerta['current_stock'] ?></span></td>
                                <td><?= $alerta['threshold'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
