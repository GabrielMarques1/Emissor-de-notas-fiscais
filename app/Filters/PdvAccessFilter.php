<?php

namespace App\Filters;

use App\Models\EmpresaModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PdvAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') {
            return null;
        }

        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        // Tentativa de autocorreção da sessão: define tenant e perfil PDV quando possível
        if (($idEmpresa <= 0 || $tipo === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmp] = resolve_tenant_ids();
            if ($idContador > 0 && $idEmp > 0) {
                if ($tipo === 0) { $session->set('tipo', 3); $tipo = 3; }
                if ($session->get('id_contador') === null) { $session->set('id_contador', $idContador); }
                if ($idEmpresa <= 0) { $session->set('id_empresa', $idEmp); $idEmpresa = $idEmp; }
            }
        }

        $acceptsJson = strpos((string) ($request->getHeaderLine('Accept') ?? ''), 'application/json') !== false;
        $path = '/' . trim((string) $request->getUri()->getPath(), '/');
        $isApi = (bool) preg_match('#(^|/)api(\/|$)#i', $path);
        $wantsJson = $acceptsJson || $isApi;

        // Permite operador PDV (3) e admins (1) na API; exige empresa válida
        $tipoValido = in_array($tipo, [1,3], true);
        if (! $tipoValido || $idEmpresa <= 0) {
            $resp = ['error' => 'Não autenticado ou perfil inválido.'];
            // Para API, nunca redireciona: responde JSON 401
            if ($wantsJson) {
                return service('response')->setStatusCode(401)->setJSON($resp);
            }
            return redirect()->to('/login');
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->find($idEmpresa);
        if (!$empresa) {
            $resp = ['error' => 'Sessão inválida.'];
            if ($wantsJson) {
                return service('response')->setStatusCode(401)->setJSON($resp);
            }
            return redirect()->to('/login');
        }

        // Assinatura pode ser verificada por outro filtro (subscription)
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}


