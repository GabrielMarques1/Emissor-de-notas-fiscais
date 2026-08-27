<?php
echo view('templates/header');
echo view('templates/navbar');
echo view('templates/sidebar');
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard de Teste</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/inicio/admin">Dashboard Master</a></li>
                        <li class="breadcrumb-item active">Teste</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Teste Simples -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title text-white">
                                <i class="fas fa-check"></i>
                                Dashboard Funcionando!
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h4><i class="icon fas fa-check"></i> Sucesso!</h4>
                                Se você está vendo esta mensagem, o dashboard está carregando corretamente.
                            </div>
                            
                            <h5>Informações do Sistema:</h5>
                            <ul>
                                <li><strong>Data/Hora:</strong> <?= date('Y-m-d H:i:s') ?></li>
                                <li><strong>Usuário:</strong> <?= session('usuario') ?? 'N/A' ?></li>
                                <li><strong>Tipo:</strong> <?= session('tipo') ?? 'N/A' ?></li>
                                <li><strong>Tenant ID:</strong> <?= session('tenant_id') ?? 'N/A' ?></li>
                                <li><strong>Master Access:</strong> <?= session('is_master_access') ? 'Sim' : 'Não' ?></li>
                            </ul>
                            
                            <div class="mt-3">
                                <button class="btn btn-primary" onclick="alert('Botão funcionando!')">
                                    <i class="fas fa-play"></i> Testar JavaScript
                                </button>
                                <a href="/inicio/admin" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Voltar ao Master
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php echo view('templates/footer'); ?>
