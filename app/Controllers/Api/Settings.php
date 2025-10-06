<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Settings extends ResourceController
{
    protected $format = 'json';

    public function company()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        $idContador = (int) ($session->get('id_contador') ?? 0);
        if (($idEmpresa === 0 || $idContador === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        if ($idEmpresa === 0) return $this->failValidationErrors('Sessão inválida');

        $empresa = (new \App\Models\EmpresaModel())
            ->where('id_empresa', $idEmpresa)
            ->where('id_contador', $idContador)
            ->first();
        if (! $empresa) return $this->failNotFound('Empresa não encontrada');
        
        // VALIDAR OWNERSHIP: Empresa deve pertencer ao tenant atual
        validateOwnershipOrFail($empresa, ['id_contador', 'id_empresa'], 'empresa');

        $logoPath = WRITEPATH . 'uploads/logos/logo-' . $idEmpresa . '.png';
        $logoUrl = file_exists($logoPath) ? (base_url('api/settings/company/logo') . '?_=' . filemtime($logoPath)) : null;

        $resp = [
            'id_empresa' => (int) $empresa['id_empresa'],
            'nome' => (string) ($empresa['xFant'] ?? $empresa['xNome'] ?? ''),
            'razao_social' => (string) ($empresa['xNome'] ?? ''),
            'cnpj' => (string) ($empresa['CNPJ'] ?? ''),
            'endereco' => [
                'logradouro' => (string) ($empresa['xLgr'] ?? ''),
                'numero' => (string) ($empresa['nro'] ?? ''),
                'bairro' => (string) ($empresa['xBairro'] ?? ''),
                'cep' => (string) ($empresa['CEP'] ?? ''),
            ],
            'telefone' => (string) ($empresa['fone'] ?? ''),
            'logo_url' => $logoUrl,
        ];
        return $this->respond($resp);
    }

    public function companyUpdate()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        $idContador = (int) ($session->get('id_contador') ?? 0);
        if (($idEmpresa === 0 || $idContador === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        if ($idEmpresa === 0) return $this->failValidationErrors('Sessão inválida');

        $empresaModel = new \App\Models\EmpresaModel();
        
        // Buscar empresa para validação de ownership
        $empresa = $empresaModel->where('id_empresa', $idEmpresa)
                                ->where('id_contador', $idContador)
                                ->first();
        if (!$empresa) return $this->failNotFound('Empresa não encontrada');
        
        // VALIDAR OWNERSHIP: Empresa deve pertencer ao tenant atual
        validateOwnershipOrFail($empresa, ['id_contador', 'id_empresa'], 'empresa');

        if ($this->request->getMethod() === 'post' && $this->request->getFile('logo')) {
            // Upload do logo
            $file = $this->request->getFile('logo');
            if (! $file->isValid()) return $this->failValidationErrors('Arquivo inválido');
            if (! in_array($file->getMimeType(), ['image/png','image/jpeg','image/jpg','image/webp'], true)) {
                return $this->failValidationErrors('Tipo de imagem não suportado');
            }
            $dir = WRITEPATH . 'uploads/logos/';
            if (! is_dir($dir)) @mkdir($dir, 0775, true);
            $target = $dir . 'logo-' . $idEmpresa . '.png';
            // Normaliza para PNG
            try {
                $file->move($dir, 'tmp-' . $file->getRandomName());
                $tmp = $dir . 'tmp-' . $file->getName();
                // Tentativa simples: se não for png, apenas renomeia (mantendo compatibilidade)
                @rename($tmp, $target);
            } catch (\Throwable $e) {
                return $this->failServerError('Falha ao salvar logo');
            }
            $logoUrl = base_url('api/settings/company/logo') . '?_=' . time();
            return $this->respond(['ok' => true, 'logo_url' => $logoUrl]);
        }

        // JSON: atualizar campos
        $data = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
        if (! is_array($data)) $data = [];

        $update = [];
        if (isset($data['nome'])) { $update['xFant'] = (string) $data['nome']; }
        if (isset($data['razao_social'])) { $update['xNome'] = (string) $data['razao_social']; }
        if (isset($data['cnpj'])) { $update['CNPJ'] = preg_replace('/[^0-9]/','', (string) $data['cnpj']); }
        if (isset($data['telefone'])) { $update['fone'] = (string) $data['telefone']; }
        if (isset($data['endereco']) && is_array($data['endereco'])) {
            $e = $data['endereco'];
            if (isset($e['logradouro'])) $update['xLgr'] = (string) $e['logradouro'];
            if (isset($e['numero'])) $update['nro'] = (string) $e['numero'];
            if (isset($e['bairro'])) $update['xBairro'] = (string) $e['bairro'];
            if (isset($e['cep'])) $update['CEP'] = preg_replace('/[^0-9]/','', (string) $e['cep']);
        }
        if (! empty($update)) {
            $empresaModel->update($idEmpresa, $update);
        }
        return $this->respond(['ok' => true]);
    }

    // Servir logo do disco (writable)
    public function companyLogo()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($idEmpresa === 0 && function_exists('resolve_tenant_ids')) { [, $idEmpresa] = resolve_tenant_ids(); }
        $file = WRITEPATH . 'uploads/logos/logo-' . $idEmpresa . '.png';
        if (! $idEmpresa || ! file_exists($file)) {
            return $this->response->setStatusCode(404);
        }
        return $this->response->setHeader('Content-Type', 'image/png')->setBody(file_get_contents($file));
    }

    // Impressão: obter e salvar configuração
    public function printing()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($idEmpresa === 0 && function_exists('resolve_tenant_ids')) { [, $idEmpresa] = resolve_tenant_ids(); }
        if ($idEmpresa === 0) return $this->failValidationErrors('Sessão inválida');

        $svc = new \App\Libraries\SettingsService();
        if ($this->request->getMethod() === 'get') {
            $defaults = [
                'printer_name' => 'Padrão do Navegador',
                'auto_print' => false,
                'header_text' => '',
                'footer_text' => '',
            ];
            $json = $svc->get($idEmpresa, 'printing');
            if (! is_array($json)) $json = $defaults;
            return $this->respond($json + $defaults);
        }
        // POST: salvar
        $data = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
        $payload = [
            'printer_name' => (string) ($data['printer_name'] ?? 'Padrão do Navegador'),
            'auto_print' => (bool) ($data['auto_print'] ?? false),
            'header_text' => (string) ($data['header_text'] ?? ''),
            'footer_text' => (string) ($data['footer_text'] ?? ''),
        ];
        $svc->save($idEmpresa, 'printing', $payload);
        return $this->respond(['ok' => true]);
    }

    // Meios de pagamento: listar/salvar (ativação e taxas)
    public function payments()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($idEmpresa === 0 && function_exists('resolve_tenant_ids')) { [, $idEmpresa] = resolve_tenant_ids(); }
        if ($idEmpresa === 0) return $this->failValidationErrors('Sessão inválida');

        $svc = new \App\Libraries\SettingsService();
        $defaults = [
            'methods' => [
                'cash'   => ['enabled' => true],
                'credit' => ['enabled' => true,  'fee_percent' => 0.0, 'fee_fixed' => 0.0],
                'debit'  => ['enabled' => true,  'fee_percent' => 0.0, 'fee_fixed' => 0.0],
                'pix'    => ['enabled' => true],
                'voucher'=> ['enabled' => false],
            ],
        ];

        if ($this->request->getMethod() === 'get') {
            $json = $svc->get($idEmpresa, 'payments');
            if (! is_array($json)) $json = $defaults;
            // merge simples
            $json['methods'] = array_merge($defaults['methods'], (array) ($json['methods'] ?? []));
            // normaliza chaves
            foreach ($json['methods'] as $k => $v) {
                if (in_array($k, ['credit','debit'], true)) {
                    $json['methods'][$k]['fee_percent'] = (float) ($v['fee_percent'] ?? 0);
                    $json['methods'][$k]['fee_fixed']   = (float) ($v['fee_fixed'] ?? 0);
                }
                $json['methods'][$k]['enabled'] = (bool) ($v['enabled'] ?? false);
            }
            return $this->respond($json);
        }

        // POST
        $data = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
        $out = $defaults;
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($out['methods'] as $k => $_) {
                $in = (array) ($data['methods'][$k] ?? []);
                $out['methods'][$k]['enabled'] = (bool) ($in['enabled'] ?? false);
                if (in_array($k, ['credit','debit'], true)) {
                    $out['methods'][$k]['fee_percent'] = (float) ($in['fee_percent'] ?? 0);
                    $out['methods'][$k]['fee_fixed']   = (float) ($in['fee_fixed'] ?? 0);
                }
            }
        }
        $svc->save($idEmpresa, 'payments', $out);
        return $this->respond(['ok' => true]);
    }

    // Usuários e Permissões (por empresa) - armazenamento simples em JSON + tabela logins
    public function users()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($idEmpresa === 0 && function_exists('resolve_tenant_ids')) { [, $idEmpresa] = resolve_tenant_ids(); }
        if ($idEmpresa === 0) return $this->failValidationErrors('Sessão inválida');

        $svc = new \App\Libraries\SettingsService();
        if ($this->request->getMethod() === 'get') {
            $data = $svc->get($idEmpresa, 'users');
            $list = is_array($data['users'] ?? null) ? $data['users'] : [];
            return $this->respond(['users' => $list]);
        }

        if ($this->request->getMethod() === 'post') {
            $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
            $username = trim((string) ($payload['username'] ?? ''));
            $password = (string) ($payload['password'] ?? '');
            $role = in_array(($payload['role'] ?? ''), ['caixa','gerente'], true) ? (string) $payload['role'] : 'caixa';
            if ($username === '' || $password === '') return $this->failValidationErrors('Informe username e password');

            $lm = new \App\Models\LoginModel();
            // Verifica duplicidade
            if ($lm->where('usuario', $username)->first()) return $this->failValidationErrors('Usuário já existe');
            $idLogin = $lm->insert(['usuario' => $username, 'senha' => password_hash($password, PASSWORD_DEFAULT), 'tipo' => 3]);
            if (! $idLogin) return $this->failServerError('Falha ao criar login');

            $data = $svc->get($idEmpresa, 'users');
            if (! is_array($data)) $data = [];
            if (! isset($data['users']) || ! is_array($data['users'])) $data['users'] = [];
            $nextId = 1; foreach ($data['users'] as $u) { $nextId = max($nextId, (int) ($u['id'] ?? 0) + 1); }
            $rec = ['id' => $nextId, 'id_login' => (int) $idLogin, 'username' => $username, 'role' => $role];
            $data['users'][] = $rec;
            $svc->save($idEmpresa, 'users', $data);
            return $this->respondCreated($rec);
        }

        // Atualização parcial
        $id = (int) ($this->request->getVar('id') ?? 0);
        if ($id <= 0) return $this->failValidationErrors('ID inválido');
        $method = strtolower($this->request->getMethod());
        $data = $svc->get($idEmpresa, 'users');
        $list = is_array($data['users'] ?? null) ? $data['users'] : [];
        $idx = null; foreach ($list as $k => $u) { if ((int) ($u['id'] ?? 0) === $id) { $idx = $k; break; } }
        if ($idx === null) return $this->failNotFound('Usuário não encontrado');

        if ($method === 'delete') {
            $idLogin = (int) ($list[$idx]['id_login'] ?? 0);
            unset($list[$idx]);
            $data['users'] = array_values($list);
            @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            if ($idLogin > 0) { try { (new \App\Models\LoginModel())->delete($idLogin); } catch (\Throwable $e) {} }
            return $this->respondDeleted(['id' => $id]);
        }

        // PUT/PATCH
        $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest ? ($this->request->getJSON(true) ?? $this->request->getRawInput()) : [];
        if (isset($payload['role']) && in_array($payload['role'], ['caixa','gerente'], true)) {
            $list[$idx]['role'] = (string) $payload['role'];
        }
        if (isset($payload['password']) && $payload['password'] !== '') {
            try { (new \App\Models\LoginModel())->update((int) $list[$idx]['id_login'], ['senha' => password_hash((string) $payload['password'], PASSWORD_DEFAULT)]); } catch (\Throwable $e) {}
        }
        $data['users'] = $list;
        $svc->save($idEmpresa, 'users', $data);
        return $this->respond($list[$idx]);
    }

    public function usersMe()
    {
        // SEGURANÇA CRÍTICA: Validação de ownership obrigatória
        helper('tenant');
        
        $session = session();
        $username = (string) ($session->get('usuario') ?? '');
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($idEmpresa === 0 && function_exists('resolve_tenant_ids')) { [, $idEmpresa] = resolve_tenant_ids(); }
        $role = null;
        if ($idEmpresa > 0 && $username !== '') {
        $svc = new \App\Libraries\SettingsService();
        $data = $svc->get($idEmpresa, 'users');
            $list = is_array($data['users'] ?? null) ? $data['users'] : [];
            foreach ($list as $u) { if (($u['username'] ?? '') === $username) { $role = (string) ($u['role'] ?? null); break; } }
        }
        return $this->respond(['username' => $username, 'role' => $role]);
    }
}


