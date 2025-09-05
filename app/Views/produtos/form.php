<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/produtos/store" method="post">
                
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <a href="/produtos" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" onblur="uppercase('nome')" value="<?= (isset($produto)) ? $produto['nome'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label for="">Cód. de Barras</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="codigo_de_barras" name="codigo_de_barras" value="<?= (isset($produto)) ? $produto['codigo_de_barras'] : "SEM GTIN" ?>" required disabled>
                                    <span class="input-group-append">
                                        <button type="button" class="btn btn-info btn-flat" onclick="semCodigoDeBarras('codigo_de_barras')">SEM GTIN</button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Unidade</label>
                                    <select class="form-control select2" name="id_unidade" style="width: 100%" required>
                                        <option value="">Selecione</option>
                                        <?php foreach($unidades as $unidade) : ?>
                                            <option value="<?= $unidade['id_unidade'] ?>" <?= (isset($produto) && $produto['id_unidade'] == $unidade['id_unidade']) ? "selected" : "" ?>><?= $unidade['descricao'] ?></option>

                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Valor Unitário</label>
                                    <input type="text" class="form-control money" id="valor_unitario" name="valor_unitario" value="<?= (isset($produto)) ? number_format($produto['valor_unitario'], 2, ',', '.') : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP NFe</label>
                                    <input type="text" class="form-control cfop" name="CFOP_NFe" value="<?= (isset($produto)) ? $produto['CFOP_NFe'] : "5403" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP NFCe</label>
                                    <input type="text" class="form-control cfop" name="CFOP_NFCe" value="<?= (isset($produto)) ? $produto['CFOP_NFCe'] : "5102" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP Externo</label>
                                    <input type="text" class="form-control cfop" name="CFOP_Externo" value="<?= (isset($produto)) ? $produto['CFOP_Externo'] : "6104" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">NCM</label>
                                    <input type="text" class="form-control ncm" name="NCM" min="8" max="8" value="<?= (isset($produto)) ? $produto['NCM'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CSOSN</label>
                                    <input type="text" class="form-control" name="CSOSN" min="3" max="3" value="<?= (isset($produto)) ? $produto['CSOSN'] : "103" ?>" required="">
                                </div>
                            </div>

                            <?php if(isset($produto)): ?>
                                <input type="hidden" class="form-control" name="id_produto" value="<?= $produto['id_produto'] ?>">
                            <?php endif ?>

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

<script>
    function semCodigoDeBarras(id)
    {
        var campo = document.getElementById(id);

        if(campo.disabled)
        {
            campo.value = "";
            campo.disabled = false;
        }
        else
        {
            campo.value = "SEM GTIN";
            campo.disabled = true;
        }
    }

    <?php if(isset($produto)) : ?>
        
        var campo = document.getElementById('codigo_de_barras');

        if(campo.value != "SEM GTIN")
        {
            campo.disabled = false;
        }
        
    <?php endif;  ?>
</script>