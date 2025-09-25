<?php

namespace App\Controllers;

class TesteSession extends BaseController
{
    public function index()
    {
        $session = session();
        
        $data = [
            'title' => 'Teste de Sessão',
            'session_data' => [
                'id_login' => $session->get('id_login'),
                'usuario' => $session->get('usuario'),
                'tipo' => $session->get('tipo'),
                'id_empresa' => $session->get('id_empresa'),
                'id_contador' => $session->get('id_contador'),
                'nome_completo' => $session->get('nome_completo'),
                'xFant' => $session->get('xFant'),
                'status' => $session->get('status'),
                'all_data' => $session->get()
            ]
        ];
        
        return $this->response->setJSON($data);
    }
}
