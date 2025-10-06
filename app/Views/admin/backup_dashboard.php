<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>Monitor de Backup<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Monitor de Backup</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/inicio/admin">Dashboard Master</a></li>
                        <li class="breadcrumb-item active">Backup</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Estatísticas de Backup -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['total_backups'] ?? 0 ?></h3>
                            <p>Total de Backups</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['today_backups'] ?? 0 ?></h3>
                            <p>Backups Hoje</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['total_size_mb'] ?? 0 ?>MB</h3>
                            <p>Tamanho Total</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-hdd"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= $stats['last_backup_hours'] ?? 0 ?>h</h3>
                            <p>Último Backup</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status do Sistema -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title text-white">
                                <i class="fas fa-shield-alt"></i>
                                Status do Sistema
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Criptografia:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-success">AES-256-CBC</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Storage Remoto:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-<?= $stats['remote_status'] === 'active' ? 'success' : 'danger' ?>">
                                        <?= strtoupper($stats['remote_status'] ?? 'OFFLINE') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Automação:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-success">ATIVA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title text-white">
                                <i class="fas fa-tools"></i>
                                Ações Rápidas
                            </h3>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-success btn-block mb-2" onclick="runBackup()">
                                <i class="fas fa-play"></i> Executar Backup Agora
                            </button>
                            <button class="btn btn-warning btn-block mb-2" onclick="testRestore()">
                                <i class="fas fa-vial"></i> Testar Restore
                            </button>
                            <button class="btn btn-danger btn-block" onclick="cleanupBackups()">
                                <i class="fas fa-trash"></i> Limpeza Automática
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Backups -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history"></i>
                                Histórico de Backups Recentes
                            </h3>
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" onclick="refreshBackups()">
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="backup-history-container">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tenant</th>
                                <th>Tamanho</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup) { ?>
                            <tr>
                                <td><?= $backup['created_at'] ?></td>
                                <td><?= $backup['tenant'] ?></td>
                                <td><?= $backup['size_mb'] ?> MB</td>
                                <td><span class="badge badge-<?= $backup['status'] === 'OK' ? 'success' : 'danger' ?>"><?= strtoupper($backup['status'] ?? 'ERRO') ?></span></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
{{ ... }}
        </div>
    </section>
</div>

<script>
// Dashboard de Backup - Comandos Reais
function runBackup() {
    if (confirm('Executar backup incremental de todos os tenants?')) {
        fetch('/admin/backup-dashboard/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.message + '\nComando: ' + data.command);
            } else {
                alert('❌ Erro: ' + (data.error || 'Falha desconhecida'));
            }
        })
        .catch(error => {
            alert('❌ Erro de conexão: ' + error.message);
        });
    }
}

function testRestore() {
    if (confirm('Executar teste de restore (modo simulação)?')) {
        fetch('/admin/backup-dashboard/test-restore', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.message + '\nComando: ' + data.command);
            } else {
                alert('❌ Erro: ' + (data.error || 'Falha desconhecida'));
            }
        })
        .catch(error => {
            alert('❌ Erro de conexão: ' + error.message);
        });
    }
}

function cleanupBackups() {
    if (confirm('Executar limpeza automática de backups antigos?')) {
        fetch('/admin/backup-dashboard/cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.message + '\nComando: ' + data.command);
            } else {
                alert('❌ Erro: ' + (data.error || 'Falha desconhecida'));
            }
        })
        .catch(error => {
            alert('❌ Erro de conexão: ' + error.message);
        });
    }
}

function refreshBackups() {
    location.reload();
}
</script>

<?= $this->endSection() ?>
