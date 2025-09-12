<?php

namespace App\Controllers;

use App\Models\EmpresaModel;
use App\Models\ContadorModel;
use App\Models\LoginModel;

class EmpresaContador extends BaseController
{
    public function adicionarContador()
    {
        $session = session();
        $idEmpresa = $session->get('id_empresa');
        if (!$idEmpresa) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Não autenticado']);
        }

        $email = (string) ($this->request->getVar('contador_email') ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'E-mail do contador inválido']);
        }

        $empresaModel = new EmpresaModel();
        $contadorModel = new ContadorModel();
        $loginModel = new LoginModel();

        $empresa = $empresaModel->find($idEmpresa);
        if (!$empresa) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Empresa não encontrada']);
        }

        $contador = $contadorModel->where('email', $email)->first();
        $novoLoginGerado = null;
        $senhaGerada = null;
        $idContador = null;

        if ($contador && !empty($contador['id_contador'])) {
            // Garante que contador tenha login; se não, cria
            if (empty($contador['id_login'])) {
                $senha = bin2hex(random_bytes(4));
                $idLogin = $loginModel->insert([
                    'usuario' => $email,
                    'senha' => password_hash($senha, PASSWORD_DEFAULT),
                    'tipo' => 2,
                ]);
                $contadorModel->update($contador['id_contador'], ['id_login' => $idLogin]);
                $novoLoginGerado = $email;
                $senhaGerada = $senha;
            }
            $idContador = (int) $contador['id_contador'];
        } else {
            // Criar login e contador mínimos
            $senha = bin2hex(random_bytes(4));
            $idLogin = $loginModel->insert([
                'usuario' => $email,
                'senha' => password_hash($senha, PASSWORD_DEFAULT),
                'tipo' => 2,
            ]);
            $idContador = $contadorModel->insert([
                'status' => 'Ativo',
                'nome' => 'Contador',
                'cnpj' => '00000000000000',
                'razao_social' => 'Contador',
                'nome_fantasia' => 'Contador',
                'ie' => 'ISENTO',
                'dia_do_pagamento' => 1,
                'logradouro' => 'ENDERECO',
                'numero' => 'S/N',
                'complemento' => '',
                'bairro' => 'BAIRRO',
                'cep' => '00000000',
                'id_uf' => 1,
                'id_municipio' => 1,
                'fixo' => '',
                'celular_1' => '',
                'celular_2' => '',
                'email' => $email,
                'id_login' => $idLogin,
            ]);
            $novoLoginGerado = $email;
            $senhaGerada = $senha;
        }

        // Vincular empresa ao contador
        $empresaModel->update($idEmpresa, ['id_contador' => $idContador]);

        // Tentar enviar e-mail ao contador com credenciais (se geradas agora)
        if (!empty($novoLoginGerado) && !empty($senhaGerada)) {
            try {
                $mailer = \Config\Services::email();
                $fromEmail = getenv('email.from') ?: 'no-reply@' . parse_url((string) (config('App')->baseURL ?? ''), PHP_URL_HOST);
                $fromName = getenv('email.from_name') ?: (config('App')->appName ?? 'Sistema');
                $mailer->setFrom($fromEmail, $fromName);
                $mailer->setTo($novoLoginGerado);
                $mailer->setSubject('Acesso de Contador - Sistema');
                $mailer->setMessage("Olá,\n\nSeu acesso de contador foi criado.\nUsuário: {$novoLoginGerado}\nSenha: {$senhaGerada}\n\nAcesse: " . rtrim((string) (config('App')->baseURL ?? ''), '/') . "/login\n\nQualquer dúvida, responda este e-mail.");
                @$mailer->send();
            } catch (\Throwable $e) { /* ignore */ }
        }

        return $this->response->setJSON(['ok' => true]);
    }
}


