<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/configuracoes/store" method="post">
                
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
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
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Nome do App</label>
                                    <input type="text" class="form-control" name="nome_do_app" value="<?= $config['nome_do_app'] ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="">Mensagem Suporte</label>
                                    <input type="text" class="form-control" name="mensagem_suporte" value="<?= $config['mensagem_suporte'] ?>">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Contato Suporte</label>
                                    <input type="text" class="form-control" name="contato_suporte" value="<?= $config['contato_suporte'] ?>">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Contato Suporte Formatado</label>
                                    <input type="text" class="form-control" name="contato_suporte_formatado" value="<?= $config['contato_suporte_formatado'] ?>">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="">Outras opções de pagamento</label>
                                    <textarea class="form-control" name="outras_opcoes_de_pagamento" rows="20"><?= $config['outras_opcoes_de_pagamento'] ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-12" style="text-align: right">
                                <button type="submit" class="btn btn-primary">Atualizar</button>
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