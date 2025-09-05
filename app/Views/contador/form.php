<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/contadores/store" method="post">
                
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <a href="/contadores" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                            <?php if (isset($contador)): ?>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="">Status</label>
                                        <select class="form-control select2" name="status" style="width: 100%">
                                            <?php if ($contador['status'] == "Ativo"): ?>
                                                <option value="Ativo" selected>Ativo</option>
                                                <option value="Vencido">Vencido</option>
                                                <option value="Desativado">Desativado</option>
                                            <?php elseif ($contador['status'] == "Vencido"): ?>
                                                <option value="Ativo">Ativo</option>
                                                <option value="Vencido" selected>Vencido</option>
                                                <option value="Desativado">Desativado</option>
                                            <?php else: ?>
                                                <option value="Ativo">Ativo</option>
                                                <option value="Vencido" >Vencido</option>
                                                <option value="Desativado" selected>Desativado</option>
                                            <?php endif;?>
                                        </select>
                                    </div>
                                </div>
                            <?php endif;?>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Nome do Responsável</label>
                                    <input type="text" class="form-control" name="nome" value="<?= (isset($contador)) ? $contador['nome'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CNPJ</label>
                                    <input type="text" class="form-control cnpj" id="CNPJ" name="cnpj" onblur="validarCNPJ('CNPJ')" value="<?= (isset($contador)) ? $contador['cnpj'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Razão Social</label>
                                    <input type="text" class="form-control" id="input-razao-social" name="razao_social" value="<?= (isset($contador)) ? $contador['razao_social'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Nome Fantasia</label>
                                    <input type="text" class="form-control" name="nome_fantasia" value="<?= (isset($contador)) ? $contador['nome_fantasia'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">I.E.</label>
                                    <input type="text" class="form-control" name="ie" value="<?= (isset($contador)) ? $contador['ie'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Dia do Pagamento</label>
                                    <input type="number" class="form-control" name="dia_do_pagamento" value="<?= (isset($contador)) ? $contador['dia_do_pagamento'] : "" ?>" required="">
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
                                    <input type="text" class="form-control cep" id="cep" name="cep" value="<?= (isset($contador)) ? $contador['cep'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <label for="">Logradouro</label>
                                    <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= (isset($contador)) ? $contador['logradouro'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label for="">Número</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="numero" name="numero" value="<?= (isset($contador)) ? $contador['numero'] : "S/N" ?>" disabled>
                                    <span class="input-group-append">
                                        <button type="button" class="btn btn-info btn-flat" onclick="semNumero('numero')">S/N</button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label for="">Complemento</label>
                                    <input type="text" class="form-control" id="complemento" name="complemento" value="<?= (isset($contador)) ? $contador['complemento'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Bairro</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro" value="<?= (isset($contador)) ? $contador['bairro'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="">UF</label>
                                    <select class="form-control select2" id="id_uf" name="id_uf" onchange="selecionaUF('id_uf')" style="width: 100%" required>
                                        <?php if(isset($contador)) :?>
                                            <?php foreach($ufs as $uf) : ?>
                                                <option value="<?= $uf['id_uf'] ?>" <?= ($contador['id_uf'] == $uf['id_uf']) ? "selected" : "" ?>><?= $uf['uf'] ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" selected>Selecione</option>
                                            <?php foreach($ufs as $uf) : ?>
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
                                        <?php if(isset($contador)): ?>
                                            <?php foreach($municipios as $municipio) : ?>
                                                <option value="<?= $municipio['id_municipio'] ?>" <?= ($contador['id_municipio'] == $municipio['id_municipio']) ? "selected" : "" ?>><?= $municipio['municipio'] ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- MUNICIPIOS AJAX -->
                                            <option value="">Selecione a UF para carregas os municipios</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <?php if(isset($cliente)): ?>
                                <input type="hidden" class="form-control" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">
                            <?php endif ?>

                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->

                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Contato</h6>
                            </div><!-- /.col -->
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Fixo</label>
                                    <input type="text" class="form-control fixo" name="fixo" value="<?= (isset($contador)) ? $contador['fixo'] : "" ?>">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Celular 1</label>
                                    <input type="text" class="form-control celular" name="celular_1" value="<?= (isset($contador)) ? $contador['celular_1'] : "" ?>">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Celular 2</label>
                                    <input type="text" class="form-control celular" name="celular_2" value="<?= (isset($contador)) ? $contador['celular_2'] : "" ?>">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">E-mail</label>
                                    <input type="email" class="form-control" name="email" value="<?= (isset($contador)) ? $contador['email'] : "" ?>">
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
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Login</h6>
                            </div><!-- /.col -->
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Usuário</label>
                                    <input type="text" class="form-control" id="usuario" name="usuario" onblur="verificaUsuarioNobanco('usuario')" value="<?= (isset($login)) ? $login['usuario'] : "" ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">Senha</label>
                                    <input type="text" class="form-control" name="senha" value="<?= (isset($login)) ? $login['senha'] : "" ?>" required>
                                </div>
                            </div>

                            <?php if (isset($contador)) : ?>
                                <input type="hidden" class="form-control" name="id_contador" value="<?= $contador['id_contador'] ?>">
                            <?php endif; ?>

                            <?php if (isset($login)) : ?>
                                <input type="hidden" class="form-control" name="id_login" value="<?= $login['id_login'] ?>">
                            <?php endif; ?>

                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-12" style="text-align: right">
                                <button type="submit" class="btn btn-primary"><?= (isset($contador)) ? "Atualizar" : "Cadastrar" ?></button>
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