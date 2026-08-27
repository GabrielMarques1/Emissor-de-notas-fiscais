<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
        }
        
        .login-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #ffe6e6;
            color: #d63384;
        }
        
        .alert-success {
            background: #e6ffe6;
            color: #198754;
        }
        
        .loading-spinner {
            display: none;
        }
        
        .footer-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .footer-link a:hover {
            text-decoration: underline;
        }
        
        .status-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            font-size: 12px;
            z-index: 1000;
        }
        
        .status-online {
            color: #28a745;
        }
        
        .status-offline {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="status-indicator">
        <i id="statusIcon" class="fas fa-wifi"></i>
        <span id="statusText">Verificando...</span>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <h1 class="login-title">Sistema PDV</h1>
                <p class="login-subtitle">Acesso restrito para operadores de caixa</p>
            </div>

            <div id="alertContainer"></div>

            <form id="loginForm">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                        <input type="text" 
                               class="form-control" 
                               id="usuario" 
                               name="usuario" 
                               placeholder="Nome de usuário"
                               autocomplete="username"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" 
                               class="form-control" 
                               id="senha" 
                               name="senha" 
                               placeholder="Senha"
                               autocomplete="current-password"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login" id="btnLogin">
                    <span class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                    <span class="button-text">
                        <i class="fas fa-sign-in-alt"></i> Entrar no PDV
                    </span>
                </button>
            </form>

            <div class="footer-link">
                <a href="/login">
                    <i class="fas fa-arrow-left"></i> Voltar ao sistema principal
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Verificar status da conexão
            function updateConnectionStatus() {
                const online = navigator.onLine;
                const statusIcon = document.getElementById('statusIcon');
                const statusText = document.getElementById('statusText');
                
                if (online) {
                    statusIcon.className = 'fas fa-wifi status-online';
                    statusText.textContent = 'Online';
                    statusText.className = 'status-online';
                } else {
                    statusIcon.className = 'fas fa-wifi-slash status-offline';
                    statusText.textContent = 'Offline';
                    statusText.className = 'status-offline';
                }
            }

            // Atualizar status inicial
            updateConnectionStatus();
            
            // Escutar mudanças de conectividade
            window.addEventListener('online', updateConnectionStatus);
            window.addEventListener('offline', updateConnectionStatus);

            // Focus no primeiro campo
            $('#usuario').focus();

            // Submit do formulário
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const usuario = $('#usuario').val().trim();
                const senha = $('#senha').val();
                
                if (!usuario || !senha) {
                    showAlert('Por favor, preencha todos os campos.', 'danger');
                    return;
                }

                // Desabilitar botão e mostrar loading
                const btnLogin = $('#btnLogin');
                btnLogin.prop('disabled', true);
                btnLogin.find('.loading-spinner').show();
                btnLogin.find('.button-text').hide();

                // Fazer login
                $.ajax({
                    url: '/login-pdv/autenticar',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        usuario: usuario,
                        senha: senha
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            // Aguarda um pouco mais para garantir que a sessão seja estabelecida
                            setTimeout(function() {
                                // Primeiro testa se tem acesso
                                $.get('/teste-pdv-access')
                                    .done(function(testResult) {
                                        if (testResult.teste_pdv_access.status === 'OK') {
                                            window.location.href = response.redirect || '/pdv';
                                        } else {
                                            showAlert('Erro de acesso: ' + testResult.teste_pdv_access.motivo, 'danger');
                                            console.error('Teste PDV Access:', testResult);
                                        }
                                    })
                                    .fail(function() {
                                        // Se o teste falhar, tenta o redirecionamento mesmo assim
                                        window.location.href = response.redirect || '/pdv';
                                    });
                            }, 1500);
                        } else {
                            showAlert(response.message, 'danger');
                            resetButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro no login:', error);
                        let message = 'Erro de conexão. Tente novamente.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        showAlert(message, 'danger');
                        resetButton();
                    }
                });
            });

            function resetButton() {
                const btnLogin = $('#btnLogin');
                btnLogin.prop('disabled', false);
                btnLogin.find('.loading-spinner').hide();
                btnLogin.find('.button-text').show();
            }

            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                `;
                
                $('#alertContainer').html(alertHtml);
                
                // Auto-remover após 5 segundos
                setTimeout(function() {
                    $('.alert').fadeOut();
                }, 5000);
            }

            // Enter em qualquer campo submete o form
            $('#usuario, #senha').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#loginForm').submit();
                }
            });

            // Verificar se já está logado
            $.get('/login-pdv/verificar-sessao')
                .done(function(response) {
                    if (response.logado) {
                        window.location.href = '/pdv';
                    }
                })
                .fail(function() {
                    // Ignore errors
                });
        });
    </script>
</body>
</html>
