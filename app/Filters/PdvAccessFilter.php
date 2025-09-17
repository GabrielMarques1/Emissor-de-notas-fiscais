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

        $acceptsJson = strpos((string) ($request->getHeaderLine('Accept') ?? ''), 'application/json') !== false;
        $isApi = str_starts_with($request->getUri()->getPath(), 'api');
        $wantsJson = $acceptsJson || $isApi;

        if ($tipo !== 3 || $idEmpresa <= 0) {
            if ($wantsJson) {
                return service('response')->setStatusCode(401)->setJSON(['error' => 'Não autenticado ou perfil inválido.']);
            }
            return redirect()->to('/login');
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->find($idEmpresa);
        if (!$empresa) {
            if ($wantsJson) {
                return service('response')->setStatusCode(401)->setJSON(['error' => 'Sessão inválida.']);
            }
            return redirect()->to('/login');
        }

        // Liberado: sem verificação de assinatura/Stripe.

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}


