<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row" style="margin-bottom: 15px">
                <div class="col-sm-6">
                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                </div><!-- /.col -->
                <div class="col-sm-6 no-print">
                    <ol class="breadcrumb float-sm-right">
                        <a href="/inicio/admin" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
                        <?php foreach ($caminhos as $caminho) : ?>
                            <?php if (!$caminho['active']) : ?>
                                <li class="breadcrumb-item"><a href="<?= $caminho['rota'] ?>"><?= $caminho['titulo'] ?></a></li>
                            <?php else : ?>
                                <li class="breadcrumb-item active"><?= $caminho['titulo'] ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div><!-- /.col -->
            </div>

            <div class="card col-lg-8">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Período do Faturamento</h6>
                        </div><!-- /.col -->
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="/relatorios/contadores" method="post">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Data Inicio</label>
                                    <input type="date" class="form-control" name="data_inicio" value="<?= (isset($data_inicio)) ? $data_inicio : "" ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Data Final</label>
                                    <input type="date" class="form-control" name="data_final" value="<?= (isset($data_final)) ? $data_final : "" ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success" style="margin-top: 30px"><i class="fas fa-search"></i> Pesquisar</button>
                                    <?php if (isset($data_inicio)): ?>
                                        <a href="/relatorios/contadores" class="btn btn-default" style="margin-top: 30px"><i class="fas fa-search-minus"></i> Desfazer</a>
                                    <?php endif;?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
            
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Relatório</h6>
                        </div><!-- /.col -->
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Contador</th>
                                <th>Nome Fantasia</th>
                                <th>CNPJ</th>
                                <th>Status</th>
                                <th>Faturamento por Período</th>
                                <th>Qtd. Empresas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($contagens)): ?>
                                <?php foreach($contagens as $contagem): ?>
                                    <tr>
                                        <td><?= $contagem['contador'] ?></td>
                                        <td><?= $contagem['nome_fantasia'] ?></td>
                                        <td class="cnpj"><?= $contagem['cnpj'] ?></td>
                                        <td><?= $contagem['status'] ?></td>
                                        <td><?= $contagem['somatorio_pagamentos'] ?></td>
                                        <td><?= $contagem['qtd_empresas'] ?></td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Nenhum registro!</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Qtd. de Empresas</h6>
                                </div><!-- /.col -->
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <!-- BAR CHART -->
                            <canvas id="chartjs-1" class="chartjs" width="undefined" height="undefined"></canvas>
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
                                    <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Faturamento no Período</h6>
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
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script>
    // Gráfico
    new Chart(document.getElementById("chartjs-1"), {
        "type": "bar",
        "data": {
            "labels": [
                <?php foreach($contagens as $contagem) : ?>
                    <?= '"' . $contagem['contador'] . '", ' ?>
                <?php endforeach; ?>
            ],
            "datasets": [{
                "label": "Qtd. de Empresas",
                "data": [
                    <?php foreach($contagens as $contagem) : ?>
                        <?= '"' . $contagem['qtd_empresas'] . '", ' ?>
                    <?php endforeach; ?>
                ],
                "fill": false,
                "backgroundColor": ["rgba(255, 99, 132, 0.2)", "rgba(255, 159, 64, 0.2)", "rgba(255, 205, 86, 0.2)", "rgba(75, 192, 192, 0.2)", "rgba(54, 162, 235, 0.2)", "rgba(153, 102, 255, 0.2)", "rgba(201, 203, 207, 0.2)"],
                "borderColor": ["rgb(255, 99, 132)", "rgb(255, 159, 64)", "rgb(255, 205, 86)", "rgb(75, 192, 192)", "rgb(54, 162, 235)", "rgb(153, 102, 255)", "rgb(201, 203, 207)"],
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
                <?php foreach($contagens as $contagem) : ?>
                    <?= '"' . $contagem['contador'] . '", ' ?>
                <?php endforeach; ?>
            ],
            "datasets": [{
                "label": "Faturamento por Período",
                "data": [
                    <?php foreach($contagens as $contagem) : ?>
                        <?= '"' . $contagem['somatorio_pagamentos'] . '", ' ?>
                    <?php endforeach; ?>
                ],
                "fill": false,
                "backgroundColor": ["rgba(255, 99, 132, 0.2)", "rgba(255, 159, 64, 0.2)", "rgba(255, 205, 86, 0.2)", "rgba(75, 192, 192, 0.2)", "rgba(54, 162, 235, 0.2)", "rgba(153, 102, 255, 0.2)", "rgba(201, 203, 207, 0.2)"],
                "borderColor": ["rgb(255, 99, 132)", "rgb(255, 159, 64)", "rgb(255, 205, 86)", "rgb(75, 192, 192)", "rgb(54, 162, 235)", "rgb(153, 102, 255)", "rgb(201, 203, 207)"],
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