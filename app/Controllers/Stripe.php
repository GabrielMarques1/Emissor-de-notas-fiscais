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
            throw new \RuntimeException('Stripe secret não configurado (env stripe.secret ou STRIPE_SECRET).');
        }
        return new \Stripe\StripeClient([ 'api_key' => $secret ]);
    }

    public function createCheckoutSession()
    {
        $session = session();
        $idEmpresa = $session->get('id_empresa');

        $priceId = $this->request->getVar('price_id') ?: getenv('stripe.price') ?: getenv('STRIPE_PRICE');
        if (!$priceId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'price_id não informado']);
        }

        $client = $this->getStripeClient();

        $baseUrl = rtrim((string) (config('App')->baseURL ?? ''), '/');
        $successUrl = $baseUrl . '/stripe/success';
        $cancelUrl  = $baseUrl . '/stripe/cancel';

        // Fluxo autenticado (empresa já existente)
        if ($idEmpresa) {
            $empresa = $this->empresaModel->find($idEmpresa);
            if (!$empresa) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Empresa não encontrada']);
            }

            // Garante customer
            $customerId = $empresa['stripe_customer_id'] ?? null;
            if (!$customerId) {
                $customer = $client->customers->create([
                    'name' => $empresa['xFant'] ?? $empresa['xNome'] ?? 'Cliente',
                    'email' => null,
                    'metadata' => [
                        'id_empresa' => (string) $idEmpresa,
                        'CNPJ' => (string) ($empresa['CNPJ'] ?? ''),
                        'signup_mode' => 'logged',
                    ],
                ]);
                $customerId = $customer->id;
                $this->empresaModel->update($idEmpresa, [
                    'stripe_customer_id' => $customerId,
                ]);
            }

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
                'metadata' => [
                    'signup_mode' => 'logged',
                    'id_empresa' => (string) $idEmpresa,
                ],
            ]);

            return $this->response->setJSON(['id' => $sessionCheckout->id, 'url' => $sessionCheckout->url]);
        }

        // Fluxo guest (novo cliente sem login) - permite self-service
        $emailEmpresa = (string) ($this->request->getVar('email_empresa') ?? '');
        $nomeFantasia = (string) ($this->request->getVar('nome_fantasia') ?? '');
        $contadorEmail = (string) ($this->request->getVar('contador_email') ?? '');
        $cnpj = (string) ($this->request->getVar('cnpj') ?? '');

        if ($emailEmpresa === '' || $nomeFantasia === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Informe email_empresa e nome_fantasia']);
        }

        // Validação básica
        if (!filter_var($emailEmpresa, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'email_empresa inválido']);
        }
        if (!empty($contadorEmail) && !filter_var($contadorEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'contador_email inválido']);
        }
        if (!empty($cnpj) && function_exists('validarCNPJ') && !validarCNPJ($cnpj)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'CNPJ inválido']);
        }

        // Cria customer com dados para criação da conta no webhook
        $customer = $client->customers->create([
            'name' => $nomeFantasia,
            'email' => $emailEmpresa,
            'metadata' => [
                'signup_mode' => 'guest',
                'email_empresa' => $emailEmpresa,
                'nome_fantasia' => $nomeFantasia,
                'contador_email' => $contadorEmail,
                'cnpj' => $cnpj,
            ],
        ]);

        $sessionCheckout = $client->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customer->id,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
            'metadata' => [
                'signup_mode' => 'guest',
                'email_empresa' => $emailEmpresa,
                'nome_fantasia' => $nomeFantasia,
                'contador_email' => $contadorEmail,
                'cnpj' => $cnpj,
            ],
        ]);

        return $this->response->setJSON(['id' => $sessionCheckout->id, 'url' => $sessionCheckout->url]);
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

        $client = $this->getStripeClient();

        // Caso guest (sem id_empresa), criar empresa e login agora
        $signupMode = $sessionObj->metadata->signup_mode ?? null;
        if (!$idEmpresa && $signupMode === 'guest') {
            try {
                $customer = $client->customers->retrieve($customerId);
                $emailEmpresa = (string) ($customer->email ?? '');
                $nomeFantasia = (string) (($customer->metadata->nome_fantasia ?? '') ?: ($sessionObj->metadata->nome_fantasia ?? 'Cliente'));
                $contadorEmail = (string) ($customer->metadata->contador_email ?? '');
                $cnpj = (string) ($customer->metadata->cnpj ?? '');

                // 1) Criar login da empresa
                $loginModel = new \App\Models\LoginModel();
                $senhaPlano = bin2hex(random_bytes(4));
                $idLoginEmpresa = $loginModel->insert([
                    'usuario' => $emailEmpresa ?: ('empresa_' . $customerId),
                    'senha' => password_hash($senhaPlano, PASSWORD_DEFAULT),
                    'tipo' => 3,
                ]);

                // 2) Localizar/gerar contador por e-mail (opcional)
                $idContador = 1;
                $contadorCriadoSenha = null;
                $contadorLoginUsuario = null;
                if (!empty($contadorEmail)) {
                    $contadorModel = new \App\Models\ContadorModel();
                    $contador = $contadorModel->where('email', $contadorEmail)->first();
                    if ($contador && !empty($contador['id_contador'])) {
                        $idContador = (int) $contador['id_contador'];
                    } else {
                        // Criar login e contador mínimos
                        $senhaContador = bin2hex(random_bytes(4));
                        $idLoginCont = $loginModel->insert([
                            'usuario' => $contadorEmail,
                            'senha' => password_hash($senhaContador, PASSWORD_DEFAULT),
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
                            'email' => $contadorEmail,
                            'id_login' => $idLoginCont,
                        ]);
                        $contadorCriadoSenha = $senhaContador;
                        $contadorLoginUsuario = $contadorEmail;
                    }
                }

                // 3) Criar empresa mínima (campos obrigatórios com placeholders)
                $empresaData = [
                    'status' => 'Ativo',
                    'CNPJ' => $cnpj ?: '00000000000000',
                    'xNome' => $nomeFantasia,
                    'xFant' => $nomeFantasia,
                    'IE' => 'ISENTO',
                    'dia_do_pagamento' => 1,
                    'CEP' => '00000000',
                    'xLgr' => 'ENDERECO',
                    'nro' => 'S/N',
                    'xCpl' => '',
                    'xBairro' => 'BAIRRO',
                    'fone' => '',
                    'natOp' => 'VENDA',
                    'serie' => '1',
                    'verProc' => 'SaaS',
                    'nNF_homologacao' => '1',
                    'nNF_producao' => '1',
                    'tpAmb_NFe' => '2',
                    'nNFC_homologacao' => '1',
                    'nNFC_producao' => '1',
                    'tpAmb_NFCe' => '2',
                    'CSC_Id' => '000001',
                    'CSC' => 'AD6A9D2E-3F93-437F-BE5B-E8FA800A08F4',
                    'certificado' => null,
                    'senha_do_certificado' => null,
                    'id_login' => $idLoginEmpresa,
                    'id_contador' => $idContador,
                    'id_uf' => 1,
                    'id_municipio' => 1,
                ];
                $idEmpresa = $this->empresaModel->insert($empresaData);

                // Enviar e-mails de credenciais (opcional)
                try {
                    $mailer = \Config\Services::email();
                    $fromEmail = getenv('email.from') ?: 'no-reply@' . parse_url((string) (config('App')->baseURL ?? ''), PHP_URL_HOST);
                    $fromName = getenv('email.from_name') ?: (config('App')->appName ?? 'Sistema');
                    if (!empty($emailEmpresa)) {
                        $mailer->setFrom($fromEmail, $fromName);
                        $mailer->setTo($emailEmpresa);
                        $mailer->setSubject('Acesso liberado - Sua conta no sistema');
                        $mailer->setMessage("Olá,\n\nSeu acesso foi liberado.\nUsuário: {$emailEmpresa}\nSenha: {$senhaPlano}\n\nAcesse: " . rtrim((string) (config('App')->baseURL ?? ''), '/') . "/login\n\nQualquer dúvida, responda este e-mail.");
                        @$mailer->send();
                    }
                    if (!empty($contadorLoginUsuario) && !empty($contadorCriadoSenha)) {
                        $mailer->clear();
                        $mailer->setFrom($fromEmail, $fromName);
                        $mailer->setTo($contadorLoginUsuario);
                        $mailer->setSubject('Acesso de Contador - Sistema');
                        $mailer->setMessage("Olá,\n\nSeu acesso de contador foi criado.\nUsuário: {$contadorLoginUsuario}\nSenha: {$contadorCriadoSenha}\n\nAcesse: " . rtrim((string) (config('App')->baseURL ?? ''), '/') . "/login\n\nQualquer dúvida, responda este e-mail.");
                        @$mailer->send();
                    }
                } catch (\Throwable $e) { /* ignore email errors */ }
            } catch (\Throwable $e) {
                // se falhar criação, apenas retornar
            }
        }

        // Atualizar assinatura/period_end da empresa (guest ou logada)
        if ($idEmpresa) {
            $currentPeriodEnd = null;
            try {
                if ($subscriptionId) {
                    $subscription = $client->subscriptions->retrieve($subscriptionId);
                    if (!empty($subscription->current_period_end)) {
                        $currentPeriodEnd = date('Y-m-d H:i:s', (int) $subscription->current_period_end);
                    }
                    // Guardar price/product se disponível
                    $priceId = null; $productId = null;
                    try {
                        $items = $subscription->items->data ?? [];
                        if (!empty($items)) {
                            $priceId = $items[0]->price->id ?? null;
                            $productId = $items[0]->price->product ?? null;
                        }
                    } catch (\Throwable $e) {}
                }
            } catch (\Throwable $e) {}
            $update = [
                'stripe_customer_id' => $customerId,
                'stripe_subscription_id' => $subscriptionId,
                'stripe_status' => 'active',
                'current_period_end' => $currentPeriodEnd,
            ];
            if (!empty($priceId)) $update['stripe_price_id'] = $priceId;
            if (!empty($productId)) $update['stripe_product_id'] = $productId;
            $this->empresaModel->update($idEmpresa, $update);
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


