<?php if(session('alert')): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-<?= session('alert')['type'] ?> alert-dismissible fade show" role="alert">
                <strong><?= session('alert')['title'] ?></strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Título e Ações -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="card-title">
                            <i class="<?= $titulo['icone'] ?>"></i> 
                            <?= $titulo['modulo'] ?>
                        </h4>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="/admin/novoContador" class="btn btn-success">
                            <i class="fa fa-user-plus"></i> Novo Contador
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Pesquisa -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fa fa-filter"></i> Filtros de Pesquisa</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nome do Contador</label>
                                <input type="text" name="nome" class="form-control" 
                                       placeholder="Digite o nome..." 
                                       value="<?= isset($nome) ? $nome : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>CNPJ</label>
                                <input type="text" name="cnpj" class="form-control" 
                                       placeholder="00.000.000/0000-00"
                                       value="<?= isset($cnpj) ? $cnpj : '' ?>"
                                       data-mask="00.000.000/0000-00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="Ativado" <?= isset($status) && $status == 'Ativado' ? 'selected' : '' ?>>Ativado</option>
                                    <option value="Desativado" <?= isset($status) && $status == 'Desativado' ? 'selected' : '' ?>>Desativado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Contadores -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if(empty($contadores)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum contador encontrado</h5>
                        <p class="text-muted">Comece cadastrando seu primeiro contador para vender o sistema!</p>
                        <a href="/admin/novoContador" class="btn btn-success">
                            <i class="fa fa-plus"></i> Cadastrar Primeiro Contador
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="80">#</th>
                                    <th>Nome / Escritório</th>
                                    <th>CNPJ</th>
                                    <th>Contato</th>
                                    <th width="120">Status</th>
                                    <th width="100">Empresas</th>
                                    <th width="200">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($contadores as $contador): ?>
                                <tr>
                                    <td><strong>#<?= $contador['id_contador'] ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?= $contador['nome'] ?></strong>
                                            <?php if($contador['nome_fantasia']): ?>
                                                <br><small class="text-muted"><?= $contador['nome_fantasia'] ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($contador['cnpj']): ?>
                                            <?php 
                                            $cnpj = $contador['cnpj'];
                                            echo substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2);
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if($contador['celular_1']): ?>
                                                <i class="fa fa-mobile-alt"></i> <?= $contador['celular_1'] ?><br>
                                            <?php endif; ?>
                                            <?php if($contador['email']): ?>
                                                <i class="fa fa-envelope"></i> <?= $contador['email'] ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($contador['status'] == 'Ativado'): ?>
                                            <span class="badge badge-success">
                                                <i class="fa fa-check"></i> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fa fa-times"></i> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="/admin/verEmpresas/<?= $contador['id_contador'] ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fa fa-building"></i> Ver
                                        </a>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/admin/editarContador/<?= $contador['id_contador'] ?>" 
                                               class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            
                                            <a href="/admin/alterarStatus/<?= $contador['id_contador'] ?>" 
                                               class="btn btn-sm <?= $contador['status'] == 'Ativado' ? 'btn-warning' : 'btn-success' ?>" 
                                               title="<?= $contador['status'] == 'Ativado' ? 'Desativar' : 'Ativar' ?>"
                                               onclick="return confirm('Tem certeza que deseja <?= $contador['status'] == 'Ativado' ? 'desativar' : 'ativar' ?> este contador?')">
                                                <i class="fa fa-<?= $contador['status'] == 'Ativado' ? 'ban' : 'check' ?>"></i>
                                            </a>
                                            
                                            <a href="/admin/excluirContador/<?= $contador['id_contador'] ?>" 
                                               class="btn btn-sm btn-danger" title="Excluir"
                                               onclick="return confirm('ATENÇÃO! Esta ação não pode ser desfeita. Tem certeza que deseja excluir este contador?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
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
// Aplicar máscara no CNPJ
$(document).ready(function() {
    $('[data-mask]').each(function() {
        var mask = $(this).attr('data-mask');
        $(this).mask(mask);
    });
});
</script>

<style>
.badge {
    font-size: 85%;
    padding: 0.375rem 0.75rem;
}

.btn-group .btn {
    margin-right: 2px;
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>