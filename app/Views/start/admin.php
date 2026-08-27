<?php 
    $session = session();
    $tipo = $session->get('tipo');

    if($tipo != 1) // Verifica se o tipo de usuário tem permissão para acessar a página
    {
        echo "<script>window.location.href = '/erro-permissao-de-acesso'; </script>";
    }
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <h4>Seja bem vindo!</h4>
            
            <!-- Visão Geral do Sistema -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title text-white">
                                <i class="fas fa-chart-line"></i>
                                Visão Geral do Sistema
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3><?= isset($system_overview) ? $system_overview['active_contadores'] : '0' ?></h3>
                                            <p>Contadores Ativos</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3><?= isset($system_overview) ? $system_overview['active_empresas'] : '0' ?></h3>
                                            <p>Empresas Ativas</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-industry"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3><?= isset($system_overview) ? $system_overview['today_logins'] : '0' ?></h3>
                                            <p>Logins Hoje</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-secondary">
                                        <div class="inner">
                                            <h3><?= isset($system_overview) ? $system_overview['uptime'] : '0h 0m' ?></h3>
                                            <p>Uptime Hoje</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboards Disponíveis -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark">
                            <h3 class="card-title text-white">
                                <i class="fas fa-th-large"></i>
                                Dashboards de Monitoramento
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if (isset($dashboards_status)): ?>
                                    <?php foreach ($dashboards_status as $key => $dashboard): ?>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <div class="card h-100 dashboard-card" style="transition: transform 0.2s;">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="<?= $dashboard['icon'] ?> fa-3x text-<?= $dashboard['color'] ?>"></i>
                                                </div>
                                                <h5 class="card-title"><?= $dashboard['name'] ?></h5>
                                                <p class="card-text text-muted"><?= $dashboard['description'] ?></p>
                                                <div class="mb-3">
                                                    <span class="badge badge-<?= $dashboard['status'] === 'active' ? 'success' : 'secondary' ?> badge-lg">
                                                        <?= strtoupper($dashboard['status']) ?>
                                                    </span>
                                                </div>
                                                <a href="<?= $dashboard['url'] ?>" class="btn btn-<?= $dashboard['color'] ?> btn-block">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    Acessar Dashboard
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            Dashboards de monitoramento serão carregados em breve...
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .dashboard-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }

            .badge-lg {
                font-size: 0.9em;
                padding: 0.5em 0.8em;
            }

            .small-box {
                border-radius: 0.5rem;
            }

            .card {
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            </style>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->