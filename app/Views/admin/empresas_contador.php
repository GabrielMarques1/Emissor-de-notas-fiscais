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

<!-- Informações do Contador -->
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
                        <a href="/admin/contadores" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Voltar aos Contadores
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fa fa-user"></i> <?= $contador['nome'] ?></h5>
                        <?php if($contador['nome_fantasia']): ?>
                            <p class="text-muted"><?= $contador['nome_fantasia'] ?></p>
                        <?php endif; ?>
                        <?php if($contador['cnpj']): ?>
                            <p><strong>CNPJ:</strong> 
                            <?php 
                            $cnpj = $contador['cnpj'];
                            echo substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2);
                            ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="text-right">
                            <?php if($contador['status'] == 'Ativado'): ?>
                                <span class="badge badge-success badge-lg">
                                    <i class="fa fa-check"></i> Contador Ativo
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger badge-lg">
                                    <i class="fa fa-times"></i> Contador Inativo
                                </span>
                            <?php endif; ?>
                            <br><br>
                            <?php if($contador['email']): ?>
                                <p><i class="fa fa-envelope"></i> <?= $contador['email'] ?></p>
                            <?php endif; ?>
                            <?php if($contador['celular_1']): ?>
                                <p><i class="fa fa-mobile-alt"></i> <?= $contador['celular_1'] ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="row">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?= count($empresas) ?></h3>
                <p>Total de Empresas</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <?php 
                $empresas_ativas = 0;
                foreach($empresas as $empresa) {
                    if($empresa['status'] == 'Ativado') $empresas_ativas++;
                }
                ?>
                <h3><?= $empresas_ativas ?></h3>
                <p>Empresas Ativas</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3><?= count($empresas) - $empresas_ativas ?></h3>
                <p>Empresas Inativas</p>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Empresas -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fa fa-building"></i> Empresas Cadastradas</h5>
            </div>
            <div class="card-body">
                <?php if(empty($empresas)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-building fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Este contador ainda não possui empresas</h5>
                        <p class="text-muted">Quando o contador cadastrar empresas, elas aparecerão aqui.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="80">#</th>
                                    <th>Empresa</th>
                                    <th>CNPJ</th>
                                    <th width="100">Status</th>
                                    <th width="120">Ambiente NFe</th>
                                    <th width="120">Ambiente NFC-e</th>
                                    <th width="120">Data Cadastro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($empresas as $empresa): ?>
                                <tr>
                                    <td><strong>#<?= $empresa['id_empresa'] ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?= $empresa['xNome'] ?></strong>
                                            <?php if($empresa['xFant']): ?>
                                                <br><small class="text-muted"><?= $empresa['xFant'] ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $cnpj = $empresa['CNPJ'];
                                        echo substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2);
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($empresa['status'] == 'Ativado'): ?>
                                            <span class="badge badge-success">
                                                <i class="fa fa-check"></i> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fa fa-times"></i> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($empresa['tpAmb_NFe'] == 1): ?>
                                            <span class="badge badge-success">
                                                <i class="fa fa-globe"></i> Produção
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fa fa-flask"></i> Homologação
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($empresa['tpAmb_NFCe'] == 1): ?>
                                            <span class="badge badge-success">
                                                <i class="fa fa-globe"></i> Produção
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fa fa-flask"></i> Homologação
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($empresa['created_at']): ?>
                                            <?= date('d/m/Y', strtotime($empresa['created_at'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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

<style>
.badge-lg {
    font-size: 100%;
    padding: 0.5rem 1rem;
}

.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

.bg-primary { background-color: #007bff !important; }
.bg-success { background-color: #28a745 !important; }
.bg-warning { background-color: #ffc107 !important; }
</style>