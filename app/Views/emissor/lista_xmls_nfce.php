<!-- Modal Cancelar Nota -->
<div class="modal fade" id="modal-cancelar-nota">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Cancelar NFCe</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/NFCe/cancelar" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="">Justificativa</label>
                                <textarea class="form-control" name="justificativa" rows="10" required></textarea>
                                <p>
                                    <b>Obs:</b> O prazo para cancelamento da NFCe é de 30min a partir da hora de emissão.
                                </p>
                            </div>
                        </div>

                        <input type="hidden" id="id_nfce" name="id_nfce" type="text">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Continuar</button>
                </div>
            </form>
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

            <div class="card col-lg-12">
                <div class="card-body">
                    <form action="/emissor/listaXMLsNFCe" method="post">
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
                                    
                                    <?php if (isset($data_inicio) && isset($data_final) && !empty($nfces)): ?>
                                        <a href="/NFCe/baixaXMLS/<?=$data_inicio?>/<?=$data_final?><?=(isset($acao)) ? "/" . $id_empresa : ""?>" class="btn btn-info" style="margin-top: 30px"><i class="fas fa-download"></i> Baixar Seleção</a>
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
                            <h6 class="m-0 text-dark"><i class="fas fa-list"></i> <?= $titulo_do_filtro ?></h6>
                        </div><!-- /.col -->
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 35px">Cód.</th>
                                <th>Chave</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Status</th>
                                <th class="no-print" style="width: 130px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($nfces)): ?>
                                <?php $valor_total_das_notas = 0 ?>
                                <?php $quantidade_de_notas = 0 ?>
                                <?php foreach($nfces as $nfce): ?>
                                    <tr>
                                        <td><?= $nfce['id_nfce'] ?></td>
                                        <td><?= $nfce['chave'] ?></td>
                                        <td><?= number_format($nfce['valor_da_nota'], 2, ',', '.') ?></td>
                                        <td><?= date('d/m/Y', strtotime($nfce['data'])) ?></td>
                                        <td><?= $nfce['hora'] ?></td>
                                        <td><?= $nfce['status'] ?></td>
                                        <td>
                                            <?php if($nfce['status'] != "Cancelada"): ?>
                                                <a href="/imprimir/DANFe/2/<?= $nfce['id_nfce'] ?>" class="btn btn-success style-action" target="_blank"><i class="fas fa-print"></i></a>
                                            <?php endif; ?>

                                            <a href="/NFCe/baixarXML/<?= $nfce['id_nfce'] ?><?= (isset($acao)) ? "/".$id_empresa : "" ?>" class="btn btn-info style-action"><i class="fas fa-download"></i></a>
                                            
                                            <?php if($nfce['status'] != "Cancelada"): ?>
                                                <button type="button" class="btn btn-danger style-action" onclick="document.getElementById('id_nfce').value = <?= $nfce['id_nfce'] ?>" data-toggle="modal" data-target="#modal-cancelar-nota" style="color: white"><i class="fas fa-window-close"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <?php $valor_total_das_notas += $nfce['valor_da_nota'] ?>
                                    <?php $quantidade_de_notas += 1 ?>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">Nenhum registro!</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <?php if(!empty($nfces)): ?>
                <div class="card col-lg-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <h6><b>Valor total das notas:</b> <?= number_format($valor_total_das_notas, 2, ',', '.') ?></h6>
                                <h6><b>Qtd de notas:</b> <?= $quantidade_de_notas ?></h6>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            <?php endif?>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
