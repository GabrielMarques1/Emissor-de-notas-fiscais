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
                        <a href="/empresas" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
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
                <div class="col-lg-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body no-print">
                            <div class="row">
                                <div class="col-lg-12">
                                    <a href="/empresas/novoPagamento/<?= $id_empresa ?>" class="btn btn-info"><i class="fas fa-plus-circle"></i> Novo Pagamento</a>

                                    <?php
                                        $pricesEnv = getenv('stripe.prices') ?: '';
                                        $pricePairs = [];
                                        if ($pricesEnv) {
                                            foreach (explode(',', $pricesEnv) as $pair) {
                                                $pair = trim($pair);
                                                if ($pair === '') continue;
                                                $parts = explode(':', $pair, 2);
                                                $pid = trim($parts[0] ?? '');
                                                $label = trim($parts[1] ?? $pid);
                                                if ($pid) $pricePairs[] = [$pid, $label];
                                            }
                                        }
                                        $defaultPrice = $empresa['stripe_price_id'] ?? (getenv('stripe.price') ?: '');
                                    ?>

                                    <select id="stripe-plan" class="form-control" style="display:inline-block; width:auto; margin-left:8px">
                                        <?php if (!empty($pricePairs)) : ?>
                                            <?php foreach ($pricePairs as $pp) : ?>
                                                <option value="<?= esc($pp[0]) ?>" <?= ($defaultPrice === $pp[0]) ? 'selected' : '' ?>><?= esc($pp[1]) ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php if (!empty($defaultPrice)) : ?>
                                                <option value="<?= esc($defaultPrice) ?>" selected><?= esc($defaultPrice) ?></option>
                                            <?php else: ?>
                                                <option value="" selected>Defina stripe.price no .env</option>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </select>

                                    <button type="button" class="btn btn-primary" style="margin-left:8px" onclick="stripeAssinar()"><i class="fas fa-credit-card"></i> Assinar agora</button>
                                    <button type="button" class="btn btn-secondary" style="margin-left:6px" onclick="stripePortal()"><i class="fas fa-user-cog"></i> Gerenciar assinatura</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-default card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                                <li class="pt-2 px-3"><h3 class="card-title">Cliente: <?= $empresa['xFant'] ?></h3></li>
                                <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-two-home-tab" data-toggle="pill" href="#dados-da-empresa" role="tab" aria-controls="custom-tabs-two-home" aria-selected="true">Dados da Empresa</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-two-profile-tab" data-toggle="pill" href="#pagamentos" role="tab" aria-controls="custom-tabs-two-profile" aria-selected="false">Pagamentos</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="custom-tabs-two-tabContent">
                                <div class="tab-pane fade show active" id="dados-da-empresa" role="tabpanel" aria-labelledby="custom-tabs-two-home-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                                                </div>
                                                <!-- <div class="col-sm-6">
                                                    <ol class="breadcrumb float-sm-right">
                                                        <a href="/empresas" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
                                                        <?php foreach ($caminhos as $caminho) : ?>
                                                            <?php if (!$caminho['active']) : ?>
                                                                <li class="breadcrumb-item"><a href="<?= $caminho['rota'] ?>"><?= $caminho['titulo'] ?></a></li>
                                                            <?php else : ?>
                                                                <li class="breadcrumb-item active"><?= $caminho['titulo'] ?></li>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </ol>
                                                </div> -->
                                            </div>
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">Status</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['status'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">CNPJ</label>
                                                        <input type="text" class="form-control cnpj" value="<?= (isset($empresa)) ? $empresa['CNPJ'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-5">
                                                    <div class="form-group">
                                                        <label for="">xNome</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['xNome'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">xFant</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['xFant'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">IE</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['IE'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">Dia do Pagamento</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['dia_do_pagamento'] : "" ?>" disabled>
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
                                                        <input type="text" class="form-control cep" value="<?= (isset($empresa)) ? $empresa['CEP'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="">xLgr</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['xLgr'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">nro</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['nro'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-8">
                                                    <div class="form-group">
                                                        <label for="">xCpl</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['xCpl'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">xBairro</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['xBairro'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">UF</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['uf'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">Municipio</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['municipio'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">fone</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['fone'] : "" ?>" disabled>
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
                                                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Dados Fiscais</h6>
                                                </div><!-- /.col -->
                                            </div>
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">natOp</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['natOp'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">serie</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['serie'] : "" ?>" disabled>
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
                                                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Dados NFe</h6>
                                                </div><!-- /.col -->
                                            </div>
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">tpAmb_NFe</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['tpAmb_NFe'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">nNF_homologacao</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['nNF_homologacao'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">nNF_producao</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['nNF_producao'] : "" ?>" disabled>
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
                                                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Dados NFCe</h6>
                                                </div><!-- /.col -->
                                            </div>
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">tpAmb_NFCe</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['tpAmb_NFCe'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">nNFC_homologacao</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['nNFC_homologacao'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">nNFC_producao</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['nNFC_producao'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label for="">CSC</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['CSC'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="form-group">
                                                        <label for="">CSC_Id</label>
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['CSC_Id'] : "" ?>" disabled>
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
                                                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> Certificado</h6>
                                                </div><!-- /.col -->
                                            </div>
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <label for="">Certificado</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" value="<?= (isset($empresa)) ? $empresa['certificado'] : "" ?>" disabled>
                                                        <span class="input-group-append">
                                                            <a href="/empresas/baixarCertificado/<?= (isset($empresa)) ? $empresa['certificado'] : "" ?>" class="btn btn-info btn-flat"><i class="fas fa-download"></i></a>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <label for="">Senha do Certificado</label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control" id="senha_do_certificado" value="<?= (isset($empresa)) ? $empresa['senha_do_certificado'] : "" ?>" disabled>
                                                        <span class="input-group-append">
                                                            <button type="button" class="btn btn-info btn-flat" onclick="mostraOcultaSenha('senha_do_certificado', 'icone-da-senha-do-certificado')"><i id="icone-da-senha-do-certificado" class="far fa-eye"></i></button>
                                                        </span>
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
                                                        <input type="text" class="form-control" value="<?= (isset($login)) ? $login['usuario'] : "" ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <label for="">Senha</label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control" id="senha_do_login" value="<?= (isset($login)) ? $login['senha'] : "" ?>" disabled>
                                                        <span class="input-group-append">
                                                            <button type="button" class="btn btn-info btn-flat" onclick="mostraOcultaSenha('senha_do_login', 'icone-da-senha')"><i id="icone-da-senha" class="far fa-eye"></i></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            
                                            </div>
                                        </div>
                                        <!-- /.card-body -->
                                    </div>
                                    <!-- /.card -->
                                </div>
                                <div class="tab-pane fade" id="pagamentos" role="tabpanel" aria-labelledby="custom-tabs-two-profile-tab">
                                    <div class="card">
                                        <div class="card-body table-responsive p-0">
                                            <table class="table table-hover text-nowrap table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 35px">Cód.</th>
                                                        <th>Data do Pagamento</th>
                                                        <th>Valor</th>
                                                        <th>Obs.</th>
                                                        <th class="no-print" style="width: 130px">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(!empty($pagamentos)): ?>
                                                        <?php foreach($pagamentos as $pagamento): ?>
                                                            <tr>
                                                                <td><?= $pagamento['id_pagamento'] ?></td>
                                                                <td><?= date('d/m/Y', strtotime($pagamento['data_do_pagamento'])) ?></td>
                                                                <td><?= number_format($pagamento['valor'], 2, ',', '.') ?></td>
                                                                <td><?= $pagamento['observacoes'] ?></td>
                                                                <td>
                                                                    <a href="/empresas/editPagamento/<?= $id_empresa ?>/<?= $pagamento['id_pagamento'] ?>" class="btn btn-warning style-action"><i class="fas fa-edit"></i></a>
                                                                    <button type="button" class="btn btn-danger style-action" onclick="confirmaAcaoExcluir('Deseja realmente excluir esse Pagamento?', '/empresas/deletePagamento/<?= $id_empresa ?>/<?= $pagamento['id_pagamento'] ?>')"><i class="fas fa-trash"></i></button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="5">Nenhum registro!</td>
                                                        </tr>
                                                    <?php endif ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- /.card-body -->
                                    </div>
                                    <!-- /.card -->
                                </div>
                            </div>
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script>
    function mostraOcultaSenha(id, icone)
    {
        var campo = document.getElementById(id);
        var icone = document.getElementById(icone);

        if(campo.type == "password")
        {
            icone.className = "far fa-eye-slash";
            campo.type = "text";
        }
        else
        {
            icone.className = "far fa-eye";
            campo.type = "password";
        }
    }

    async function stripeAssinar(priceId)
    {
        try {
            const body = new URLSearchParams();
            const sel = document.getElementById('stripe-plan');
            const chosen = sel && sel.value ? sel.value : priceId;
            if (chosen) body.append('price_id', chosen);
            // CSRF
            body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const res = await fetch('/stripe/checkout', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
            const data = await res.json();
            if (data.url) {
                window.location.href = data.url;
            } else {
                alert('Erro ao iniciar checkout: ' + (data.error || 'desconhecido'));
            }
        } catch (e) {
            alert('Falha ao iniciar checkout');
        }
    }

    async function stripePortal()
    {
        try {
            const body = new URLSearchParams();
            body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const res = await fetch('/stripe/portal', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
            const data = await res.json();
            if (data.url) {
                window.location.href = data.url;
            } else {
                alert('Erro ao abrir portal: ' + (data.error || 'desconhecido'));
            }
        } catch (e) {
            alert('Falha ao abrir portal');
        }
    }
</script>