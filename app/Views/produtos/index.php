<!-- Modal Altera Dados do Produto -->
<div class="modal fade" id="pesquisar">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pesquisar Produto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formulario-de-pesquisa" action="/produtos" method="get">
                <div class="modal-body">
                    <p id="alerta-de-campo-vazio" style="display: none"><b>Para pesquisar digite em um dos campos e clique em pesquisar!</b></p>

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="">Cód.:</label>
                                <input type="text" class="form-control" id="id_produto" name="id_produto" value="<?= (isset($id_produto)) ? $id_produto : "" ?>" onkeyup="limpaCamposProduto('id_produto')">
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="form-group">
                                <label for="">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= (isset($nome)) ? $nome : "" ?>" onkeyup="limpaCamposProduto('nome')">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">Cód. de Barras</label>
                                <input type="text" class="form-control" id="codigo_de_barras" name="codigo_de_barras" value="<?= (isset($codigo_de_barras)) ? $codigo_de_barras : "" ?>" onkeyup="limpaCamposProduto('codigo_de_barras')">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>

                    <?php if (isset($id_produto) || isset($nome) || isset($codigo_de_barras)) : ?>
                        <a href="/produtos" class="btn btn-default">Desfazer</a>
                    <?php endif; ?>

                    <button type="button" class="btn btn-success" onclick="validaCamposAtivaFormularioProdutos()">Pesquisar</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- Modal SELECIONA CSV -->
<div class="modal fade" id="modal-add-por-csv">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Selecione o CSV</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-import-csv" action="/produtos/addPorCSV" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="exampleInputFile">Selecione o CSV</label>
                                <div class="input-group">
                                    <input type="file" name="csv" required="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p>
                                <b style="color: orange;">Atenção:</b> <br>
                                1).. Todos os produtos já cadastrados serão substituidos pelo do arquivo CSV. <br>
                                2).. Não remova a primeira linha (CABEÇALHO). <br>
                                3).. Caso o campo seja NUMERICO não deixe em vazio. <br>
                                4).. Usei o padrão de moeda BRL e para valores quebrados use VIRGULA. <br>
                                5).. Não coloque mascara nos campos. <br>
                                6).. Se não tiver Cód. de Barras colocar SEM GTIN
                                7).. Baixe a planilha CSV exemplo e preencha seus respectivos campos. <br>
                            </p>
                            <p style="text-align: center;">
                                <a href="<?= base_url('assets/files/PLANILHA_MODELO.csv') ?>" class="btn btn-info"><i class="fas fa-download"></i> Baixar planilha modelo</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-continuar-import-csv" onclick="importarCSV()"><i class="fas fa-save"></i> Continuar</button>
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

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body no-print">
                            <div class="row">
                                <div class="col-lg-10 col-md-10 col-sm-10 col-xs-6">
                                    <a href="/produtos/create" class="btn btn-info"><i class="fas fa-plus-circle"></i> Novo Produto</a>
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modal-add-por-csv"><i class="fas fa-plus-circle"></i> Add por CSV</button>
                                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#pesquisar"><i class="fas fa-search"></i> Pesquisar</button>
                                    <?php if (isset($id_produto) || isset($nome) || isset($codigo_de_barras)) : ?>
                                        <a href="/produtos" class="btn btn-default">Desfazer</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>

            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 offset-lg-10 offset-md-10 offset-sm-10" style="margin-bottom: 10px">
                    <select class="form-control select2" id="num_de_registros" name="num_de_registros" style="width: 100%" onchange="listaNumDeRegistros()">
                        <option value="10" <?= (isset($num_de_registros) && $num_de_registros == "10") ? "selected" : "" ?>>10 Registros</option>
                        <option value="50" <?= (isset($num_de_registros) && $num_de_registros == "50") ? "selected" : "" ?>>50 Registros</option>
                        <option value="100" <?= (isset($num_de_registros) && $num_de_registros == "100") ? "selected" : "" ?>>100 Registros</option>
                        <option value="500" <?= (isset($num_de_registros) && $num_de_registros == "500") ? "selected" : "" ?>>500 Registros</option>
                        <option value="00" <?= (isset($num_de_registros) && $num_de_registros == "00") ? "selected" : "" ?>>Todos</option>
                    </select>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 35px">Cód.</th>
                                <th>Nome</th>
                                <th>Cod. Barras</th>
                                <th>Unidade</th>
                                <th>Valor Unitário</th>
                                <th class="no-print" style="width: 130px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($produtos)) : ?>
                                <?php foreach ($produtos as $produto) : ?>
                                    <tr>
                                        <td><?= $produto['id_produto'] ?></td>
                                        <td><?= $produto['nome'] ?></td>
                                        <td><?= ($produto['codigo_de_barras'] == 0 || $produto['codigo_de_barras'] == "") ? "SEM GTIN" : $produto['codigo_de_barras'] ?></td>
                                        <td><?= $produto['descricao'] ?></td>
                                        <td><?= number_format($produto['valor_unitario'], 2, ',', '.') ?></td>
                                        <td>
                                            <a href="/produtos/show/<?= $produto['id_produto'] ?>" class="btn btn-primary style-action"><i class="fas fa-folder-open"></i></a>
                                            <a href="/produtos/edit/<?= $produto['id_produto'] ?>" class="btn btn-warning style-action"><i class="fas fa-edit"></i></a>
                                            <button type="button" class="btn btn-danger style-action" onclick="confirmaAcaoExcluir('Deseja realmente excluir esse Produto?', '/produtos/delete/<?= $produto['id_produto'] ?>')"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="9">Nenhum registro!</td>
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
    function limpaCamposProduto(campo) {
        if (campo == "id_produto") {
            document.getElementById('nome').value = "";
            document.getElementById('codigo_de_barras').value = "";
        } else if (campo == "nome") {
            document.getElementById('id_produto').value = "";
            document.getElementById('codigo_de_barras').value = "";
        } else if (campo == "codigo_de_barras") {
            document.getElementById('id_produto').value = "";
            document.getElementById('nome').value = "";
        }
    }

    function listaNumDeRegistros() {
        var num_de_registros = document.getElementById('num_de_registros').value;
        window.location.href = '/produtos?num_de_registros=' + num_de_registros;
    }

    function validaCamposAtivaFormularioProdutos() {
        // campos a serem validados
        var codigo, nome, codigo_de_barras;

        codigo = document.getElementById('id_produto').value;
        nome = document.getElementById('nome').value;
        codigo_de_barras = document.getElementById('codigo_de_barras').value;

        if (codigo == "" && nome == "" && codigo_de_barras == "") {
            document.getElementById('alerta-de-campo-vazio').style += 'display: block; color: red';
        } else {
            document.getElementById('formulario-de-pesquisa').submit();
        }
    }
</script>

<script>
    function importarCSV() {
        document.getElementById("btn-continuar-import-csv").innerHTML = "Aguarde..";
        document.getElementById("btn-continuar-import-csv").disabled = true;
        document.getElementById("form-import-csv").submit();
    }
</script>