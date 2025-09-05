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
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="row" style="text-align: center">
                                <div class="col-lg-6">
                                    <img src="<?= base_url('assets/img/suporte.png') ?>" alt="Imagem de Suporte" style="width: 300px">
                                </div>
                                <div class="col-lg-6">
                                    <h4><?= $configuracao['mensagem_suporte'] ?></h4>
                                    <h5 style="margin-top: 25px">Chame no Whats: <?= $configuracao['contato_suporte_formatado'] ?> ou</h5>
                                    <a href="https://wa.me/<?= $configuracao['contato_suporte'] ?>" target="_blank" class="btn btn-success" style="margin-top: 25px">Clique aqui para abrir uma conversa</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->