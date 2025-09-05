<?php $session = session() ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/produtos/store" method="post">
                
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
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Nome</label>
                                    <input type="text" class="form-control" value="<?= $produto['nome'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label for="">Cód. de Barras</label>
                                <input type="text" class="form-control" value="<?= $produto['codigo_de_barras'] ?>" disabled>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Unidade</label>
                                    <input type="text" class="form-control" value="<?= $produto['id_unidade'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Valor Unitário</label>
                                    <input type="text" class="form-control" value="<?= $produto['valor_unitario'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP NFe</label>
                                    <input type="text" class="form-control" value="<?= $produto['CFOP_NFe'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP NFCe</label>
                                    <input type="text" class="form-control" value="<?= $produto['CFOP_NFCe'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP Externo</label>
                                    <input type="text" class="form-control" value="<?= $produto['CFOP_Externo'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">NCM</label>
                                    <input type="text" class="form-control" value="<?= $produto['NCM'] ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CSOSN</label>
                                    <input type="text" class="form-control" value="<?= $produto['CSOSN'] ?>" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->

            </form>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->