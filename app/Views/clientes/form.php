<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/clientes/store" method="post">

                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <a href="/clientes" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                                    <label for="">Tipo</label>
                                    <select class="form-control select2" id="tipo" name="tipo" onchange="alteraTipo()">
                                        <?php if (isset($cliente)) : ?>
                                            <?php if ($cliente['tipo'] == 1) : ?>
                                                <option value="1" selected>Pessoa Física</option>
                                                <option value="2">Pessoa Jurídica</option>
                                            <?php else : ?>
                                                <option value="1">Pessoa Física</option>
                                                <option value="2" selected>Pessoa Jurídica</option>
                                            <?php endif ?>
                                        <?php else : ?>
                                            <option value="1" selected>Pessoa Física</option>
                                            <option value="2">Pessoa Jurídica</option>
                                        <?php endif ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3" style="">
                                <div class="form-group">
                                    <label for="">Nome</label>
                                    <input type="text" id="input-nome" class="form-control" min="3" onblur="uppercase('input-nome')" name="nome" value="<?= (isset($cliente)) ? $cliente['nome'] : "" ?>" required="" <?= (isset($cliente) && $cliente['tipo'] == 2) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-3" style="">
                                <div class="form-group">
                                    <label for="">CPF</label>
                                    <input type="text" id="input-cpf" class="form-control cpf" name="cpf" onblur="validarCPF('input-cpf')" value="<?= (isset($cliente)) ? $cliente['cpf'] : "" ?>" required="" <?= (isset($cliente) && $cliente['tipo'] == 2) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-3" style="">
                                <div class="form-group">
                                    <label for="">CNPJ</label>
                                    <input type="text" id="input-cnpj" class="form-control cnpj" name="cnpj" onblur="validarCNPJ('input-cnpj')" value="<?= (isset($cliente)) ? $cliente['cnpj'] : "" ?>" required="" <?= (isset($cliente) && $cliente['tipo'] == 1) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-3" style="">
                                <div class="form-group">
                                    <label for="">Razão Social</label>
                                    <input type="text" id="input-razao-social" class="form-control" min="3" onblur="uppercase('input-razao-social')" name="razao_social" value="<?= (isset($cliente)) ? $cliente['razao_social'] : "" ?>" required="" <?= (isset($cliente) && $cliente['tipo'] == 1) ? "disabled" : "" ?>>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">Isento</label>
                                    <select class="form-control select2" id="cliente_isento" name="isento" style="width: 100%" onchange="alteraClienteIsento()">
                                        <?php if (isset($cliente) && $cliente['isento'] == 1) : ?>
                                            <option value="1" selected>Sim</option>
                                            <option value="0">Não</option>
                                        <?php else : ?>
                                            <option value="1">Sim</option>
                                            <option value="0" selected>Não</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">I.E.</label>
                                    <input type="text" class="form-control" id="input-ie" name="ie" value="<?= (isset($cliente)) ? $cliente['ie'] : "" ?>" required <?= (isset($cliente) && $cliente['isento'] == 1) ? "disabled" : "" ?>>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Fone</label>
                                    <input type="text" class="form-control fone-sem-mascara" name="fone" value="<?= (isset($cliente)) ? $cliente['fone'] : "" ?>" required placeholder="Digitar somente os números">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->

                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Endereço</h6>
                            </div><!-- /.col -->
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CEP</label>
                                    <input type="text" class="form-control cep" id="cep" name="cep" value="<?= (isset($cliente)) ? $cliente['cep'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <label for="">Logradouro</label>
                                    <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= (isset($cliente)) ? $cliente['logradouro'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label for="">Número</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="numero" name="numero" value="<?= (isset($cliente)) ? $cliente['numero'] : "S/N" ?>" disabled>
                                    <span class="input-group-append">
                                        <button type="button" class="btn btn-info btn-flat" onclick="semNumero('numero')">S/N</button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label for="">Complemento</label>
                                    <input type="text" class="form-control" id="complemento" name="complemento" value="<?= (isset($cliente)) ? $cliente['complemento'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Bairro</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro" value="<?= (isset($cliente)) ? $cliente['bairro'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">UF</label>
                                    <select class="form-control select2" id="id_uf" name="id_uf" onchange="selecionaUF('id_uf')" style="width: 100%" required>
                                        <?php if (isset($cliente)) : ?>
                                            <?php foreach ($ufs as $uf) : ?>
                                                <option value="<?= $uf['id_uf'] ?>" <?= ($cliente['id_uf'] == $uf['id_uf']) ? "selected" : "" ?>><?= $uf['uf'] ?></option>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <option value="" selected>Selecione</option>
                                            <?php foreach ($ufs as $uf) : ?>
                                                <option value="<?= $uf['id_uf'] ?>"><?= $uf['uf'] ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Municipio</label>
                                    <select class="form-control select2" id="id_municipio" name="id_municipio" style="width: 100%" required>
                                        <?php if (isset($cliente)) : ?>
                                            <?php foreach ($municipios as $municipio) : ?>
                                                <option value="<?= $municipio['id_municipio'] ?>" <?= ($cliente['id_municipio'] == $municipio['id_municipio']) ? "selected" : "" ?>><?= $municipio['municipio'] ?></option>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <!-- MUNICIPIOS AJAX -->
                                            <option value="">Selecione a UF para carregas os municipios</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <?php if (isset($cliente)) : ?>
                                <input type="hidden" class="form-control" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">
                            <?php endif ?>

                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-12" style="text-align: right">
                                <button type="submit" class="btn btn-primary"><?= (isset($cliente)) ? "Atualizar" : "Cadastrar" ?></button>
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
    function alteraTipo() {
        var tipo = document.getElementById('tipo').value;

        if (tipo == 1) {
            // HABILITA
            document.getElementById('input-nome').disabled = false;
            document.getElementById('input-cpf').disabled = false;

            // DESABILITA E LIMPA OS CAMPOS
            document.getElementById('input-cnpj').disabled = true;
            document.getElementById('input-razao-social').disabled = true;

            document.getElementById('input-cnpj').value = "";
            document.getElementById('input-razao-social').value = "";

            document.getElementById('input-cnpj').className = "form-control cnpj"; // Remove o is-valid se caso for desativado
        } else {
            // DESABILITA E LIMPA OS CAMPOS
            document.getElementById('input-nome').disabled = true;
            document.getElementById('input-cpf').disabled = true;

            document.getElementById('input-nome').value = "";
            document.getElementById('input-cpf').value = "";

            document.getElementById('input-cpf').className = "form-control cpf"; // Remove o is-valid se caso for desativado

            // HABILITA
            document.getElementById('input-cnpj').disabled = false;
            document.getElementById('input-razao-social').disabled = false;
        }
    }

    <?php if (!isset($cliente)) : ?>
        // CASO A AÇÃO SEJÁ CREATE ENTÃO DEIXA A PESSOA JURIDICA DESABILITADA
        document.getElementById('input-cnpj').disabled = true;
        document.getElementById('input-razao-social').disabled = true;
    <?php endif ?>

    function alteraClienteIsento() {
        var opcao = document.getElementById('cliente_isento').value;

        if (opcao == 1) {
            document.getElementById('input-ie').disabled = true;
            document.getElementById('input-ie').value = "";
        } else {
            document.getElementById('input-ie').disabled = false;
        }
    }

    <?php if (isset($cliente)) : ?>

        var campo = document.getElementById('numero');

        if (campo.value != "S/N") {
            campo.disabled = false;
        }

    <?php endif;  ?>
</script>
