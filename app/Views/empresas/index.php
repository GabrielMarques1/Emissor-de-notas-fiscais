<!-- Modal Escolhe tipo da Nota para gerar os XMLS -->
<div class="modal fade" id="modal-selecionar-tipo-da-nota">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Selecione o Tipo da Nota</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th width="120px">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <input type="hidden" id="id_empresa">
                            <tr>
                                <td>NFe</td>
                                <td>
                                    <button type="button" class="btn btn-primary style-action" onclick="escolheTipoDeNota('NFe')">Selecionar <i class="fas fa-arrow-alt-circle-right"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>NFCe</td>
                                <td>
                                    <button type="button" class="btn btn-primary style-action" onclick="escolheTipoDeNota('NFCe')">Selecionar <i class="fas fa-arrow-alt-circle-right"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fas fa-window-close"></i> Cancelar</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

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
            <div class="card">
                <!-- /.card-header -->
                <div class="card-body no-print">
                    <div class="row">
                        <div class="col-lg-12">
                            <a href="/empresas/create" class="btn btn-info"><i class="fas fa-plus-circle"></i> Nova Empresa</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <hr>
                        </div>
                    </div>
                    <form action="/empresas" method="post">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CNPJ</label>
                                    <input type="text" class="form-control cnpj" name="cnpj" value="<?= (isset($cnpj)) ? $cnpj : "" ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success" style="margin-top: 30px"><i class="fas fa-search"></i> Pesquisar</button>
                                    <?php  if(isset($cnpj)) : ?>
                                        <a href="/empresas" class="btn btn-default" style="margin-top: 30px"><i class="fas fa-search-minus"></i> Desfazer</a>
                                    <?php  endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /.card -->

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Registros</h6>
                        </div><!-- /.col -->
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 35px">Cód.</th>
                                <th>Nome Fantasia</th>
                                <th>Razão Social</th>
                                <th>CNPJ</th>
                                <th class="no-print" style="width: 130px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($empresas)): ?>
                                <?php foreach($empresas as $empresa): ?>
                                    <tr>
                                        <td><?= $empresa['id_empresa'] ?></td>
                                        <td><?= $empresa['xFant'] ?></td>
                                        <td><?= $empresa['xNome'] ?></td>
                                        <td class="cnpj"><?= $empresa['CNPJ'] ?></td>
                                        <td>
                                            <a href="/empresas/show/<?= $empresa['id_empresa'] ?>" class="btn btn-primary style-action"><i class="fas fa-folder-open"></i></a>
                                            <button type="button" class="btn btn-success style-action" onclick="document.getElementById('id_empresa').value = <?= $empresa['id_empresa'] ?>" data-toggle="modal" data-target="#modal-selecionar-tipo-da-nota"><i class="fas fa-code"></i> xmls</button>
                                            <a href="/empresas/edit/<?= $empresa['id_empresa'] ?>" class="btn btn-warning style-action"><i class="fas fa-edit"></i></a>
                                            <button type="button" class="btn btn-danger style-action" onclick="confirmaAcaoExcluir('Deseja realmente excluir essa empresa?', '/empresas/delete/<?= $empresa['id_empresa'] ?>')"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Nenhum registro!</td>
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

<script>
    function escolheTipoDeNota(tipo)
    {
        var id_empresa = document.getElementById('id_empresa').value;

        if(tipo == 'NFe')
        {
            window.location.href = "/empresas/listaXMLsNFe/"+id_empresa;
        }
        else
        {
            window.location.href = "/empresas/listaXMLsNFCe/"+id_empresa;
        }
    }
</script>