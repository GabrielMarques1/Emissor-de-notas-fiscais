<?php

namespace App\Controllers;

class PDVSimple extends BaseController
{
    public function index()
    {
        $session = session();
        
        echo '<h2>PDV Simple Test</h2>';
        echo '<p>Se você está vendo esta página, o controller está funcionando.</p>';
        
        echo '<h3>Dados da Sessão:</h3>';
        echo '<ul>';
        echo '<li><strong>Tipo:</strong> ' . ($session->get('tipo') ?? 'N/A') . '</li>';
        echo '<li><strong>Usuário:</strong> ' . ($session->get('usuario') ?? 'N/A') . '</li>';
        echo '<li><strong>ID Empresa:</strong> ' . ($session->get('id_empresa') ?? 'N/A') . '</li>';
        echo '<li><strong>Nome Completo:</strong> ' . ($session->get('nome_completo') ?? 'N/A') . '</li>';
        echo '</ul>';
        
        echo '<h3>Links de Teste:</h3>';
        echo '<a href="/pdv">PDV Normal (com filtros)</a><br>';
        echo '<a href="/pdv-direct">PDV Direto (sem filtros)</a><br>';
        echo '<a href="/teste-pdv-access">Teste PDV Access</a><br>';
        echo '<a href="/login-pdv">Voltar ao Login</a>';
    }
}
