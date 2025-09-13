<?php

namespace App\Filters;

use App\Models\EmpresaModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SubscriptionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Apenas aplica para Empresas (tipo 3)
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        if ($tipo !== 3 || $idEmpresa <= 0) {
            return null;
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->find($idEmpresa);
        if (!$empresa) {
            $session->setFlashdata('alert', [
                'type'  => 'warning',
                'title' => 'Sessão inválida. Faça login novamente.'
            ]);
            return redirect()->to('/login');
        }

        $stripeStatus = (string) ($empresa['stripe_status'] ?? '');
        $currentPeriodEnd = (string) ($empresa['current_period_end'] ?? '');
        $trialEndsAt = (string) ($empresa['trial_ends_at'] ?? '');

        $allowTrial = (bool) ((getenv('stripe.allow_trial') ?: getenv('STRIPE_ALLOW_TRIAL')));

        $nowTs = time();
        $periodOk = true;
        if (!empty($currentPeriodEnd)) {
            $periodTs = strtotime($currentPeriodEnd) ?: 0;
            $periodOk = $periodTs >= $nowTs;
        }

        $trialOk = false;
        if ($allowTrial && !empty($trialEndsAt)) {
            $trialTs = strtotime($trialEndsAt) ?: 0;
            $trialOk = $trialTs >= $nowTs;
        }

        $statusOk = ($stripeStatus === 'active') || ($allowTrial && $stripeStatus === 'trialing');

        if (!($statusOk && ($periodOk || $trialOk))) {
            $session->setFlashdata('alert', [
                'type'  => 'warning',
                'title' => 'Acesso restrito a assinantes ativos. Conclua o pagamento para continuar.'
            ]);
            return redirect()->to('/login');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}


