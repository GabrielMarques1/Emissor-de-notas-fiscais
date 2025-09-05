<!-- Modal Altera Dados do Produto -->
<div class="modal fade" id="pesquisar">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pesquisar Cliente</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formulario-de-pesquisa" action="/clientes" method="get">
                <div class="modal-body">
                    <p id="alerta-de-campo-vazio" style="display: none"><b>Para pesquisar digite em um dos campos e clique em pesquisar!</b></p>

                    <div class="row">
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label for="">Cód.:</label>
                                <input type="text" class="form-control" id="id_cliente" name="id_cliente" value="<?= (isset($id_cliente)) ? $id_cliente : "" ?>" onkeyup="limpaCamposCliente('id_cliente')">
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="form-group">
                                <label for="">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= (isset($nome)) ? $nome : "" ?>" onkeyup="limpaCamposCliente('nome')">
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="form-group">
                                <label for="">Razão Social</label>
                                <input type="text" class="form-control" id="razao_social" name="razao_social" value="<?= (isset($razao_social)) ? $razao_social : "" ?>" onkeyup="limpaCamposCliente('razao_social')">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">CPF</label>
                                <input type="text" class="form-control cpf" id="cpf" name="cpf" value="<?= (isset($cpf)) ? $cpf : "" ?>" onkeyup="limpaCamposCliente('cpf')">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="">CNPJ</label>
                                <input type="text" class="form-control cnpj" id="cnpj" name="cnpj" value="<?= (isset($cnpj)) ? $cnpj : "" ?>" onkeyup="limpaCamposCliente('cnpj')">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>

                    <?php if(isset($id_cliente) || isset($nome) || isset($razao_social) || isset($cpf) || isset($cnpj)): ?>
                        <a href="/clientes" class="btn btn-default">Desfazer</a>
                    <?php endif; ?>

                    <button type="button" class="btn btn-success" onclick="validaCamposAtivaFormularioClientes()">Pesquisar</button>
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
                <div class="col-lg-4">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body no-print">
                            <div class="row">
                                <div class="col-lg-12">
                                    <a href="/clientes/create" class="btn btn-info"><i class="fas fa-plus-circle"></i> Novo Cliente</a>
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#pesquisar">Pesquisar</button>
                                    <?php if(isset($id_cliente) || isset($nome) || isset($razao_social) || isset($cpf) || isset($cnpj)): ?>
                                        <a href="/clientes" class="btn btn-default">Desfazer</a>
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
                                <th>Nome/Razão Social</th>
                                <th>CPF/CNPJ</th>
                                <th>Tipo</th>
                                <th class="no-print" style="width: 130px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($clientes)): ?>
                                <?php foreach($clientes as $cliente): ?>
                                    <tr>
                                        <td><?= $cliente['id_cliente'] ?></td>
                                        <td><?= ($cliente['tipo'] == 1) ? $cliente['nome'] : $cliente['razao_social'] ?></td>
                                        
                                        <?php if($cliente['tipo'] == 1): ?>
                                            <td class="cpf"><?= $cliente['cpf'] ?></td>
                                        <?php else: ?>
                                            <td class="cnpj"><?= $cliente['cnpj'] ?></td>
                                        <?php endif ?>
                                        
                                        <td><?= ($cliente['tipo'] == 1) ? "Pessoa Física" : "Pessoa Jurídica" ?></td>
                                        <td>
                                            <a href="/clientes/show/<?= $cliente['id_cliente'] ?>" class="btn btn-primary style-action"><i class="fas fa-folder-open"></i></a>
                                            <a href="/clientes/edit/<?= $cliente['id_cliente'] ?>" class="btn btn-warning style-action"><i class="fas fa-edit"></i></a>
                                            <button type="button" class="btn btn-danger style-action" onclick="confirmaAcaoExcluir('Deseja realmente excluir esse Cliente?', '/clientes/delete/<?= $cliente['id_cliente'] ?>')"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">Nenhum registro!</td>
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
    function limpaCamposCliente(campo)
    {
        if(campo == "id_cliente")
        {
            document.getElementById('nome').value = "";
            document.getElementById('razao_social').value = "";
            document.getElementById('cpf').value = "";
            document.getElementById('cnpj').value = "";
        }
        else if(campo == "nome")
        {
            document.getElementById('id_cliente').value = "";
            document.getElementById('razao_social').value = "";
            document.getElementById('cpf').value = "";
            document.getElementById('cnpj').value = "";
        }
        else if(campo == "razao_social")
        {
            document.getElementById('id_cliente').value = "";
            document.getElementById('nome').value = "";
            document.getElementById('cpf').value = "";
            document.getElementById('cnpj').value = "";
        }
        else if(campo == "cpf")
        {
            document.getElementById('id_cliente').value = "";
            document.getElementById('nome').value = "";
            document.getElementById('razao_social').value = "";
            document.getElementById('cnpj').value = "";
        }
        else if(campo == "cnpj")
        {
            document.getElementById('id_cliente').value = "";
            document.getElementById('nome').value = "";
            document.getElementById('razao_social').value = "";
            document.getElementById('cpf').value = "";
        }
    }

    function listaNumDeRegistros()
    {
        var num_de_registros = document.getElementById('num_de_registros').value;
        window.location.href = '/clientes?num_de_registros='+num_de_registros;
    }

    function validaCamposAtivaFormularioClientes()
    {
        // campos a serem validados
        var codigo, nome, razao_social, cpf, cnpj;

        codigo       = document.getElementById('id_cliente').value;
        nome         = document.getElementById('nome').value;
        razao_social = document.getElementById('razao_social').value;
        cpf          = document.getElementById('cpf').value;
        cnpj         = document.getElementById('cnpj').value;

        if(codigo == "" && nome == "" && razao_social == "" && cpf == "" && cnpj == "")
        {
            document.getElementById('alerta-de-campo-vazio').style += 'display: block; color: red';
        }
        else
        {
            document.getElementById('formulario-de-pesquisa').submit();
        }
    }
</script>