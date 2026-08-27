<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">Recuperar Senha</div>
                        <div class="card-body">
                            <form method="post" action="/auth/forgot">
                                <div class="form-group">
                                    <label>Usuário</label>
                                    <input name="usuario" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Email do contador (opcional)</label>
                                    <input name="email" type="email" class="form-control" placeholder="contador@empresa.com">
                                </div>
                                <div class="form-group">
                                    <label>CNPJ da empresa (opcional)</label>
                                    <input name="cnpj" class="form-control" placeholder="00.000.000/0000-00">
                                </div>
                                <button type="submit" class="btn btn-primary">Enviar link</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


