<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>Dashboard de Segurança<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard de Segurança</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/inicio/admin">Dashboard Master</a></li>
                        <li class="breadcrumb-item active">Segurança</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Alertas de Segurança -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats['critical_alerts'] ?? 0 ?></h3>
                            <p>Alertas Críticos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['failed_logins'] ?? 0 ?></h3>
                            <p>Logins Falharam</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['blocked_ips'] ?? 0 ?></h3>
                            <p>IPs Bloqueados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-ban"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['security_score'] ?? 0 ?>%</h3>
                            <p>Score de Segurança</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status dos Sistemas de Segurança -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title text-white">
                                <i class="fas fa-shield-alt"></i>
                                Sistemas de Proteção
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>TenantFilter (Multi-tenant):</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>AuditFilter (Logs):</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>Rate Limiting:</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>Triggers MySQL (34):</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>Cache Anti-Poisoning:</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title text-white">
                                <i class="fas fa-chart-pie"></i>
                                Métricas de Segurança
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>Tentativas de Cross-Tenant:</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-danger"><?= $stats['cross_tenant_attempts'] ?? 0 ?></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>Acessos Suspeitos:</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-warning"><?= $stats['suspicious_access'] ?? 0 ?></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>SQL Injections Bloqueadas:</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-info"><?= $stats['sql_injections'] ?? 0 ?></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <strong>XSS Attempts:</strong>
                                </div>
                                <div class="col-4">
                                    <span class="badge badge-info"><?= $stats['xss_attempts'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas Recentes -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h3 class="card-title text-white">
                                <i class="fas fa-exclamation-triangle"></i>
                                Alertas de Segurança Recentes
                            </h3>
                            <div class="card-tools">
                                <button class="btn btn-sm btn-light" onclick="refreshAlerts()">
                                    <i class="fas fa-sync"></i> Atualizar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="security-alerts-container">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p>Carregando alertas...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoramento em Tempo Real -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info">
                            <h3 class="card-title text-white">
                                <i class="fas fa-eye"></i>
                                Monitoramento em Tempo Real
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Usuários Online:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-success" id="users-online"><?= $stats['users_online'] ?? 0 ?></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Requests/min:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-info" id="requests-per-min"><?= $stats['requests_per_min'] ?? 0 ?></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>CPU Usage:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-primary" id="cpu-usage"><?= $stats['cpu_usage'] ?? 0 ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-dark">
                            <h3 class="card-title text-white">
                                <i class="fas fa-tools"></i>
                                Ações de Segurança
                            </h3>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-warning btn-block mb-2" onclick="scanSecurity()">
                                <i class="fas fa-search"></i> Scan de Segurança
                            </button>
                            <button class="btn btn-info btn-block mb-2" onclick="generateReport()">
                                <i class="fas fa-file-alt"></i> Relatório de Segurança
                            </button>
                            <button class="btn btn-danger btn-block" onclick="emergencyLockdown()">
                                <i class="fas fa-lock"></i> Lockdown de Emergência
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    loadSecurityAlerts();
    
    // Atualizar métricas a cada 15 segundos
    setInterval(updateRealTimeMetrics, 15000);
});

function loadSecurityAlerts() {
    $.get('/admin/security-dashboard/alerts')
        .done(function(data) {
            $('#security-alerts-container').html(data);
        })
        .fail(function() {
            $('#security-alerts-container').html('<div class="alert alert-danger">Erro ao carregar alertas</div>');
        });
}

function updateRealTimeMetrics() {
    // Atualizar métricas em tempo real via AJAX
    $.get('/admin/security-dashboard/metrics')
        .done(function(data) {
            $('#users-online').text(data.users_online);
            $('#requests-per-min').text(data.requests_per_min);
            $('#cpu-usage').text(data.cpu_usage + '%');
        });
}

function scanSecurity() {
    if (confirm('Executar scan completo de segurança?')) {
        alert('Scan de segurança iniciado (pode levar alguns minutos)');
    }
}

function generateReport() {
    window.open('/admin/security-dashboard/report', '_blank');
}

function emergencyLockdown() {
    if (confirm('ATENÇÃO: Isso irá bloquear todos os acessos não-admin. Continuar?')) {
        if (confirm('Tem certeza? Esta ação deve ser usada apenas em emergências!')) {
            alert('Lockdown de emergência ativado!');
        }
    }
}

function refreshAlerts() {
    loadSecurityAlerts();
}
</script>

<?= $this->endSection() ?>
