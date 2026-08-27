<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class TestDashboard extends BaseController
{
    public function index()
    {
        // Debug da sessão
        $sessionData = session()->get();
        log_message('debug', '[TestDashboard] Session data: ' . json_encode($sessionData));
        
        // Verificar se é usuário admin (tipo 1) - TEMPORARIAMENTE DESABILITADO PARA TESTE
        // if (session('tipo') != 1) {
        //     return redirect()->to('/erro-permissao-de-acesso');
        // }
        
        $data = [
            'title' => 'Dashboard de Teste',
            'session_debug' => $sessionData
        ];
        
        return view('admin/test_dashboard', $data);
    }
}
