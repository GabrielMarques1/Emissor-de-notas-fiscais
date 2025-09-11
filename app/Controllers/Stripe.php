<?php

namespace App\Controllers;

use App\Models\EmpresaModel;
use CodeIgniter\HTTP\ResponseInterface;

class Stripe extends BaseController
{
    private $empresaModel;

    public function __construct()
    {
        $this->helpers = ['app'];
        $this->empresaModel = new EmpresaModel();
    }

    private function getStripeClient(): \Stripe\StripeClient
    {
        $secret = getenv('stripe.secret') ?: getenv('STRIPE_SECRET_KEY') ?: getenv('STRIPE_SECRET');
        if (!$secret) {
            // Para desenvolvimento: usar chave de teste padrão ou mostrar erro amigável
            $secret = 'sk_test_fake_key_for_development';
        }
        return new \Stripe\StripeClient([ 'api_key' => $secret ]);
    }

    public function createCheckoutSession()
    {
        $session = session();
        $idEmpresa = $session->get('id_empresa');
        if (!$idEmpresa) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Não autenticado']);
        }

        $priceId = $this->request->getVar('price_id') ?: getenv('stripe.price') ?: getenv('STRIPE_PRICE');
        if (!$priceId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'price_id não informado']);
        }

        $empresa = $this->empresaModel->find($idEmpresa);
        if (!$empresa) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Empresa não encontrada']);
        }

        $client = $this->getStripeClient();

        // Garante customer
        $customerId = $empresa['stripe_customer_id'] ?? null;
        if (!$customerId) {
            $customer = $client->customers->create([
                'name' => $empresa['xFant'] ?? $empresa['xNome'] ?? 'Cliente',
                'metadata' => [
                    'id_empresa' => (string) $idEmpresa,
                    'CNPJ' => (string) ($empresa['CNPJ'] ?? ''),
                ],
            ]);
            $customerId = $customer->id;
            $this->empresaModel->update($idEmpresa, [
                'stripe_customer_id' => $customerId,
            ]);
        }

        $baseUrl = rtrim((string) (config('App')->baseURL ?? ''), '/');
        $successUrl = $baseUrl . '/stripe/success';
        $cancelUrl  = $baseUrl . '/stripe/cancel';

        $sessionCheckout = $client->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
            'client_reference_id' => (string) $idEmpresa,
        ]);

        return $this->response->setJSON(['id' => $sessionCheckout->id, 'url' => $sessionCheckout->url]);
    }

    public function pay()
    {
        // Página/endpoint simples para redirecionar usuário ao checkout/portal
        // Tenta criar sessão de checkout com price padrão; se não houver price, cai para portal
        try {
            $priceId = $this->request->getVar('price_id') ?: getenv('stripe.price') ?: getenv('STRIPE_PRICE');
            if ($priceId) {
                $resp = $this->createCheckoutSession();
                $data = json_decode($resp->getBody(), true);
                if (isset($data['url'])) {
                    return redirect()->to($data['url']);
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }
        // fallback: portal do cliente
        $session = session();
        $idEmpresa = $session->get('id_empresa');
        if (!$idEmpresa) {
            return redirect()->to('/login');
        }
        $empresa = $this->empresaModel->find($idEmpresa);
        if (!$empresa || empty($empresa['stripe_customer_id'])) {
            // Se não existe customer ainda, cria e retorna ao checkout
            try {
                $client = $this->getStripeClient();
                $customer = $client->customers->create([
                    'name' => $empresa['xFant'] ?? $empresa['xNome'] ?? 'Cliente',
                    'metadata' => [
                        'id_empresa' => (string) $idEmpresa,
                        'CNPJ' => (string) ($empresa['CNPJ'] ?? ''),
                    ],
                ]);
                $this->empresaModel->update($idEmpresa, ['stripe_customer_id' => $customer->id]);
            } catch (\Throwable $e) {}
            return redirect()->to('/stripe/pay');
        }
        try {
            $client = $this->getStripeClient();
            $returnUrl = rtrim((string) (config('App')->baseURL ?? ''), '/') . '/inicio/emissor';
            $portal = $client->billingPortal->sessions->create([
                'customer' => $empresa['stripe_customer_id'],
                'return_url' => $returnUrl,
            ]);
            return redirect()->to($portal->url);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('Falha ao iniciar pagamento.');
        }
    }

    public function createPortalSession()
    {
        $session = session();
        $idEmpresa = $session->get('id_empresa');
        if (!$idEmpresa) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Não autenticado']);
        }
        $empresa = $this->empresaModel->find($idEmpresa);
        if (!$empresa || empty($empresa['stripe_customer_id'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Empresa sem customer Stripe']);
        }

        $client = $this->getStripeClient();
        $returnUrl = rtrim((string) (config('App')->baseURL ?? ''), '/') . '/inicio/emissor';
        $portal = $client->billingPortal->sessions->create([
            'customer' => $empresa['stripe_customer_id'],
            'return_url' => $returnUrl,
        ]);
        return $this->response->setJSON(['url' => $portal->url]);
    }

    public function webhook()
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpointSecret = getenv('stripe.webhook_secret') ?: getenv('STRIPE_WEBHOOK_SECRET');

        try {
            if ($endpointSecret) {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $event = json_decode($payload);
            }
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400, 'Invalid payload');
        }

        // Processa eventos importantes
        $type = is_object($event) ? ($event->type ?? '') : ($event['type'] ?? '');
        $data = is_object($event) ? ($event->data->object ?? null) : ($event['data']['object'] ?? null);

        if (!$data) {
            return $this->response->setStatusCode(200);
        }

        switch ($type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($data);
                break;
            case 'invoice.paid':
                $this->handleInvoicePaid($data);
                break;
            case 'customer.subscription.updated':
            case 'customer.subscription.created':
            case 'customer.subscription.deleted':
                $this->handleSubscriptionChange($data);
                break;
            case 'invoice.payment_failed':
            case 'invoice.payment_succeeded':
                // Pode atualizar status com base na invoice também
                break;
        }

        return $this->response->setStatusCode(200);
    }

    private function handleCheckoutCompleted($sessionObj): void
    {
        $subscriptionId = $sessionObj->subscription ?? null;
        $customerId = $sessionObj->customer ?? null;
        $idEmpresa = $sessionObj->client_reference_id ?? null;
        if (!$idEmpresa) {
            // Fallback: buscar empresa por customer
            $empresa = $this->empresaModel->where('stripe_customer_id', $customerId)->first();
            $idEmpresa = $empresa['id_empresa'] ?? null;
        }
        if ($idEmpresa) {
            $currentPeriodEnd = null;
            try {
                if ($subscriptionId) {
                    $client = $this->getStripeClient();
                    $subscription = $client->subscriptions->retrieve($subscriptionId);
                    if (!empty($subscription->current_period_end)) {
                        $currentPeriodEnd = date('Y-m-d H:i:s', (int) $subscription->current_period_end);
                    }
                }
            } catch (\Throwable $e) {}
            $this->empresaModel->update($idEmpresa, [
                'stripe_customer_id' => $customerId,
                'stripe_subscription_id' => $subscriptionId,
                'stripe_status' => 'active',
                'current_period_end' => $currentPeriodEnd,
            ]);
        }
    }

    private function handleInvoicePaid($invoiceObj): void
    {
        $subscriptionId = $invoiceObj->subscription ?? null;
        if (!$subscriptionId) return;
        try {
            $client = $this->getStripeClient();
            $subscription = $client->subscriptions->retrieve($subscriptionId);
            $currentPeriodEnd = !empty($subscription->current_period_end)
                ? date('Y-m-d H:i:s', (int) $subscription->current_period_end)
                : null;
            $empresa = $this->empresaModel->where('stripe_subscription_id', $subscriptionId)->first();
            if ($empresa) {
                $this->empresaModel->update($empresa['id_empresa'], [
                    'current_period_end' => $currentPeriodEnd,
                    'stripe_status' => 'active',
                ]);
            }
        } catch (\Throwable $e) {
            // noop
        }
    }

    private function handleSubscriptionChange($subscriptionObj): void
    {
        $subscriptionId = $subscriptionObj->id ?? null;
        if (!$subscriptionId) return;
        $status = $subscriptionObj->status ?? null; // trialing, active, past_due, canceled, unpaid, etc
        $customerId = $subscriptionObj->customer ?? null;

        $empresa = null;
        if ($customerId) {
            $empresa = $this->empresaModel->where('stripe_customer_id', $customerId)->first();
        }
        if (!$empresa) {
            $empresa = $this->empresaModel->where('stripe_subscription_id', $subscriptionId)->first();
        }
        if (!$empresa) return;

        $update = [
            'stripe_status' => $status,
            'stripe_subscription_id' => $subscriptionId,
        ];

        // Opcional: bloquear/desbloquear baseado no status
        if ($status === 'active' || $status === 'trialing') {
            $update['status'] = 'Ativo';
            $update['motivo_bloqueio'] = null;
            $update['data_bloqueio'] = null;
        } elseif (in_array($status, ['past_due','unpaid','canceled'], true)) {
            $update['status'] = 'Desativado';
            $update['motivo_bloqueio'] = 'Status da assinatura: ' . $status;
            $update['data_bloqueio'] = date('Y-m-d');
        }

        $this->empresaModel->update($empresa['id_empresa'], $update);
    }
}


