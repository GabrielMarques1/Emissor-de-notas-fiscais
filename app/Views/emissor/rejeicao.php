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
                        <a href="/notaDeSaida/emitir" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                <!-- /.card-header -->
                <div class="card-body no-print">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="">Rejeição</label>
                                <textarea class="form-control" rows="5" disabled><?= $rejeicao ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="">Solução</label>
                                <input type="text" class="form-control" value="Entre em contato com seu contador que ele passará as instruções para corrigir essa rejeição!" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->