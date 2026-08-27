<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

            <div class="row" style="margin-bottom: 15px">
                <div class="col-sm-6">
                    <h6 class="m-0 text-dark">Seja bem Vindo <?= $dados_da_empresa['xFant'] ?>!</h6>
                </div><!-- /.col -->
                <div class="col-sm-6 no-print">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Inicio</li>
                        <li class="breadcrumb-item">
                            <?php
                                $pricesEnv = getenv('stripe.prices') ?: '';
                                $pricePairs = [];
                                if ($pricesEnv) {
                                    foreach (explode(',', $pricesEnv) as $pair) {
                                        $pair = trim($pair);
                                        if ($pair === '') continue;
                                        $parts = explode(':', $pair, 2);
                                        $pid = trim($parts[0] ?? '');
                                        $label = trim($parts[1] ?? $pid);
                                        if ($pid) $pricePairs[] = [$pid, $label];
                                    }
                                }
                                $defaultPrice = getenv('stripe.price') ?: '';
                            ?>
                            <select id="stripe-plan-dash" class="form-control form-control-sm" style="display:inline-block; width:auto; margin-right:6px">
                                <?php if (!empty($pricePairs)) : ?>
                                    <?php foreach ($pricePairs as $pp) : ?>
                                        <option value="<?= esc($pp[0]) ?>" <?= ($defaultPrice === $pp[0]) ? 'selected' : '' ?>><?= esc($pp[1]) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php if (!empty($defaultPrice)) : ?>
                                        <option value="<?= esc($defaultPrice) ?>" selected><?= esc($defaultPrice) ?></option>
                                    <?php else: ?>
                                        <option value="" selected>Defina stripe.price no .env</option>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="btn btn-primary btn-sm" onclick="stripeAssinarDash()"><i class="fas fa-credit-card"></i> Assinar</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="stripePortalDash()"><i class="fas fa-user-cog"></i> Portal</button>
                        </li>
                    </ol>
                </div><!-- /.col -->
            </div>

            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                    <span class="info-box-icon bg-default elevation-1">
                        <img src="<?= base_url('assets/img/icon-notas/icon-nfe-entrada-xfiscal.jpg') ?>" alt="Icone imagem do total de notas de entrada" style="padding: 5px">
                    </span>

                    <div class="info-box-content">
                        <span class="info-box-text">NFe Entrada</span>
                        <span class="info-box-number">
                            <?= $total_nfe_entrada_emitidas ?>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                    <span class="info-box-icon bg-default elevation-1">
                        <img src="<?= base_url('assets/img/icon-notas/icon-nfe-saida-xfiscal.jpg') ?>" alt="Icone imagem do total de notas de entrada" style="padding: 5px">
                    </span>

                    <div class="info-box-content">
                        <span class="info-box-text">NFe Saída</span>
                        <span class="info-box-number">
                            <?= $total_nfe_saidas_emitidas ?>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->

                <!-- fix for small devices only -->
                <div class="clearfix hidden-md-up"></div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                    <span class="info-box-icon bg-default elevation-1">
                        <img src="<?= base_url('assets/img/icon-notas/icon-nfe-devolucao-xfiscal.jpg') ?>" alt="Icone imagem do total de notas de entrada" style="padding: 5px">
                    </span>

                    <div class="info-box-content">
                        <span class="info-box-text">NFe Devolução</span>
                        <span class="info-box-number">
                            <?= $total_nfe_devolucao_emitidas ?>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                    <span class="info-box-icon bg-default elevation-1">
                        <img src="<?= base_url('assets/img/icon-notas/icon-nfe-nfce-xfiscal.jpg') ?>" alt="Icone imagem do total de notas de entrada" style="padding: 5px">
                    </span>

                    <div class="info-box-content">
                        <span class="info-box-text">NFCe</span>
                        <span class="info-box-number">
                            <?= $total_nfce_emitidas ?>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h6 class="m-0 text-dark"><i class="fas fa-money-bill-alt"></i> Valor Total Notas Emitidas - <?= date('Y') ?></h6>
                                </div><!-- /.col -->
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <!-- BAR CHART -->
                            <canvas id="chartjs-2" class="chartjs" width="undefined" height="undefined"></canvas>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h6 class="m-0 text-dark"><i class="fas fa-sort-amount-up-alt"></i> Qtd. Notas Emitidas - <?= date('Y') ?></h6>
                                </div><!-- /.col -->
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="m-0 text-dark"><i class="fas fa-user-tie"></i> Adicionar contador</h6>
                                <div class="input-group" style="margin-top:.5rem;">
                                    <input type="email" class="form-control" id="contador_email_dashboard" placeholder="E-mail do contador">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="adicionarContadorEmpresa()">Adicionar</button>
                                    </div>
                                </div>
                                <small class="text-muted">Informe o e-mail do seu contador para conceder acesso.</small>
                            </div>
                            <!-- BAR CHART -->
                            <canvas id="chartjs-1" class="chartjs" width="undefined" height="undefined"></canvas>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h6 class="m-0 text-dark"><i class="fas fa-sort-amount-up-alt"></i> Qtd. e Valor Total das Notas Emitidas - <?= date('Y') ?></h6>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Mês</th>
                                        <th>Nº de Notas</th>
                                        <th>Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $cont = 12;
                                        for($i=0; $i<$cont; $i++) :
                                    ?>

                                            <tr>
                                                <td><?= $array_qtd_de_notas_geradas[$i]['mes'] ?></td>
                                                <td><?= $array_qtd_de_notas_geradas[$i]['qtd'] ?></td>
                                                <td><?= number_format($array_valor_total_notas_geradas[$i]['valor'], 2, ',', '.') ?></td>
                                            </tr>

                                    <?php
                                        endfor;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script>
    async function stripeAssinarDash() {
        try {
            const body = new URLSearchParams();
            const sel = document.getElementById('stripe-plan-dash');
            if (sel && sel.value) body.append('price_id', sel.value);
            body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const res = await fetch('/stripe/checkout', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
            const data = await res.json();
            if (data.url) window.location.href = data.url; else alert('Erro: ' + (data.error || 'desconhecido'));
        } catch (e) { alert('Falha ao iniciar checkout'); }
    }
    async function stripePortalDash() {
        try {
            const body = new URLSearchParams();
            body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const res = await fetch('/stripe/portal', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
            const data = await res.json();
            if (data.url) window.location.href = data.url; else alert('Erro: ' + (data.error || 'desconhecido'));
        } catch (e) { alert('Falha ao abrir portal'); }
    }

    async function adicionarContadorEmpresa() {
        try {
            const email = document.getElementById('contador_email_dashboard').value.trim();
            if (!email) { alert('Informe o e-mail do contador.'); return; }
            const params = new URLSearchParams();
            params.append('contador_email', email);
            params.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const res = await fetch('/empresa/adicionar-contador', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params });
            if (res.ok) {
                const data = await res.json();
                if (data.ok) { alert('Contador adicionado com sucesso.'); } else { alert('Falha: ' + (data.error || 'desconhecida')); }
            } else {
                const text = await res.text();
                alert('Erro na requisição: ' + text);
            }
        } catch (e) { alert('Falha ao adicionar contador.'); }
    }
    // Gráfico
    new Chart(document.getElementById("chartjs-1"), {
        "type": "bar",
        "data": {
            "labels": [
                <?php foreach($array_qtd_de_notas_geradas as $array) : ?>
                    '<?= $array['mes'] ?>',
                <?php endforeach; ?>
            ],
            "datasets": [{
                "label": "Qtd. de Empresas",
                "data": [
                    <?php foreach($array_qtd_de_notas_geradas as $array) : ?>
                        <?= $array['qtd'] ?>,
                    <?php endforeach; ?>
                ],
                "fill": false,
                "backgroundColor": [
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                ],
                "borderColor": [
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                ],
                "borderWidth": 1
            }]
        },
        "options": {
            "scales": {
                "yAxes": [{
                    "ticks": {
                        "beginAtZero": true
                    }
                }]
            }
        }
    });

    // Gráfico
    new Chart(document.getElementById("chartjs-2"), {
        "type": "bar",
        "data": {
            "labels": [
                <?php foreach($array_valor_total_notas_geradas as $array) : ?>
                    '<?= $array['mes'] ?>',
                <?php endforeach; ?>
            ],
            "datasets": [{
                "label": "Faturamento por Período",
                "data": [
                    <?php foreach($array_valor_total_notas_geradas as $array) : ?>
                    <?= $array['valor'] ?>,
                <?php endforeach; ?>
                ],
                "fill": false,
                "backgroundColor": [
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(255, 99, 132, 0.2)",
                ],
                "borderColor": [
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                    "rgb(255, 99, 132)",
                ],
                "borderWidth": 1
            }]
        },
        "options": {
            "scales": {
                "yAxes": [{
                    "ticks": {
                        "beginAtZero": true
                    }
                }]
            }
        }
    });
</script>