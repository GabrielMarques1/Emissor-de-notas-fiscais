<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>Monitor de Cache<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Monitor de Cache</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/inicio/admin">Dashboard Master</a></li>
                        <li class="breadcrumb-item active">Cache</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Estatísticas de Cache -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['hit_rate'] ?? 0 ?>%</h3>
                            <p>Hit Rate</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['total_hits'] ?? 0 ?></h3>
                            <p>Total Hits</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['total_misses'] ?? 0 ?></h3>
                            <p>Total Misses</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= $stats['cache_size_mb'] ?? 0 ?>MB</h3>
                            <p>Tamanho Cache</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status e Ações -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title text-white">
                                <i class="fas fa-shield-alt"></i>
                                Status de Segurança
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Isolamento:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Anti-Poisoning:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>TTL Dinâmico:</strong>
                                </div>
                                <div class="col-6">
                                    <span class="badge badge-success">ATIVO</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Limpeza Auto:</strong>
                                </div>
                                <div class="col-6">
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
                                <i class="fas fa-tools"></i>
                                Ações de Cache
                            </h3>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-warning btn-block mb-2" onclick="flushCache()">
                                <i class="fas fa-trash"></i> Limpar Cache Atual
                            </button>
                            <button class="btn btn-info btn-block mb-2" onclick="cleanupCache()">
                                <i class="fas fa-broom"></i> Limpeza Inteligente
                            </button>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="group-name" placeholder="Nome do grupo">
                                <div class="input-group-append">
                                    <button class="btn btn-danger" onclick="invalidateGroup()">
                                        <i class="fas fa-ban"></i> Invalidar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance em Tempo Real -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line"></i>
                                Performance em Tempo Real
                            </h3>
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" onclick="refreshStats()">
                                    <i class="fas fa-sync"></i> Atualizar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="cache-stats-container">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p>Carregando estatísticas...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de Cache -->
            <?php if (isset($alerts) && !empty($alerts)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h3 class="card-title text-white">
                                <i class="fas fa-exclamation-triangle"></i>
                                Alertas de Cache
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($alerts as $alert): ?>
                            <div class="alert alert-<?= $alert['type'] ?>">
                                <strong><?= $alert['title'] ?>:</strong> <?= $alert['message'] ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    loadCacheStats();
    
    // Atualizar estatísticas a cada 30 segundos
    setInterval(loadCacheStats, 30000);
});

function loadCacheStats() {
    $.get('/admin/cache-monitor/stats')
        .done(function(response) {
            if (response.success && response.stats) {
                const stats = response.stats;
                const html = `
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Hit Rate</span>
                                    <span class="info-box-number">${stats.hit_rate}%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Hits</span>
                                    <span class="info-box-number">${stats.total_hits}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Misses</span>
                                    <span class="info-box-number">${stats.total_misses}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon"><i class="fas fa-memory"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tamanho</span>
                                    <span class="info-box-number">${stats.cache_size_mb}MB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted">Última atualização: ${response.timestamp}</p>
                `;
                $('#cache-stats-container').html(html);
            } else {
                $('#cache-stats-container').html('<div class="alert alert-warning">Dados não disponíveis</div>');
            }
        })
        .fail(function() {
            $('#cache-stats-container').html('<div class="alert alert-danger">Erro ao carregar estatísticas</div>');
        });
}

function flushCache() {
    if (confirm('Limpar todo o cache? Esta ação não pode ser desfeita.')) {
        $.post('/admin/cache-monitor/flush')
            .done(function(response) {
                alert('Cache limpo com sucesso!');
                loadCacheStats();
            })
            .fail(function() {
                alert('Erro ao limpar cache');
            });
    }
}

function cleanupCache() {
    $.post('/admin/cache-monitor/cleanup')
        .done(function(response) {
            alert('Limpeza inteligente executada!');
            loadCacheStats();
        })
        .fail(function() {
            alert('Erro na limpeza');
        });
}

function invalidateGroup() {
    const groupName = $('#group-name').val();
    if (!groupName) {
        alert('Digite o nome do grupo');
        return;
    }
    
    if (confirm(`Invalidar grupo "${groupName}"?`)) {
        $.post('/admin/cache-monitor/invalidateGroup', {group: groupName})
            .done(function(response) {
                alert('Grupo invalidado com sucesso!');
                $('#group-name').val('');
                loadCacheStats();
            })
            .fail(function() {
                alert('Erro ao invalidar grupo');
            });
    }
}

function refreshStats() {
    loadCacheStats();
}
</script>

<?= $this->endSection() ?>
