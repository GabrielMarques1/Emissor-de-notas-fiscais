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

            <div class="card col-lg-12 no-print">
                <div class="card-body">
                    <form action="/relatorios/pagamentos" method="post">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Data Inicio</label>
                                    <input type="date" class="form-control" name="data_inicio" value="<?= (isset($data_inicio)) ? $data_inicio : "" ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Data Final</label>
                                    <input type="date" class="form-control" name="data_final" value="<?= (isset($data_final)) ? $data_final : "" ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Empresa</label>
                                    <select class="form-control select2" name="id_empresa" style="width: 100%">
                                        <option value="0" <?= isset($id_empresa) ? "selected" : "" ?>>Todos</option>
                                        <?php foreach($empresas as $empresa) : ?>
                                            <option value="<?= $empresa['id_empresa'] ?>" <?= isset($id_empresa) && $empresa['id_empresa'] == $id_empresa ? "selected" : "" ?>><?= $empresa['xFant'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success" style="margin-top: 30px">Gerar Relatório</button>
                                    <?php if(isset($data_inicio) && isset($data_final)): ?>
                                        <button type="button" class="btn btn-info" style="margin-top: 30px" onclick="print()">Imprimir/Salvar PDF</button>
                                    <?php endif; ?>
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
                                <th style="width: 35px">Cód.</th>
                                <th style="width: 120px">Data do PGTO</th>
                                <th style="width: 100px">Valor</th>
                                <th style="width:250px">Empresa</th>
                                <th style="width: 150px">CNPJ</th>
                                <th>Obs.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($pagamentos)): ?>
                                <?php
                                    $qtd_de_pagamentos = 0;
                                    $valor_total_de_pagamentos = 0;
                                ?>

                                <?php foreach($pagamentos as $pagamento): ?>
                                    <tr>
                                        <td><?= $pagamento['id_pagamento'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($pagamento['data_do_pagamento'])) ?></td>
                                        <td><?= number_format($pagamento['valor'], 2, ',', '.') ?></td>
                                        <td><?= $pagamento['xFant'] ?></td>
                                        <td class="cnpj"><?= $pagamento['CNPJ'] ?></td>
                                        <td><?= $pagamento['observacoes'] ?></td>
                                    </tr>

                                    <?php
                                        $valor_total_de_pagamentos += $pagamento['valor'];
                                        $qtd_de_pagamentos += 1;
                                    ?>
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

            <?php if(isset($qtd_de_pagamentos) && isset($valor_total_de_pagamentos)) : ?>
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Resumo</h6>
                                    </div><!-- /.col -->
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <h6> <b>Qtd. de Pagamentos:</b> <?= $qtd_de_pagamentos ?></h6>
                                <h6><b>Valor Total de Pagamentos:</b> R$ <?= number_format($valor_total_de_pagamentos, 2, ',', '.') ?></h6>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->