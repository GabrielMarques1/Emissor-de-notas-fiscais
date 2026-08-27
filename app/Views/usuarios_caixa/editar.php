<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $title ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit"></i> <?= $title ?>
                </h5>
            </div>
            <div class="card-body">

                <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <form method="post" action="/usuarios-caixa/editar/<?= $usuario['id_login'] ?>">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="usuario">
                            <i class="fas fa-user"></i> Nome de Usuário
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="usuario" 
                               value="<?= esc($usuario['usuario']) ?>"
                               disabled>
                        <small class="form-text text-muted">
                            O nome de usuário não pode ser alterado.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="nome_completo">
                            <i class="fas fa-id-card"></i> Nome Completo
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nome_completo" 
                               name="nome_completo" 
                               value="<?= esc($usuario['nome_completo']) ?>"
                               placeholder="Ex: João Silva Santos"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-toggle-on"></i> Status
                        </label>
                        <select class="form-control" id="status" name="status">
                            <option value="ativo" <?= $usuario['status_caixa'] === 'ativo' ? 'selected' : '' ?>>
                                Ativo - Pode fazer login no PDV
                            </option>
                            <option value="inativo" <?= $usuario['status_caixa'] === 'inativo' ? 'selected' : '' ?>>
                                Inativo - Bloqueado do PDV
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            Usuários inativos não conseguem fazer login no PDV.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="senha">
                            <i class="fas fa-lock"></i> Nova Senha
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="senha" 
                               name="senha"
                               placeholder="Deixe em branco para manter a senha atual"
                               minlength="6">
                        <small class="form-text text-muted">
                            Deixe em branco se não quiser alterar a senha. Mínimo 6 caracteres se informado.
                        </small>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle text-info"></i> Informações do Usuário
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Criado em:</strong><br>
                                        <?= date('d/m/Y \à\s H:i', strtotime($usuario['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Último Acesso:</strong><br>
                                        <?php if (!empty($usuario['ultimo_acesso'])): ?>
                                            <?= date('d/m/Y \à\s H:i', strtotime($usuario['ultimo_acesso'])) ?>
                                        <?php else: ?>
                                            Nunca acessou
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Lembre-se:</strong> Este usuário tem acesso apenas ao PDV, não ao sistema ERP completo.
                    </div>

                    <div class="form-group mt-4">
                        <div class="row">
                            <div class="col-sm-6">
                                <a href="/usuarios-caixa" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left"></i> Voltar
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
// Validação da nova senha
document.getElementById('senha').addEventListener('input', function() {
    const senha = this.value;
    
    if (senha && senha.length < 6) {
        this.setCustomValidity('A senha deve ter pelo menos 6 caracteres');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        if (senha.length >= 6) {
            this.classList.add('is-valid');
        }
    }
});
</script>

<?= $this->endSection() ?>
