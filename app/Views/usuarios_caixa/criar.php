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
                    <i class="fas fa-user-plus"></i> <?= $title ?>
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

                <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <form method="post" action="/usuarios-caixa/criar" id="formCriarUsuario">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="usuario">
                            <i class="fas fa-user"></i> Nome de Usuário *
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="usuario" 
                               name="usuario" 
                               value="<?= old('usuario') ?>"
                               placeholder="Ex: joao.silva"
                               pattern="[a-zA-Z0-9._-]+"
                               title="Apenas letras, números, pontos, underlines e hífens"
                               required>
                        <small class="form-text text-muted">
                            Apenas letras, números, pontos (.), underlines (_) e hífens (-). Mínimo 3 caracteres.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="nome_completo">
                            <i class="fas fa-id-card"></i> Nome Completo *
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nome_completo" 
                               name="nome_completo" 
                               value="<?= old('nome_completo') ?>"
                               placeholder="Ex: João Silva Santos"
                               required>
                        <small class="form-text text-muted">
                            Nome completo do funcionário para identificação.
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="senha">
                                    <i class="fas fa-lock"></i> Senha *
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha"
                                       placeholder="Mínimo 6 caracteres"
                                       minlength="6"
                                       required>
                                <small class="form-text text-muted">
                                    Mínimo 6 caracteres. Use uma senha segura.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmar_senha">
                                    <i class="fas fa-lock"></i> Confirmar Senha *
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmar_senha" 
                                       name="confirmar_senha"
                                       placeholder="Repita a senha"
                                       minlength="6"
                                       required>
                                <small class="form-text text-muted">
                                    Repita a senha para confirmação.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Permissões do Usuário Caixa:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Acesso apenas ao PDV (Ponto de Venda)</li>
                            <li>Processar vendas e pagamentos</li>
                            <li>Consultar produtos e estoque</li>
                            <li>Imprimir cupons e relatórios básicos</li>
                            <li><strong>Não tem acesso</strong> ao sistema ERP completo</li>
                        </ul>
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
                                    <i class="fas fa-save"></i> Criar Usuário
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
// Validação de senha em tempo real
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirmar = this.value;
    
    if (senha !== confirmar) {
        this.setCustomValidity('As senhas não coincidem');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        if (confirmar.length >= 6) {
            this.classList.add('is-valid');
        }
    }
});

// Validação do nome de usuário
document.getElementById('usuario').addEventListener('input', function() {
    const valor = this.value;
    const regex = /^[a-zA-Z0-9._-]+$/;
    
    if (valor && !regex.test(valor)) {
        this.setCustomValidity('Nome de usuário deve conter apenas letras, números, pontos, underlines e hífens');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        if (valor.length >= 3) {
            this.classList.add('is-valid');
        }
    }
});

// Verificar se usuário já existe
let timeoutUsuario = null;
document.getElementById('usuario').addEventListener('input', function() {
    const usuario = this.value.trim();
    if (usuario.length >= 3) {
        clearTimeout(timeoutUsuario);
        timeoutUsuario = setTimeout(() => {
            fetch('/login/verificaUsuario', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'usuario=' + encodeURIComponent(usuario)
            })
            .then(response => response.text())
            .then(data => {
                const campo = document.getElementById('usuario');
                if (data === '1') {
                    campo.setCustomValidity('Este nome de usuário já existe');
                    campo.classList.add('is-invalid');
                    campo.classList.remove('is-valid');
                } else {
                    campo.setCustomValidity('');
                    campo.classList.remove('is-invalid');
                    campo.classList.add('is-valid');
                }
            })
            .catch(error => {
                console.error('Erro ao verificar usuário:', error);
            });
        }, 500);
    }
});
</script>

<?= $this->endSection() ?>
