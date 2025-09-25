<?php

namespace App\Controllers;

class TesteLoginPDV extends BaseController
{
    public function index()
    {
        return view('teste_login_pdv');
    }

    public function testar()
    {
        $dados = $this->request->getPost();
        
        try {
            // Simula o que o LoginPDV faz
            $loginModel = new \App\Models\LoginModel();
            
            $login = $loginModel
                ->select('logins.*, usuarios_caixa.nome_completo, usuarios_caixa.id_empresa, usuarios_caixa.status as status_caixa, usuarios_caixa.id as id_caixa')
                ->join('usuarios_caixa', 'usuarios_caixa.id_login = logins.id_login')
                ->where('logins.usuario', $dados['usuario'])
                ->where('logins.tipo', 4)
                ->first();

            if (!$login) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Usuário não encontrado',
                    'debug' => 'Query executada com sucesso, mas usuário não existe'
                ]);
            }

            // Testa senha
            $senhaValida = password_verify($dados['senha'], $login['senha']);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Teste executado com sucesso',
                'login_encontrado' => true,
                'senha_valida' => $senhaValida,
                'dados_usuario' => [
                    'usuario' => $login['usuario'],
                    'nome_completo' => $login['nome_completo'],
                    'status' => $login['status_caixa'],
                    'id_empresa' => $login['id_empresa']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}
