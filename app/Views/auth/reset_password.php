<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">Redefinir Senha</div>
                        <div class="card-body">
                            <form method="post" action="/auth/reset/<?= esc($token) ?>">
                                <div class="form-group">
                                    <label>Nova senha</label>
                                    <input name="senha" type="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">Redefinir</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


