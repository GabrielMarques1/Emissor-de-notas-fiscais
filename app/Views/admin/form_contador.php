<?php if(session('alert')): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-<?= session('alert')['type'] ?> alert-dismissible fade show" role="alert">
                <strong><?= session('alert')['title'] ?></strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/salvarContador">

    <!-- Título -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="card-title">
                                <i class="<?= $titulo['icone'] ?>"></i> 
                                <?= $titulo['modulo'] ?>
                            </h4>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="/admin/contadores" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campos obrigatórios ocultos para edição -->
    <?php if(isset($contador)): ?>
        <input type="hidden" name="id_contador" value="<?= $contador['id_contador'] ?>">
        <input type="hidden" name="id_login" value="<?= $login['id_login'] ?>">
    <?php endif; ?>

    <!-- Dados de Acesso -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-key"></i> Dados de Acesso ao Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Usuário de Login <span class="text-danger">*</span></label>
                                <input type="text" name="usuario" class="form-control" required
                                       placeholder="Ex: escritorio_silva"
                                       value="<?= isset($login) ? $login['usuario'] : old('usuario') ?>">
                                <small class="text-muted">Este será o usuário para login no sistema</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Senha <span class="text-danger">*</span></label>
                                <input type="text" name="senha" class="form-control" required
                                       placeholder="Digite uma senha segura"
                                       value="<?= isset($login) ? $login['senha'] : old('senha') ?>">
                                <small class="text-muted">O contador usará esta senha para acessar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dados Pessoais -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-user"></i> Dados do Contador</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" required
                                       placeholder="Ex: João Silva Santos"
                                       value="<?= isset($contador) ? $contador['nome'] : old('nome') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome do Escritório/Fantasia</label>
                                <input type="text" name="nome_fantasia" class="form-control"
                                       placeholder="Ex: Escritório Silva & Associados"
                                       value="<?= isset($contador) ? $contador['nome_fantasia'] : old('nome_fantasia') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CNPJ</label>
                                <input type="text" name="cnpj" class="form-control"
                                       placeholder="00.000.000/0000-00"
                                       data-mask="00.000.000/0000-00"
                                       value="<?= isset($contador) ? $contador['cnpj'] : old('cnpj') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Razão Social</label>
                                <input type="text" name="razao_social" class="form-control"
                                       placeholder="Ex: Silva Santos Contabilidade Ltda"
                                       value="<?= isset($contador) ? $contador['razao_social'] : old('razao_social') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Inscrição Estadual</label>
                                <input type="text" name="ie" class="form-control"
                                       placeholder="Inscrição estadual"
                                       value="<?= isset($contador) ? $contador['ie'] : old('ie') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Endereço -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-map-marker-alt"></i> Endereço</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Logradouro</label>
                                <input type="text" name="logradouro" class="form-control"
                                       placeholder="Ex: Rua das Flores"
                                       value="<?= isset($contador) ? $contador['logradouro'] : old('logradouro') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Número</label>
                                <input type="text" name="numero" class="form-control"
                                       placeholder="Ex: 123"
                                       value="<?= isset($contador) ? $contador['numero'] : old('numero') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Complemento</label>
                                <input type="text" name="complemento" class="form-control"
                                       placeholder="Ex: Sala 101"
                                       value="<?= isset($contador) ? $contador['complemento'] : old('complemento') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bairro</label>
                                <input type="text" name="bairro" class="form-control"
                                       placeholder="Ex: Centro"
                                       value="<?= isset($contador) ? $contador['bairro'] : old('bairro') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>UF</label>
                                <select name="id_uf" class="form-control" id="select_uf" onchange="carregaMunicipios(this.value)">
                                    <option value="">Selecione...</option>
                                    <?php foreach($ufs as $uf): ?>
                                        <option value="<?= $uf['id_uf'] ?>" 
                                                <?= (isset($contador) && $contador['id_uf'] == $uf['id_uf']) ? 'selected' : '' ?>>
                                            <?= $uf['uf'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Município</label>
                                <select name="id_municipio" class="form-control" id="select_municipio">
                                    <option value="">Primeiro selecione a UF</option>
                                    <?php if(isset($municipios)): ?>
                                        <?php foreach($municipios as $municipio): ?>
                                            <option value="<?= $municipio['id_municipio'] ?>"
                                                    <?= (isset($contador) && $contador['id_municipio'] == $municipio['id_municipio']) ? 'selected' : '' ?>>
                                                <?= $municipio['municipio'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CEP</label>
                                <input type="text" name="cep" class="form-control"
                                       placeholder="00000-000"
                                       data-mask="00000-000"
                                       value="<?= isset($contador) ? $contador['cep'] : old('cep') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contato -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-phone"></i> Contato</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Telefone Fixo</label>
                                <input type="text" name="fixo" class="form-control"
                                       placeholder="(00) 0000-0000"
                                       data-mask="(00) 0000-0000"
                                       value="<?= isset($contador) ? $contador['fixo'] : old('fixo') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Celular 1</label>
                                <input type="text" name="celular_1" class="form-control"
                                       placeholder="(00) 00000-0000"
                                       data-mask="(00) 00000-0000"
                                       value="<?= isset($contador) ? $contador['celular_1'] : old('celular_1') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Celular 2</label>
                                <input type="text" name="celular_2" class="form-control"
                                       placeholder="(00) 00000-0000"
                                       data-mask="(00) 00000-0000"
                                       value="<?= isset($contador) ? $contador['celular_2'] : old('celular_2') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>E-mail</label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="contato@escritorio.com.br"
                                       value="<?= isset($contador) ? $contador['email'] : old('email') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Dia do Pagamento</label>
                                <select name="dia_do_pagamento" class="form-control">
                                    <option value="">Selecione...</option>
                                    <?php for($i = 1; $i <= 28; $i++): ?>
                                        <option value="<?= $i ?>"
                                                <?= (isset($contador) && $contador['dia_do_pagamento'] == $i) ? 'selected' : '' ?>>
                                            Dia <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="/admin/contadores" class="btn btn-secondary">
                                <i class="fa fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> 
                                <?= isset($contador) ? 'Atualizar' : 'Cadastrar' ?> Contador
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>

<script>
$(document).ready(function() {
    // Aplicar máscaras
    $('[data-mask]').each(function() {
        var mask = $(this).attr('data-mask');
        $(this).mask(mask);
    });
});

function carregaMunicipios(id_uf) {
    if(id_uf === "") {
        $('#select_municipio').html('<option value="">Primeiro selecione a UF</option>');
        return;
    }

    $('#select_municipio').html('<option value="">Carregando...</option>');

    $.get('/uf/carregaMunicipios/' + id_uf, function(data) {
        var options = '<option value="">Selecione o município...</option>';
        data.forEach(function(municipio) {
            options += '<option value="' + municipio.id_municipio + '">' + municipio.municipio + '</option>';
        });
        $('#select_municipio').html(options);
    }).fail(function() {
        $('#select_municipio').html('<option value="">Erro ao carregar municípios</option>');
    });
}
</script>

<style>
.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-group label {
    font-weight: 600;
    color: #495057;
}

.text-danger {
    color: #dc3545 !important;
}

.card-header h5 {
    margin-bottom: 0;
    color: #495057;
}
</style>