<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="pt_BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title><?= $config['nome_do_app'] ?></title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/fontawesome-free/css/all.css') ?>">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.css') ?>">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/datatables-bs4/css/dataTables.bootstrap4.css') ?>">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/select2/css/select2.css') ?>">
    <link rel="stylesheet" href="<?= base_url('theme/plugins/select2-bootstrap4-theme/select2-bootstrap4.css') ?>">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/icheck-bootstrap/icheck-bootstrap.css') ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('theme/dist/css/adminlte.css') ?>">
    <!-- Style -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

    <!-- ========= Scripts com prioridade ============= -->
    <!-- jQuery -->
    <script src="<?= base_url('theme/plugins/jquery/jquery.js') ?>"></script>
    <!-- SweetAlert2 -->
    <script src="<?= base_url('theme/plugins/sweetalert2/sweetalert2.js') ?>"></script>
    <!-- OPTIONAL SCRIPTS -->
    <script src="<?= base_url('theme/plugins/chart.js/Chart.min.js') ?>"></script>
</head>


<body class="hold-transition login-page">

    <div class="login-box">
        <div class="card">
            <div class="card-body login-card-body">
                <div class="login-logo">
                    <span style="font-weight: bold;"><?= $config['nome_do_app'] ?></span>
                </div>
                <!-- /.login-logo -->
                <p class="login-box-msg">Acesse sua conta para continuar</p>

                <form action="/login/autenticar" method="post">
                    <?= csrf_field() ?>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" style="height: 45px" name="usuario" placeholder="Digite seu Usuário" autofocus required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" style="height: 45px" name="senha" placeholder="Digite sua Senha" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-default btn-block">ACESSAR <i class="fas fa-sign-in-alt"></i></button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>
                <hr>

                <div>
                    <p class="login-box-msg" style="margin-bottom: .75rem;">Novo por aqui? Crie sua conta após o pagamento</p>
                    <div class="input-group mb-2">
                        <input type="email" class="form-control" style="height: 42px" id="signup_email_empresa" placeholder="E-mail da empresa" autocomplete="email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" style="height: 42px" id="signup_nome_fantasia" placeholder="Nome fantasia da empresa">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-building"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-2">
                        <input type="email" class="form-control" style="height: 42px" id="signup_contador_email" placeholder="E-mail do contador (opcional)">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user-tie"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" style="height: 42px" id="signup_cnpj" placeholder="CNPJ (opcional)" maxlength="18">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-id-card"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="button" id="btn_ir_pagamento" class="btn btn-outline-primary btn-block" onclick="iniciarAssinatura()">Continuar para pagamento <i class="fas fa-credit-card"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Form oculto não é mais necessário; requisição será AJAX para receber a URL e redirecionar -->
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- Bootstrap 4 -->
    <script src="<?= base_url('theme/plugins/bootstrap/js/bootstrap.bundle.js') ?>"></script>
    <!-- Select2 -->
    <script src="<?= base_url('theme/plugins/select2/js/select2.full.js') ?>"></script>
    <!-- DataTables -->
    <script src="<?= base_url('theme/plugins/datatables/jquery.dataTables.js') ?>"></script>
    <script src="<?= base_url('theme/plugins/datatables-bs4/js/dataTables.bootstrap4.js') ?>"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('theme/dist/js/adminlte.js') ?>"></script>

    <script>
        <?php
        $session = session();
        $alert = $session->getFlashdata('alert');
        $paywall = $session->getFlashdata('paywall');

        if (isset($alert)) : ?>

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });

            Toast.fire({
                type: '<?= $alert['type'] ?>',
                title: '<?= $alert['title'] ?>'
            })

        <?php endif;
        ?>

        <?php if (isset($paywall)) : ?>
        $(document).ready(function(){
            console.log('Paywall detectado, exibindo pop-up...');
            Swal.fire({
                icon: 'warning',
                title: 'Acesso restrito a assinantes ativos. Conclua o pagamento para continuar.',
                showCancelButton: true,
                confirmButtonText: 'Fazer pagamento',
                cancelButtonText: 'Fechar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Usuário clicou em Fazer pagamento');
                    
                    // Preenche dados e inicia checkout imediatamente
                    const email = '<?= esc($paywall['email'] ?? '', 'js') ?>';
                    const nome = email.split('@')[0] || 'Cliente';
                    
                    console.log('Iniciando checkout para:', email, nome);
                    
                    const payload = {
                        email_empresa: email,
                        nome_fantasia: nome,
                        contador_email: '',
                        cnpj: '',
                    };
                    payload['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
                    
                    console.log('Enviando requisição para:', '/stripe/checkout');
                    console.log('Payload:', payload);
                    
                    $.ajax({
                        url: '/stripe/checkout',
                        type: 'POST',
                        data: payload,
                        dataType: 'json',
                        beforeSend: function() {
                            console.log('Enviando requisição AJAX...');
                        }
                    }).done(function(data) {
                        console.log('Resposta checkout recebida:', data);
                        console.log('Status da resposta:', data ? 'OK' : 'NULL');
                        if (data && data.url) {
                            console.log('Redirecionando para:', data.url);
                            window.location.href = data.url;
                        } else {
                            console.error('Resposta inválida:', data);
                            Swal.fire({ 
                                icon: 'error', 
                                title: 'Erro no checkout',
                                text: (data && data.error) ? data.error : 'Falha ao iniciar pagamento'
                            });
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Erro AJAX completo:', {
                            xhr: xhr,
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });
                        let msg = 'Falha ao iniciar checkout';
                        try { 
                            const j = JSON.parse(xhr.responseText); 
                            if (j && j.error) msg = j.error; 
                        } catch(e){
                            console.error('Erro ao parsear JSON:', e);
                        }
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Erro',
                            text: msg 
                        });
                    });
                }
            });
        });
        <?php endif; ?>

        function startCheckout(email, nome, contadorEmail, cnpj) {
            if (!email || !nome) {
                Swal.fire({ icon: 'warning', title: 'Informe e-mail e nome fantasia.' });
                return;
            }
            const payload = {
                email_empresa: email,
                nome_fantasia: nome,
                contador_email: contadorEmail,
                cnpj: cnpj,
            };
            payload['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
            $.ajax({
                url: '/stripe/checkout',
                type: 'POST',
                data: payload,
                dataType: 'json'
            }).done(function(data) {
                if (data && data.url) {
                    window.location.href = data.url;
                } else {
                    Swal.fire({ icon: 'error', title: (data && data.error) ? data.error : 'Falha ao iniciar checkout.' });
                }
            }).fail(function(xhr) {
                let msg = 'Falha ao iniciar checkout';
                try { const j = JSON.parse(xhr.responseText); if (j && j.error) msg = j.error; } catch(e){}
                Swal.fire({ icon: 'error', title: msg });
            });
        }

        function iniciarAssinatura() {
            const email = $('#signup_email_empresa').val().trim();
            const nome = $('#signup_nome_fantasia').val().trim();
            const contadorEmail = $('#signup_contador_email').val().trim();
            const cnpj = $('#signup_cnpj').val().trim();

            if (!email || !nome) {
                Swal.fire({ icon: 'warning', title: 'Informe e-mail e nome fantasia.' });
                return;
            }
            const re = /^\S+@\S+\.[\S]+$/;
            if (!re.test(email)) {
                Swal.fire({ icon: 'warning', title: 'E-mail inválido.' });
                return;
            }

            $.post('/login/verificaUsuario', { usuario: email, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
                .done(function(resp) {
                    if (String(resp).trim() === '0') {
                        // Não existe: inicia checkout
                        startCheckout(email, nome, contadorEmail, cnpj);
                    } else {
                        Swal.fire({ icon: 'info', title: 'Usuário já cadastrado. Faça login.' });
                    }
                })
                .fail(function() {
                    Swal.fire({ icon: 'error', title: 'Falha ao verificar usuário. Tente novamente.' });
                });
        }
    </script>
</body>

</html>