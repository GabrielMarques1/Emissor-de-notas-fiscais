<!-- Modal Altera Dados do Produto -->
<div class="modal fade" id="alterar-produto-da-nota">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Editar Produto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/notaDeEntrada/alteraDadosDoProduto" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="form-group">
                                <label for="">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">Quantidade</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">Valor Unitário</label>
                                <input type="text" class="form-control money" id="valor_unitario" name="valor_unitario">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">CFOP NFe</label>
                                <input type="text" class="form-control cfop" id="CFOP_NFe" name="CFOP_NFe">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">NCM</label>
                                <input type="text" class="form-control ncm" id="NCM" name="NCM">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">CSOSN</label>
                                <input type="text" class="form-control" id="CSOSN" name="CSOSN">
                            </div>
                        </div>

                        <input type="hidden" id="id_produto_provisorio" name="id_produto_provisorio">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
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
            
            <?php $error_messages = session()->getFlashdata('error_messages'); ?>
            <?php if (!empty($error_messages)): ?>
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading">
                        <i class="fa fa-exclamation-triangle"></i> Falha na Emissão da Nota Fiscal
                    </h4>
                    <p>Por favor, corrija os seguintes erros antes de continuar:</p>
                    <hr>
                    <ul>
                        <?php foreach ($error_messages as $message): ?>
                            <li><?= $message ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body no-print">
            <div class="card">
                <!-- /.card-header -->
                <div class="card-body no-print">
                    <div class="row">
                        <div class="col-lg-12">
                            <a href="/produtos/create" class="btn btn-info"><i class="fas fa-plus-circle"></i> Novo Produto</a>
                            <a href="/clientes/create" class="btn btn-info"><i class="fas fa-plus-circle"></i> Novo Cliente</a>
                        </div>
                    </div>
                    <form action="/notaDeEntrada/adicionaProduto" method="post">
                        <div class="row" style="margin-top: 20px">
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label for="">Produto</label>
                                    <select class="form-control select2" name="id_produto" style="width: 100%" <?= (empty($produtos)) ? "disabled" : "" ?>>
                                        <?php if(!empty($produtos)): ?>
                                            <?php foreach($produtos as $produto): ?>
                                                <option value="<?= $produto['id_produto'] ?>"><?= $produto['nome'] ?></option>
                                            <?php endforeach ?>
                                        <?php else: ?>
                                            <option value="">Nenhum produto cadastrado!</option>
                                        <?php endif ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <label for="">Cód. de Barras</label>
                                <input type="number" class="form-control" name="codigo_de_barras" autofocus <?= (empty($produtos)) ? "disabled" : "" ?>>
                            </div>
                            <div class="col-lg-2">
                                <label for="">Quantidade</label>
                                <input type="number" class="form-control" name="quantidade" value="1" <?= (empty($produtos)) ? "disabled" : "" ?>>
                            </div>
                            <div class="col-lg-1">
                                <button type="submit" class="btn btn-success btn-block" style="margin-top: 30px" <?= (empty($produtos)) ? "disabled" : "" ?>><i class="fas fa-plus-circle"></i> Add</button>
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
                            <h6 class="m-0 text-dark"><i class="fas fa-list"></i> Produtos da Nota</h6>
                        </div>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 35px">Cód.</th>
                                <th>Nome</th>
                                <th>Qtd</th>
                                <th>Valor Unit.</th>
                                <th>Subtotal</th>
                                <th>Valor Final</th>
                                <th>CFOP NFe</th>
                                <th>NCM</th>
                                <th style="width: 35px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php $valor_total_da_nota = 0 ?>

                            <?php if(!empty($produtos_provisorios)): ?>
                                <?php foreach($produtos_provisorios as $produto): ?>
                                    <tr>
                                        <td><?= $produto['id_produto_provisorio'] ?></td>
                                        <td><?= $produto['nome'] ?></td>
                                        <td><?= $produto['quantidade'] ?></td>
                                        <td><?= number_format($produto['valor_unitario'], 2, ',', '.') ?></td>
                                        <td><?= number_format(($produto['quantidade'] * $produto['valor_unitario']), 2, ',', '.') ?></td>
                                        <td><?= number_format((($produto['quantidade'] * $produto['valor_unitario']) - $produto['desconto']), 2, ',', '.') ?></td>
                                        <td class="cfop"><?= $produto['CFOP_NFe'] ?></td>
                                        <td class="ncm"><?= $produto['NCM'] ?></td>
                                        <td>
                                            <button type="button" class="btn btn-danger style-action" onclick="confirmaAcaoExcluir('Deseja realmente excluir esse produto?', '/notaDeEntrada/removeProduto/<?= $produto['id_produto_provisorio'] ?>')"><i class="fas fa-trash"></i></button>
                                            <button type="button" class="btn btn-warning style-action" onclick="preparaDadosDoProduto(<?= $produto['id_produto_provisorio'] ?>, '<?= $produto['nome'] ?>', <?= $produto['quantidade'] ?>, <?= $produto['valor_unitario'] ?>, <?= $produto['desconto'] ?>, '<?= $produto['CFOP_NFe'] ?>', '<?= $produto['NCM'] ?>', '<?= $produto['CSOSN'] ?>')" data-toggle="modal" data-target="#alterar-produto-da-nota"><i class="fas fa-edit"></i></button>
                                        </td>
                                    </tr>

                                    <?php $valor_total_da_nota += (($produto['quantidade'] * $produto['valor_unitario']) - $produto['desconto']) ?>

                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11">Nenhum registro!</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
                <div class="card-footer" style="background: white">
                    <div class="row">
                        <div class="col-lg-12">
                            <h5><b>Valor da Nota:</b> R$ <?= number_format($valor_total_da_nota, 2, ',', '.') ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->
            <form id="form-emitir-nota" action="/NFe/emitirNotaDeEntrada" method="post">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12">
                                <h6 class="m-0 text-dark"><i class="fas fa-check"></i> Dados Finais</h6>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Natureza da Operação</label>
                                    <input type="text" class="form-control" name="natureza_da_operacao" value="ENTRADA DE MERCADORIAS" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">Data</label>
                                    <input type="date" class="form-control" name="data" value="<?= date('Y-m-d') ?>" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">Hora</label>
                                    <input type="time" class="form-control" name="hora" value="<?= date('H:i') ?>" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">Forma de Pagamento</label>
                                    <select class="form-control select2" name="forma_de_pagamento" style="width: 100%" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                        <option value="0" selected>À Vista</option>
                                        <option value="1">À Prazo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">Tipo de Pagamento</label>
                                    <select class="form-control select2" name="tipo_de_pagamento" style="width: 100%" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                        <option value="01" selected>Dinheiro</option>
                                        <option value="02">Cheque</option>
                                        <option value="03">Cartão de Crédito</option>
                                        <option value="04">Cartão de Débito</option>
                                        <option value="05">Crédito Loja</option>
                                        <option value="10">Vale Alimentação</option>
                                        <option value="11">Vale Refeição</option>
                                        <option value="12">Vale Presente</option>
                                        <option value="13">Vale Combustível</option>
                                        <option value="15">Boleto Bancário</option>
                                        <option value="90">Sem Pagamento</option>
                                        <option value="99">Outros</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Inf. Complementares</label>
                                    <input type="text" class="form-control" name="informacoes_complementares" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                    <p style="font-size: 10px">Não entra na NFCe.</p>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Inf. para o Fisco</label>
                                    <input type="text" class="form-control" name="infomacoes_para_fisco" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>>
                                    <p style="font-size: 10px">Não entra na NFCe.</p>
                                </div>
                            </div>
                            
                            <input type="hidden" class="form-control" name="valor_da_nota" value="<?= $valor_total_da_nota ?>">

                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="offset-lg-8 col-lg-4" style="text-align: right">
                                <button class="btn btn-success" type="submit" <?= ($valor_total_da_nota == 0) ? "disabled" : "" ?>><i class="fas fa-plus-circle"></i> Emitir NFe</button>
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

<script>
    <?php
        $session = session();
        $id_nfe  = $session->getFlashdata('id_nfe');
        $id_nfce = $session->getFlashdata('id_nfce');

        if (isset($id_nfe)) :
            echo "window.open('/Imprimir/DANFe/1/$id_nfe', '_blank');";
        endif;

        if (isset($id_nfce)) :            
            echo "window.open('/Imprimir/DANFe/2/$id_nfce', '_blank');";
        endif;
    ?>

    function preparaDadosDoProduto(id_produto_provisorio, nome, quantidade, valor_unitario, desconto, CFOP_NFe, NCM, CSOSN)
    {
        document.getElementById('id_produto_provisorio').value = id_produto_provisorio;
        document.getElementById('nome').value                  = nome;
        document.getElementById('quantidade').value            = quantidade;
        document.getElementById('valor_unitario').value        = formataMascara(valor_unitario, 'valor');
        document.getElementById('CFOP_NFe').value              = formataMascara(CFOP_NFe, 'cfop');
        document.getElementById('NCM').value                   = formataMascara(NCM, 'ncm');
        document.getElementById('CSOSN').value                 = formataMascara(CSOSN, 'CSOSN');
    }
</script>