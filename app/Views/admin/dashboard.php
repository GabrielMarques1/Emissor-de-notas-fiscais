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

<!-- Título da página -->
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
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fa fa-info-circle"></i>
                    Bem-vindo ao painel administrativo! Aqui você tem controle total sobre todos os contadores e empresas da plataforma.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-users fa-3x"></i>
                    </div>
                    <div class="col-9 text-right">
                        <h3 class="mb-0"><?= $total_contadores ?></h3>
                        <p class="mb-0">Total de Contadores</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span class="text-white-50">
                    <i class="fa fa-check-circle"></i> 
                    <?= $contadores_ativos ?> ativos
                </span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-building fa-3x"></i>
                    </div>
                    <div class="col-9 text-right">
                        <h3 class="mb-0"><?= $total_empresas ?></h3>
                        <p class="mb-0">Total de Empresas</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span class="text-white-50">
                    <i class="fa fa-check-circle"></i> 
                    <?= $empresas_ativas ?> ativas
                </span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-chart-line fa-3x"></i>
                    </div>
                    <div class="col-9 text-right">
                        <h3 class="mb-0">R$ 0,00</h3>
                        <p class="mb-0">Faturamento Mensal</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span class="text-white-50">
                    <i class="fa fa-calendar"></i> 
                    Este mês
                </span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-file-invoice fa-3x"></i>
                    </div>
                    <div class="col-9 text-right">
                        <h3 class="mb-0">0</h3>
                        <p class="mb-0">Notas Emitidas Hoje</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span class="text-white-50">
                    <i class="fa fa-clock"></i> 
                    Últimas 24h
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="fa fa-bolt"></i> Ações Rápidas
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="/admin/novoContador" class="btn btn-success btn-block">
                            <i class="fa fa-user-plus"></i><br>
                            Novo Contador
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/contadores" class="btn btn-primary btn-block">
                            <i class="fa fa-users"></i><br>
                            Gerenciar Contadores
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/configuracoes" class="btn btn-warning btn-block">
                            <i class="fa fa-cogs"></i><br>
                            Configurações
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/relatorios/admin" class="btn btn-info btn-block">
                            <i class="fa fa-chart-bar"></i><br>
                            Relatórios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Últimos Contadores Cadastrados -->
<?php if($total_contadores > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="fa fa-clock"></i> Atividade Recente
                </h4>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <p class="text-muted">
                        <i class="fa fa-info-circle"></i>
                        Sistema funcionando normalmente. <?= $total_contadores ?> contadores cadastrados e <?= $total_empresas ?> empresas ativas na plataforma.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.btn-block {
    display: block;
    width: 100%;
    padding: 20px;
    text-align: center;
    margin-bottom: 10px;
}

.btn-block i {
    font-size: 24px;
    margin-bottom: 10px;
}

.bg-primary { background-color: #007bff !important; }
.bg-success { background-color: #28a745 !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-info { background-color: #17a2b8 !important; }

.card-footer {
    padding: 0.5rem 1.25rem;
    background-color: rgba(0, 0, 0, 0.03);
    border-top: 1px solid rgba(0, 0, 0, 0.125);
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.75) !important;
}
</style>