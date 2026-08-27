<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SimpleTest extends BaseController
{
    public function index()
    {
        // Retorno HTML simples sem templates
        return '<!DOCTYPE html>
<html>
<head>
    <title>Teste Simples</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>✅ TESTE FUNCIONANDO!</h1>
    <p><strong>Data/Hora:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>Sessão:</strong> ' . (session('usuario') ? 'Logado' : 'Não logado') . '</p>
    <p><strong>Tipo:</strong> ' . (session('tipo') ?? 'N/A') . '</p>
    <p><strong>IP:</strong> ' . $this->request->getIPAddress() . '</p>
    
    <hr>
    <h2>Dados da Sessão:</h2>
    <pre>' . print_r(session()->get(), true) . '</pre>
    
    <hr>
    <p><a href="/debug-test">← Voltar para debug-test</a></p>
</body>
</html>';
    }
}
