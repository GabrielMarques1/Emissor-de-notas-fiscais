<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/empresas/storePagamento/<?= $id_empresa ?>" method="post" enctype="multipart/form-data">

                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="m-0 text-dark"><i class="<?=$titulo['icone']?>"></i> <?=$titulo['modulo']?></h6>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <a href="/empresas/show/<?= $id_empresa ?>" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
                                    <?php foreach ($caminhos as $caminho): ?>
                                        <?php if (!$caminho['active']): ?>
                                            <li class="breadcrumb-item"><a href="<?=$caminho['rota']?>"><?=$caminho['titulo']?></a></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item active"><?=$caminho['titulo']?></li>
                                        <?php endif;?>
                                    <?php endforeach;?>
                                </ol>
                            </div><!-- /.col -->
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Data do Pagamento</label>
                                    <input type="date" class="form-control" name="data_do_pagamento" value="<?=(isset($pagamento)) ? $pagamento['data_do_pagamento'] : date('Y-m-d') ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Valor</label>
                                    <input type="text" class="form-control money" id="valor" name="valor" value="<?=(isset($pagamento)) ? number_format($pagamento['valor'], 2, ',', '.') : ""?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="">Observações</label>
                                    <input type="text" class="form-control" name="observacoes" value="<?=(isset($pagamento)) ? $pagamento['observacoes'] : ""?>">
                                </div>
                            </div>

                            <?php if(isset($pagamento)) : ?>
                                <input type="hidden" name="id_pagamento" value="<?= $pagamento['id_pagamento'] ?>">
                            <?php endif; ?>

                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-12" style="text-align: right">
                                <button type="submit" class="btn btn-primary"><?= (isset($pagamento)) ? "Atualizar" : "Cadastrar" ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->

            </form>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->