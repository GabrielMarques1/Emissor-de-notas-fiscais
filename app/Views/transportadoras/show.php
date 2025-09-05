<?php $session = session() ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-6">
                            <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <a href="<?= $session->get('_ci_previous_url') ?>" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3" style="">
                            <div class="form-group">
                                <label for="">CNPJ</label>
                                <input type="text" class="form-control" value="<?= $transportadora['CNPJ'] ?>" disabled>
                            </div>
                        </div>
                        <div class="col-lg-4" style="">
                            <div class="form-group">
                                <label for="">Razão Social</label>
                                <input type="text" class="form-control" value="<?= $transportadora['xNome'] ?>" disabled>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label for="">Isento</label>
                                <input type="text" class="form-control" value="<?= ($transportadora['isento'] == 1) ? "Sim" : "Não" ?>" disabled>
                            </div>
                        </div>
                        <div class="col-lg-3" style="">
                            <div class="form-group">
                                <label for="">I.E.</label>
                                <input type="text" class="form-control" value="<?= $transportadora['IE'] ?>" disabled>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-12">
                            <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Endereço</h6>
                        </div><!-- /.col -->
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">Endereço</label>
                                <input type="text" class="form-control" value="<?= $transportadora['xEnder'] ?>" disabled>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label for="">UF</label>
                                <input type="text" class="form-control" value="<?= $transportadora['uf'] ?>" disabled>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">Municipio</label>
                                <input type="text" class="form-control" value="<?= $transportadora['municipio'] ?>" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->