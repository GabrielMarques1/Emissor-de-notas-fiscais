<?php

namespace App\Controllers;

use App\Models\LoginModel;
use CodeIgniter\Controller;

class LoginPDV extends Controller
{
    private $session;
    private $loginModel;

    public function __construct()
    {
        $this->session = session();
        $this->loginModel = new LoginModel();
    }

    public function index()
    {
        // Se já está logado como caixa, redireciona para PDV
        if ($this->session->get('tipo') === 4) {
            return redirect()->to('/pdv');
        }

        // Se está logado como outro tipo, faz logout
        if ($this->session->get('tipo')) {
            $this->session->destroy();
        }

        $data = [
            'title' => 'Login PDV - Sistema de Caixa'
        ];

        return view('login_pdv/index', $data);
    }

    public function autenticar()
    {
        // Throttle por IP: 5 tentativas por minuto
        $throttler = service('throttler');
        $ipKey = 'login-pdv-' . $this->request->getIPAddress();
        if (!$throttler->check($ipKey, 5, MINUTE)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Muitas tentativas. Tente novamente em instantes.'
            ]);
        }

        $dados = $this->request->getPost();
        
        if (empty($dados['usuario']) || empty($dados['senha'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Usuário e senha são obrigatórios'
            ]);
        }

        // Busca usuário caixa (tipo 4)
        $login = $this->loginModel
            ->select('logins.*, usuarios_caixa.nome_completo, usuarios_caixa.id_empresa, usuarios_caixa.status as status_caixa, usuarios_caixa.id as id_caixa')
            ->join('usuarios_caixa', 'usuarios_caixa.id_login = logins.id_login')
            ->where('logins.usuario', $dados['usuario'])
            ->where('logins.tipo', 4)
            ->first();

        // Verifica senha
        $senhaValida = false;
        if ($login) {
            $info = password_get_info($login['senha']);
            if (!empty($info['algo'])) {
                $senhaValida = password_verify($dados['senha'], $login['senha']);
            } else {
                $senhaValida = hash_equals((string) $login['senha'], (string) $dados['senha']);
            }
        }

        if (!$login || !$senhaValida) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Usuário ou senha incorretos'
            ]);
        }

        // Verifica se usuário está ativo
        if ($login['status_caixa'] !== 'ativo') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Usuário inativo. Contate o gerente.'
            ]);
        }

        // Verifica se empresa existe
        $empresaModel = new \App\Models\EmpresaModel();
        $empresa = $empresaModel->find($login['id_empresa']);
        if (!$empresa) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Empresa não encontrada. Contate o suporte.'
            ]);
        }

        // Atualiza último acesso
        $db = \Config\Database::connect();
        $db->table('usuarios_caixa')->where('id', $login['id_caixa'])->update([
            'ultimo_acesso' => date('Y-m-d H:i:s')
        ]);

        // Upgrade de senha se necessário
        $info = password_get_info($login['senha']);
        if (empty($info['algo'])) {
            $this->loginModel->update($login['id_login'], [
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT)
            ]);
        }

        // Cria sessão
        $this->session->regenerate();
        $sessionData = [
            'id_login' => $login['id_login'],
            'id_empresa' => $login['id_empresa'],
            'id_contador' => $empresa['id_contador'],
            'usuario' => $login['usuario'],
            'nome_completo' => $login['nome_completo'],
            'tipo' => 4, // Tipo caixa
            'xFant' => $empresa['xFant'],
            'xApp' => 'PDV System'
        ];
        
        $this->session->set($sessionData);
        
        // Debug: log dos dados da sessão
        log_message('debug', 'LoginPDV - Dados salvos na sessão: ' . json_encode($sessionData));
        log_message('debug', 'LoginPDV - Verificação imediata da sessão tipo: ' . $this->session->get('tipo'));

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'redirect' => '/pdv'
        ]);
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/login-pdv');
    }

    // Verificar status da sessão (para AJAX)
    public function verificarSessao()
    {
        $tipo = $this->session->get('tipo');
        $usuario = $this->session->get('usuario');
        
        // Debug completo da sessão
        $debugData = [
            'tipo' => $tipo,
            'usuario' => $usuario,
            'id_login' => $this->session->get('id_login'),
            'id_empresa' => $this->session->get('id_empresa'),
            'id_contador' => $this->session->get('id_contador'),
            'nome_completo' => $this->session->get('nome_completo'),
            'xFant' => $this->session->get('xFant'),
            'session_id' => session_id(),
            'all_data' => $this->session->get()
        ];
        
        if ($tipo === 4 && $usuario) {
            return $this->response->setJSON([
                'logado' => true,
                'usuario' => $usuario,
                'nome_completo' => $this->session->get('nome_completo'),
                'empresa' => $this->session->get('xFant'),
                'debug' => $debugData
            ]);
        }

        return $this->response->setJSON([
            'logado' => false,
            'debug' => $debugData
        ]);
    }
}
