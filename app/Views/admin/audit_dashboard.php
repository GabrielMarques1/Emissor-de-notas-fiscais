<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>Dashboard de Auditoria<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard de Auditoria</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/inicio/admin">Dashboard Master</a></li>
                        <li class="breadcrumb-item active">Auditoria</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Estatísticas de Auditoria -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['total_logs'] ?? 0 ?></h3>
                            <p>Total de Logs</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['today_logs'] ?? 0 ?></h3>
                            <p>Logs Hoje</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['security_alerts'] ?? 0 ?></h3>
                            <p>Alertas de Segurança</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats['failed_logins'] ?? 0 ?></h3>
                            <p>Logins Falharam</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros e Pesquisa -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search"></i>
                                Filtros de Auditoria
                            </h3>
                        </div>
                        <div class="card-body">
                            <form id="audit-filters" class="row">
                                <div class="col-md-3">
                                    <label>Data Inicial</label>
                                    <input type="date" class="form-control" name="date_start" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label>Data Final</label>
                                    <input type="date" class="form-control" name="date_end" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label>Tipo de Evento</label>
                                    <select class="form-control" name="event_type">
                                        <option value="">Todos</option>
                                        <option value="login">Login</option>
                                        <option value="security">Segurança</option>
                                        <option value="data_access">Acesso a Dados</option>
                                        <option value="admin_action">Ação Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Pesquisar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs de Auditoria -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i>
                                Logs de Auditoria Recentes
                            </h3>
                            <div class="card-tools">
                                <button class="btn btn-sm btn-success" onclick="refreshLogs()">
                        </div>
                        <div class="card-body">
                            <div id="audit-logs-container">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Timestamp</th>
                                                <th>Evento</th>
                                                <th>Usuário</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?= date('Y-m-d H:i:s') ?></td>
                                                <td>Login de usuário admin</td>
                                                <td>master</td>
                                                <td><span class="badge badge-success">OK</span></td>
                                            </tr>
                                            <tr>
                                                <td><?= date('Y-m-d H:i:s', strtotime('-5 minutes')) ?></td>
                                                <td>Acesso ao dashboard</td>
                                                <td>admin</td>
                                                <td><span class="badge badge-info">INFO</span></td>
                                            </tr>
                                            <tr>
                                                <td><?= date('Y-m-d H:i:s', strtotime('-10 minutes')) ?></td>
                                                <td>Cache limpo automaticamente</td>
                                                <td>system</td>
                                                <td><span class="badge badge-warning">MAINT</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
        </div>
    </section>
</div>

<script>
// Dashboard de Auditoria - Versão Simplificada
function refreshLogs() {
    location.reload();
}
</script>

<?= $this->endSection() ?>
