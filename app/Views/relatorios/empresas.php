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
                        <a href="/inicio/contador" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                                <th style="width: 35px">Cód.</th>
                                <th style="width: 200px">Nome Fantasia</th>
                                <th style="width: 150px">CNPJ</th>
                                <th>Dia PGTO</th>
                                <th>Fone</th>
                                <th>Endereço</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($empresas)): ?>
                                <?php foreach($empresas as $empresa): ?>
                                    <tr>
                                        <td><?= $empresa['id_empresa'] ?></td>
                                        <td><?= $empresa['xFant'] ?></td>
                                        <td class="cnpj"><?= $empresa['CNPJ'] ?></td>
                                        <td><?= $empresa['dia_do_pagamento'] ?></td>
                                        <td><?= $empresa['fone'] ?></td>
                                        <td>Rua/Av: <?= $empresa['xLgr'] ?> Nº <?= $empresa['nro'] ?>, <?= $empresa['xCpl'] ?> - Bairro: <?= $empresa['xBairro'] ?>. <?= $empresa['municipio'] ?> - <?= $empresa['uf'] ?>.</td>
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

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->