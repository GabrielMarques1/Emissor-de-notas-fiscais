<?php

namespace App\Controllers;

class TestePDVAccess extends BaseController
{
    public function verificar()
    {
        $session = session();
        
        $dados = [
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => $session->session_id ?? 'N/A',
            'dados_sessao' => [
                'tipo' => $session->get('tipo'),
                'id_empresa' => $session->get('id_empresa'),
                'id_contador' => $session->get('id_contador'),
                'usuario' => $session->get('usuario'),
                'nome_completo' => $session->get('nome_completo'),
                'xFant' => $session->get('xFant')
            ],
            'teste_pdv_access' => $this->testePdvAccess(),
            'teste_controller_pdv' => $this->testeControllerPdv()
        ];
        
        return $this->response->setJSON($dados);
    }
    
    private function testePdvAccess()
    {
        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        
        if (!in_array($tipo, [1,3,4], true)) {
            return ['status' => 'FALHOU', 'motivo' => 'Tipo inválido: ' . $tipo];
        }
        
        if ($idEmpresa <= 0) {
            return ['status' => 'FALHOU', 'motivo' => 'ID empresa inválido: ' . $idEmpresa];
        }
        
        // Testa se empresa existe
        $empresaModel = new \App\Models\EmpresaModel();
        $empresa = $empresaModel->find($idEmpresa);
        if (!$empresa) {
            return ['status' => 'FALHOU', 'motivo' => 'Empresa não encontrada no banco'];
        }
        
        return ['status' => 'OK', 'empresa' => $empresa['xFant']];
    }
    
    private function testeControllerPdv()
    {
        $session = session();
        $tipoUsuario = (int) ($session->get('tipo') ?? 0);
        $status = $session->get('status');
        
        if (!in_array($tipoUsuario, [3, 4])) {
            return ['status' => 'FALHOU', 'motivo' => 'Tipo não permitido no PDV: ' . $tipoUsuario];
        }
        
        if ($status === "Desativado") {
            return ['status' => 'FALHOU', 'motivo' => 'Status desativado'];
        }
        
        return ['status' => 'OK', 'tipo_permitido' => $tipoUsuario];
    }
}
