<?= $this->extend('templates/default') ?>

<?= $this->section('title') ?>
<?= $title ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users"></i> Usuários Caixa - <?= esc($empresa['xFant']) ?>
                </h5>
                <a href="/usuarios-caixa/criar" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Adicionar Caixa
                </a>
            </div>
            <div class="card-body">

                <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Nome Completo</th>
                                <th>Status</th>
                                <th>Último Acesso</th>
                                <th>Criado em</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Nenhum usuário caixa cadastrado.
                                    <br><br>
                                    <a href="/usuarios-caixa/criar" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Criar Primeiro Usuário
                                    </a>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc($usuario['usuario']) ?></strong>
                                    </td>
                                    <td><?= esc($usuario['nome_completo']) ?></td>
                                    <td>
                                        <?php if ($usuario['status_caixa'] === 'ativo'): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($usuario['ultimo_acesso'])): ?>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Nunca acessou</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="/usuarios-caixa/editar/<?= $usuario['id_login'] ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Excluir"
                                                    onclick="excluirUsuario(<?= $usuario['id_login'] ?>, '<?= esc($usuario['usuario']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Importante:</strong> Os usuários caixa têm acesso apenas ao PDV, não ao sistema ERP completo. 
                        Eles podem processar vendas, consultar produtos e gerar relatórios básicos de caixa.
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o usuário <strong id="nomeUsuario"></strong>?</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let usuarioParaExcluir = null;

function excluirUsuario(id, nome) {
    usuarioParaExcluir = id;
    document.getElementById('nomeUsuario').textContent = nome;
    $('#modalConfirmacao').modal('show');
}

document.getElementById('btnConfirmarExclusao').addEventListener('click', function() {
    if (!usuarioParaExcluir) return;
    
    fetch(`/usuarios-caixa/excluir/${usuarioParaExcluir}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Falha ao excluir usuário'));
        }
    })
    .catch(error => {
        alert('Erro ao excluir usuário');
        console.error(error);
    });
    
    $('#modalConfirmacao').modal('hide');
});
</script>

<?= $this->endSection() ?>
