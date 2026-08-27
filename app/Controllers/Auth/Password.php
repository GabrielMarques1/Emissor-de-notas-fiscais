<?php

namespace App\Controllers\Auth;

use App\Models\LoginModel;
use App\Models\PasswordResetModel;
use CodeIgniter\Controller;

class Password extends Controller
{
    public function forgot()
    {
        if ($this->request->getMethod() === 'post') {
            $usuario = (string) $this->request->getPost('usuario');
            $email = (string) ($this->request->getPost('email') ?? '');
            $cnpj  = (string) ($this->request->getPost('cnpj') ?? '');

            $login = (new LoginModel())->where('usuario', $usuario)->first();
            if (! $login) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Usuário não encontrado']);
            }

            // Validação básica: email deve coincidir com o contador/empresa dono desse login
            $db = \Config\Database::connect();
            $isOwner = false;
            // 1) Validação por e-mail do contador (se informado)
            if ($email !== '') {
                $isOwner = $db->table('contadores')
                              ->where('id_login', $login['id_login'])
                              ->where('email', $email)
                              ->countAllResults() > 0;
            }
            // 2) Validação por CNPJ da empresa (se informado)
            if (! $isOwner && $cnpj !== '') {
                $cnpjSemMascara = function_exists('removeMascaras') ? removeMascaras($cnpj) : preg_replace('/[^0-9]/', '', $cnpj);
                $empresa = $db->table('empresas')
                              ->where('id_login', $login['id_login'])
                              ->get()
                              ->getRowArray();
                if ($empresa) {
                    $cnpjEmpresa = function_exists('removeMascaras') ? removeMascaras($empresa['CNPJ']) : preg_replace('/[^0-9]/', '', (string) $empresa['CNPJ']);
                    $isOwner = ($cnpjSemMascara !== '' && $cnpjSemMascara === $cnpjEmpresa);
                }
            }
            if (! $isOwner) {
                return $this->response->setStatusCode(422)->setJSON(['error' => 'Dados não conferem']);
            }

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            (new PasswordResetModel())->insert([
                'id_login' => $login['id_login'],
                'token' => $token,
                'expires_at' => $expires,
            ]);

            // Enviar email (placeholder): em produção, use serviço de email
            // Por ora, retornamos o link na resposta para facilitar o teste
            $link = base_url('auth/reset/' . $token);
            return $this->response->setJSON(['message' => 'Link de redefinição gerado', 'reset_link' => $link]);
        }

        echo view('templates/header');
        echo view('auth/forgot_password');
        echo view('templates/footer');
    }

    public function reset($token = null)
    {
        $model = new PasswordResetModel();
        $row = $model->where('token', $token)->first();
        if (! $row) {
            return $this->response->setStatusCode(404)->setBody('Token inválido');
        }
        if (strtotime($row['expires_at']) < time()) {
            return $this->response->setStatusCode(410)->setBody('Token expirado');
        }
        if (! empty($row['used_at'])) {
            return $this->response->setStatusCode(410)->setBody('Token já utilizado');
        }

        if ($this->request->getMethod() === 'post') {
            $senha = (string) $this->request->getPost('senha');
            if (strlen($senha) < 6) {
                return $this->response->setStatusCode(422)->setBody('Senha muito curta');
            }
            $loginModel = new LoginModel();
            $loginModel->update($row['id_login'], [
                'senha' => password_hash($senha, PASSWORD_BCRYPT),
            ]);
            $model->update($row['id'], ['used_at' => date('Y-m-d H:i:s')]);
            return redirect()->to('/login');
        }

        echo view('templates/header');
        echo view('auth/reset_password', ['token' => $token]);
        echo view('templates/footer');
    }
}


