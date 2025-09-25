<?php

namespace App\Controllers;

use App\Models\LoginModel;
use App\Models\EmpresaModel;

class UsuariosCaixa extends BaseController
{
    private $loginModel;
    private $empresaModel;

    public function __construct()
    {
        $this->loginModel = new LoginModel();
        $this->empresaModel = new EmpresaModel();
    }

    public function index()
    {
        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);

        // Apenas gerentes (tipo 3) podem acessar
        if ($tipo !== 3 || $idEmpresa <= 0) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
        }

        $empresa = $this->empresaModel->find($idEmpresa);
        
        // Busca usuários caixa da empresa
        $usuariosCaixa = $this->loginModel
            ->select('logins.*, usuarios_caixa.nome_completo, usuarios_caixa.status as status_caixa')
            ->join('usuarios_caixa', 'usuarios_caixa.id_login = logins.id_login', 'left')
            ->where('usuarios_caixa.id_empresa', $idEmpresa)
            ->where('logins.tipo', 4)
            ->findAll();

        $data = [
            'title' => 'Gerenciar Usuários Caixa',
            'empresa' => $empresa,
            'usuarios' => $usuariosCaixa,
            'link' => 'usuarios'
        ];

        return view('usuarios_caixa/index', $data);
    }

    public function criar()
    {
        if ($this->request->getMethod() === 'post') {
            $validation = \Config\Services::validation();
            
            $rules = [
                'usuario' => 'required|min_length[3]|max_length[50]|is_unique[logins.usuario]',
                'senha' => 'required|min_length[6]',
                'nome_completo' => 'required|min_length[3]|max_length[100]',
                'confirmar_senha' => 'required|matches[senha]'
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $validation->getErrors());
            }

            $session = session();
            $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
            
            if ($idEmpresa <= 0) {
                return redirect()->back()->with('error', 'Sessão inválida');
            }

            $dados = $this->request->getPost();
            
            // Cria login tipo 4 (caixa)
            $loginData = [
                'usuario' => $dados['usuario'],
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                'tipo' => 4
            ];

            $idLogin = $this->loginModel->insert($loginData);
            
            if (!$idLogin) {
                return redirect()->back()->withInput()->with('error', 'Erro ao criar usuário');
            }

            // Cria registro na tabela usuarios_caixa
            $db = \Config\Database::connect();
            $caixaData = [
                'id_login' => $idLogin,
                'id_empresa' => $idEmpresa,
                'nome_completo' => $dados['nome_completo'],
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->table('usuarios_caixa')->insert($caixaData);

            return redirect()->to('/usuarios-caixa')->with('success', 'Usuário caixa criado com sucesso!');
        }

        return view('usuarios_caixa/criar', ['title' => 'Criar Usuário Caixa', 'link' => 'usuarios']);
    }

    public function editar($id = null)
    {
        if (!$id) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Usuário não encontrado');
        }

        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        
        // Busca usuário caixa
        $usuario = $this->loginModel
            ->select('logins.*, usuarios_caixa.nome_completo, usuarios_caixa.status as status_caixa, usuarios_caixa.id as id_caixa')
            ->join('usuarios_caixa', 'usuarios_caixa.id_login = logins.id_login')
            ->where('logins.id_login', $id)
            ->where('usuarios_caixa.id_empresa', $idEmpresa)
            ->where('logins.tipo', 4)
            ->first();

        if (!$usuario) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Usuário não encontrado');
        }

        if ($this->request->getMethod() === 'post') {
            $dados = $this->request->getPost();
            
            $updateLogin = ['updated_at' => date('Y-m-d H:i:s')];
            $updateCaixa = ['updated_at' => date('Y-m-d H:i:s')];

            if (!empty($dados['senha'])) {
                if (strlen($dados['senha']) < 6) {
                    return redirect()->back()->with('error', 'Senha deve ter pelo menos 6 caracteres');
                }
                $updateLogin['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }

            if (!empty($dados['nome_completo'])) {
                $updateCaixa['nome_completo'] = $dados['nome_completo'];
            }

            if (isset($dados['status'])) {
                $updateCaixa['status'] = in_array($dados['status'], ['ativo', 'inativo']) ? $dados['status'] : 'ativo';
            }

            $this->loginModel->update($id, $updateLogin);
            
            $db = \Config\Database::connect();
            $db->table('usuarios_caixa')->where('id', $usuario['id_caixa'])->update($updateCaixa);

            return redirect()->to('/usuarios-caixa')->with('success', 'Usuário atualizado com sucesso!');
        }

        return view('usuarios_caixa/editar', [
            'title' => 'Editar Usuário Caixa',
            'usuario' => $usuario,
            'link' => 'usuarios'
        ]);
    }

    public function excluir($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID inválido']);
        }

        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        
        // Verifica se usuário pertence à empresa
        $usuario = $this->loginModel
            ->select('logins.id_login, usuarios_caixa.id as id_caixa')
            ->join('usuarios_caixa', 'usuarios_caixa.id_login = logins.id_login')
            ->where('logins.id_login', $id)
            ->where('usuarios_caixa.id_empresa', $idEmpresa)
            ->where('logins.tipo', 4)
            ->first();

        if (!$usuario) {
            return $this->response->setJSON(['success' => false, 'message' => 'Usuário não encontrado']);
        }

        try {
            $db = \Config\Database::connect();
            
            // Remove da tabela usuarios_caixa primeiro (devido à foreign key)
            $db->table('usuarios_caixa')->where('id', $usuario['id_caixa'])->delete();
            
            // Remove da tabela logins
            $this->loginModel->delete($id);
            
            return $this->response->setJSON(['success' => true, 'message' => 'Usuário excluído com sucesso']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Erro ao excluir usuário']);
        }
    }
}
