<?php

namespace App\Filters;

use App\Models\EmpresaModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class SubscriptionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $idEmpresa = $session->get('id_empresa');
        if (!$idEmpresa) {
            return redirect()->to('/login');
        }

        $empresa = (new EmpresaModel())->find($idEmpresa);
        $status = $empresa['stripe_status'] ?? null;
        $periodEnd = $empresa['current_period_end'] ?? null;

        // Apenas 'active' (sem trialing) e período válido
        $isPaid = ($status === 'active');
        $isPeriodValid = true;
        if (!empty($periodEnd)) {
            $ts = strtotime((string) $periodEnd);
            $isPeriodValid = $ts !== false && $ts >= time();
        }

        if (!($isPaid && $isPeriodValid)) {
            return redirect()->to('/precos');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // noop
    }
}


