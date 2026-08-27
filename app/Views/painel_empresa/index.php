<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $title ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-12">
        <!-- Header com informações da empresa -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1">
                            <i class="fas fa-building text-primary"></i>
                            Bem-vindo ao Sistema ERP
                        </h4>
                        <p class="text-muted mb-0">
                            <strong><?= esc($empresa['xFant']) ?></strong> • 
                            Usuário: <?= esc($usuario) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <div class="btn-group" role="group">
                            <a href="/painel/empresa/pdv" class="btn btn-success">
                                <i class="fas fa-cash-register"></i> Acessar PDV
                            </a>
                            <a href="/usuarios-caixa" class="btn btn-primary">
                                <i class="fas fa-users"></i> Gerenciar Caixas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de acesso rápido -->
        <div class="row">
            <!-- Gestão de Caixas -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-users fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Usuários Caixa</h5>
                        <p class="card-text text-muted">
                            Gerencie operadores do PDV, crie logins e controle permissões de acesso.
                        </p>
                        <a href="/usuarios-caixa" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Gerenciar
                        </a>
                    </div>
                </div>
            </div>

            <!-- PDV -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-cash-register fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">Ponto de Venda</h5>
                        <p class="card-text text-muted">
                            Acesse o sistema de PDV para processar vendas e gerenciar o caixa.
                        </p>
                        <a href="/painel/empresa/pdv" class="btn btn-success">
                            <i class="fas fa-play"></i> Abrir PDV
                        </a>
                    </div>
                </div>
            </div>

            <!-- Produtos -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-boxes fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">Produtos</h5>
                        <p class="card-text text-muted">
                            Cadastre e gerencie produtos, preços, estoque e códigos de barras.
                        </p>
                        <a href="/produtos" class="btn btn-info">
                            <i class="fas fa-box"></i> Gerenciar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Clientes -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-friends fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">Clientes</h5>
                        <p class="card-text text-muted">
                            Cadastre clientes, gerencie dados e histórico de compras.
                        </p>
                        <a href="/clientes" class="btn btn-warning">
                            <i class="fas fa-address-book"></i> Gerenciar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Nota Fiscal -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-file-invoice fa-3x text-purple"></i>
                        </div>
                        <h5 class="card-title">Nota Fiscal</h5>
                        <p class="card-text text-muted">
                            Emita NF-e, NFC-e e gerencie documentos fiscais da empresa.
                        </p>
                        <a href="/nfe" class="btn btn-outline-primary">
                            <i class="fas fa-receipt"></i> Gerenciar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Relatórios -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-chart-line fa-3x text-danger"></i>
                        </div>
                        <h5 class="card-title">Relatórios</h5>
                        <p class="card-text text-muted">
                            Visualize vendas, estoque, faturamento e análises gerenciais.
                        </p>
                        <a href="/relatorios" class="btn btn-danger">
                            <i class="fas fa-chart-bar"></i> Ver Relatórios
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Separação clara entre ERP e PDV -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Controle de Acesso
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">
                                    <i class="fas fa-user-shield"></i> Seu Acesso (Gerente/Dono)
                                </h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Sistema ERP completo</li>
                                    <li><i class="fas fa-check text-success"></i> Gestão de usuários caixa</li>
                                    <li><i class="fas fa-check text-success"></i> Acesso ao PDV</li>
                                    <li><i class="fas fa-check text-success"></i> Relatórios gerenciais</li>
                                    <li><i class="fas fa-check text-success"></i> Configurações do sistema</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">
                                    <i class="fas fa-user"></i> Acesso dos Caixas
                                </h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-info"></i> Apenas o sistema PDV</li>
                                    <li><i class="fas fa-check text-info"></i> Processar vendas</li>
                                    <li><i class="fas fa-check text-info"></i> Consultar produtos</li>
                                    <li><i class="fas fa-check text-info"></i> Imprimir cupons</li>
                                    <li><i class="fas fa-times text-danger"></i> Sem acesso ao ERP</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="/usuarios-caixa" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus"></i> Criar Novo Usuário Caixa
                            </a>
                            <a href="/login-pdv" class="btn btn-outline-success btn-sm" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Abrir Login PDV (Nova Aba)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-purple {
    color: #6f42c1 !important;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.fa-3x {
    margin-bottom: 15px;
}
</style>

<?= $this->endSection() ?>
