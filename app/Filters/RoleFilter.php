<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0); // 1=admin (sistema)
        if ($tipo === 1) {
            // Admin do sistema sempre permitido
            return null;
        }

        $required = array_map('strtolower', (array) $arguments);
        if (empty($required)) {
            return null; // sem papéis exigidos
        }

        // Descobre papel via settings/users (JSON por empresa)
        $username = (string) ($session->get('usuario') ?? '');
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($idEmpresa === 0 && function_exists('resolve_tenant_ids')) { [, $idEmpresa] = resolve_tenant_ids(); }
        $role = 'caixa';
        if ($idEmpresa > 0 && $username !== '') {
            $file = WRITEPATH . 'uploads/settings/users-' . $idEmpresa . '.json';
            if (file_exists($file)) {
                $data = json_decode((string) @file_get_contents($file), true);
                if (is_array($data) && is_array($data['users'] ?? null)) {
                    foreach ($data['users'] as $u) {
                        if (($u['username'] ?? '') === $username) {
                            $role = strtolower((string) ($u['role'] ?? 'caixa'));
                            break;
                        }
                    }
                }
            }
        }

        // Permite gerente cumprir papéis de caixa implicitamente
        $effective = [$role];
        if ($role === 'gerente') $effective[] = 'caixa';

        // Verifica interseção
        foreach ($effective as $r) {
            if (in_array($r, $required, true)) {
                return null;
            }
        }

        // Bloqueia
        $acceptsJson = strpos((string) ($request->getHeaderLine('Accept') ?? ''), 'application/json') !== false;
        if ($acceptsJson) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'Acesso negado: permissão insuficiente']);
        }
        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}


