<!DOCTYPE html>
<html>
<head>
    <title>Teste Login PDV</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Teste Login PDV</h2>
    
    <form id="testForm">
        <p>
            <label>Usuário:</label><br>
            <input type="text" name="usuario" value="caixa.teste" required>
        </p>
        <p>
            <label>Senha:</label><br>
            <input type="password" name="senha" value="123456" required>
        </p>
        <p>
            <button type="submit">Testar Login</button>
        </p>
    </form>

    <div id="resultado"></div>

    <script>
    $('#testForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '/teste-login-pdv/testar',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#resultado').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
            },
            error: function(xhr, status, error) {
                $('#resultado').html('<p style="color:red">Erro: ' + error + '</p><pre>' + xhr.responseText + '</pre>');
            }
        });
    });
    </script>
</body>
</html>
