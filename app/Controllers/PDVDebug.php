<?php

namespace App\Controllers;

class PDVDebug extends BaseController
{
    public function index()
    {
        $session = session();
        
        $dados = [
            'titulo' => 'PDV Debug - Acesso Direto',
            'timestamp' => date('Y-m-d H:i:s'),
            'sessao' => [
                'id_login' => $session->get('id_login'),
                'tipo' => $session->get('tipo'),
                'usuario' => $session->get('usuario'),
                'id_empresa' => $session->get('id_empresa'),
                'id_contador' => $session->get('id_contador'),
                'xFant' => $session->get('xFant'),
                'status' => $session->get('status')
            ],
            'verificacoes' => [
                'tipo_valido' => in_array((int)$session->get('tipo'), [3,4]),
                'empresa_valida' => (int)$session->get('id_empresa') > 0,
                'status_ok' => $session->get('status') !== 'Desativado'
            ]
        ];
        
        echo '<pre>';
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo '</pre>';
        
        echo '<hr><h3>Links de Teste:</h3>';
        echo '<a href="/pdv" class="btn btn-primary">PDV Normal</a> ';
        echo '<a href="/teste-pdv-access" class="btn btn-info">Teste PDV Access</a> ';
        echo '<a href="/login-pdv" class="btn btn-secondary">Login PDV</a>';
    }
}
