<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            
            <!-- Título de Boas-vindas -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-primary">
                                <i class="fa fa-crown"></i> Painel do Administrador
                            </h2>
                            <p class="text-muted">
                                Bem-vindo ao controle total do seu Sistema SaaS de Notas Fiscais!<br>
                                Aqui você gerencia todos os contadores e empresas da plataforma.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações Principais -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <a href="/admin" class="btn btn-primary btn-lg btn-block">
                                <i class="fa fa-tachometer-alt fa-2x mb-2"></i><br>
                                Dashboard Completo
                            </a>
                            <p class="text-muted mt-2">Estatísticas detalhadas</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <a href="/admin/contadores" class="btn btn-success btn-lg btn-block">
                                <i class="fa fa-users fa-2x mb-2"></i><br>
                                Gerenciar Contadores
                            </a>
                            <p class="text-muted mt-2">Cadastrar e gerenciar</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <a href="/admin/novoContador" class="btn btn-warning btn-lg btn-block">
                                <i class="fa fa-user-plus fa-2x mb-2"></i><br>
                                Novo Contador
                            </a>
                            <p class="text-muted mt-2">Cadastrar cliente novo</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <a href="/configuracoes" class="btn btn-info btn-lg btn-block">
                                <i class="fa fa-cogs fa-2x mb-2"></i><br>
                                Configurações
                            </a>
                            <p class="text-muted mt-2">Sistema global</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações Importantes -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fa fa-lightbulb"></i> Como Vender Seu Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fa fa-1"></i> Cadastre um Contador</h6>
                                    <p>Crie o login e senha para o escritório contábil que vai usar o sistema.</p>
                                    
                                    <h6><i class="fa fa-2"></i> O Contador Cadastra Empresas</h6>
                                    <p>Cada contador pode gerenciar múltiplas empresas clientes.</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fa fa-3"></i> Empresas Emitem Notas</h6>
                                    <p>Cada empresa pode emitir NFe e NFC-e com seu próprio certificado.</p>
                                    
                                    <h6><i class="fa fa-chart-line"></i> Você Monitora Tudo</h6>
                                    <p>Acompanhe o uso, estatísticas e gerencie toda a plataforma.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<style>
.btn-lg {
    padding: 1rem 1.5rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.text-primary {
    color: #007bff !important;
}

.fa-crown {
    color: #ffd700;
}
</style>