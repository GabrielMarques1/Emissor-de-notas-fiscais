# 🚀 ROADMAP DE IMPLEMENTAÇÃO - PDV MULTI-TENANT

**Baseado em:** AUDITORIA_COMPLETA_PDV_MULTI_TENANT.md  
**Objetivo:** Guia técnico detalhado para implementar funcionalidades críticas  
**Foco:** Produção-ready em 4 semanas

---

## 📋 RESUMO EXECUTIVO

### Prioridades SPRINT 1-2 (Semana 1-2)
1. ✅ Integração TEF (40h)
2. ✅ Múltiplas Formas de Pagamento (16h)
3. ✅ PIX com QR Code (32h)

**Total:** 88 horas | **Meta:** Sistema 100% operacional com pagamentos eletrônicos

---

## 🔴 SPRINT 1: INTEGRAÇÃO TEF (40 horas)

### Objetivo
Permitir processamento de cartões de crédito/débito via TEF integrado a adquirentes brasileiras.

### Adquirentes Recomendadas
1. **Cielo** (maior market share)
2. **Stone** (tecnologia moderna, API REST)
3. **Rede** (segunda maior adquirente)

---

### 📦 Tarefa 1.1: Escolher e Configurar Biblioteca TEF (4h)

#### Opção A: Cloudwalk SDK (Recomendado)
```bash
composer require cloudwalk/pos-integration-sdk
```

#### Opção B: Integração Direta Cielo
```bash
composer require cielo/api-3.0-php
```

#### Configuração Multi-Tenant
```php
// app/Config/Tef.php
<?php
namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Tef extends BaseConfig
{
    public array $adquirentes = [
        'cielo' => [
            'class' => \App\Libraries\Tef\CieloAdapter::class,
            'endpoint_prod' => 'https://api.cielocommerce.cielo.com.br',
            'endpoint_homolog' => 'https://apisandbox.cielocommerce.cielo.com.br',
            'timeout' => 60, // segundos
            'retry_attempts' => 3,
        ],
        'stone' => [
            'class' => \App\Libraries\Tef\StoneAdapter::class,
            'endpoint_prod' => 'https://transaction.stone.com.br',
            'endpoint_homolog' => 'https://sandbox-transaction.stone.com.br',
            'timeout' => 45,
            'retry_attempts' => 3,
        ],
        'rede' => [
            'class' => \App\Libraries\Tef\RedeAdapter::class,
            'endpoint_prod' => 'https://api.userede.com.br',
            'endpoint_homolog' => 'https://api-homologacao.userede.com.br',
            'timeout' => 60,
            'retry_attempts' => 3,
        ],
    ];
    
    public array $tipos_transacao = [
        'credit' => 1,
        'debit' => 2,
        'voucher' => 3,
    ];
}
```

---

### 📦 Tarefa 1.2: Criar Migration de Transações TEF (2h)

```php
// app/Database/Migrations/2025-10-02-000001_CreateTefTransactions.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTefTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_tef_transaction' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            
            // Tenant
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            
            // Vinculação
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            
            // Transação
            'adquirente' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'cielo|stone|rede',
            ],
            'tipo_transacao' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'comment'    => 'credit|debit|voucher',
            ],
            'valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'parcelas' => [
                'type'       => 'TINYINT',
                'default'    => 1,
            ],
            
            // Dados da resposta TEF
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'pending|authorized|confirmed|cancelled|failed',
            ],
            'nsu' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'comment'    => 'Número Sequencial Único',
            ],
            'authorization_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'tid' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => 'Transaction ID da adquirente',
            ],
            'error_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'error_message' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            
            // Dados do cartão (mascarados)
            'card_brand' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'visa|mastercard|elo|amex',
            ],
            'card_last_digits' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            
            // Request/Response completos (para debug)
            'request_payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'response_payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            
            // Auditoria
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_tef_transaction', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('nsu');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('tef_transactions');
    }
    
    public function down()
    {
        $this->forge->dropTable('tef_transactions');
    }
}
```

---

### 📦 Tarefa 1.3: Adicionar Campos em Empresas (1h)

```php
// app/Database/Migrations/2025-10-02-000002_AddTefCredentialsToEmpresas.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTefCredentialsToEmpresas extends Migration
{
    public function up()
    {
        $fields = [
            'tef_adquirente' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'senha_do_certificado',
            ],
            'tef_merchant_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'tef_merchant_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true,
                'comment'    => 'Criptografado',
            ],
            'tef_terminal_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'tef_ambiente' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'homolog',
                'comment'    => 'homolog|prod',
            ],
            'tef_max_parcelas' => [
                'type'    => 'TINYINT',
                'default' => 12,
            ],
        ];
        
        $this->forge->addColumn('empresas', $fields);
    }
    
    public function down()
    {
        $this->forge->dropColumn('empresas', [
            'tef_adquirente',
            'tef_merchant_id',
            'tef_merchant_key',
            'tef_terminal_id',
            'tef_ambiente',
            'tef_max_parcelas',
        ]);
    }
}
```

---

### 📦 Tarefa 1.4: Criar Model TefTransactionModel (2h)

```php
// app/Models/TefTransactionModel.php
<?php
namespace App\Models;

class TefTransactionModel extends BaseAppModel
{
    protected $table = 'tef_transactions';
    protected $primaryKey = 'id_tef_transaction';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_contador', 'id_empresa', 'id_pos_sale',
        'adquirente', 'tipo_transacao', 'valor', 'parcelas',
        'status', 'nsu', 'authorization_code', 'tid',
        'error_code', 'error_message',
        'card_brand', 'card_last_digits',
        'request_payload', 'response_payload',
    ];
    
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    
    protected $validationRules = [
        'adquirente'      => 'required|in_list[cielo,stone,rede]',
        'tipo_transacao'  => 'required|in_list[credit,debit,voucher]',
        'valor'           => 'required|decimal',
        'parcelas'        => 'required|integer|greater_than[0]',
    ];
    
    /**
     * Busca transação por NSU (para estornos)
     */
    public function findByNsu(string $nsu, int $idEmpresa): ?array
    {
        return $this->where('nsu', $nsu)
                    ->where('id_empresa', $idEmpresa)
                    ->first();
    }
    
    /**
     * Lista transações pendentes (para retry)
     */
    public function getPending(int $idEmpresa, int $limit = 50): array
    {
        return $this->where('status', 'pending')
                    ->where('id_empresa', $idEmpresa)
                    ->where('created_at >', date('Y-m-d H:i:s', strtotime('-1 hour')))
                    ->orderBy('created_at', 'ASC')
                    ->findAll($limit);
    }
}
```

---

### 📦 Tarefa 1.5: Criar TefService (16h -核心)

```php
// app/Libraries/TefService.php
<?php
namespace App\Libraries;

use App\Models\TefTransactionModel;
use App\Models\EmpresaModel;
use App\Config\Tef as TefConfig;

class TefService
{
    protected TefTransactionModel $transactionModel;
    protected EmpresaModel $empresaModel;
    protected TefConfig $config;
    
    public function __construct()
    {
        $this->transactionModel = new TefTransactionModel();
        $this->empresaModel = new EmpresaModel();
        $this->config = config('Tef');
    }
    
    /**
     * Autoriza pagamento TEF
     * 
     * @param int $idEmpresa
     * @param float $valor
     * @param string $tipo ('credit'|'debit'|'voucher')
     * @param int $parcelas
     * @param array $dadosCartao ['numero', 'validade', 'cvv', 'titular']
     * @return array ['success' => bool, 'transaction' => array, 'error' => string]
     */
    public function authorize(
        int $idEmpresa,
        float $valor,
        string $tipo,
        int $parcelas = 1,
        array $dadosCartao = []
    ): array {
        try {
            // 1. Buscar credenciais do tenant
            $empresa = $this->empresaModel->find($idEmpresa);
            if (!$empresa || !$empresa['tef_merchant_id']) {
                return [
                    'success' => false,
                    'error' => 'Credenciais TEF não configuradas para esta empresa'
                ];
            }
            
            // 2. Validar dados
            if ($valor <= 0) {
                return ['success' => false, 'error' => 'Valor inválido'];
            }
            
            if ($tipo === 'debit' && $parcelas > 1) {
                return ['success' => false, 'error' => 'Débito não permite parcelamento'];
            }
            
            if ($parcelas > ($empresa['tef_max_parcelas'] ?? 12)) {
                return ['success' => false, 'error' => 'Número de parcelas excede o limite'];
            }
            
            // 3. Criar registro de transação
            $transactionData = [
                'id_contador' => $empresa['id_contador'],
                'id_empresa' => $idEmpresa,
                'adquirente' => $empresa['tef_adquirente'],
                'tipo_transacao' => $tipo,
                'valor' => $valor,
                'parcelas' => $parcelas,
                'status' => 'pending',
            ];
            
            $idTransaction = $this->transactionModel->insert($transactionData);
            
            // 4. Obter adapter da adquirente
            $adapter = $this->getAdapter($empresa['tef_adquirente'], $empresa);
            
            // 5. Preparar payload
            $payload = [
                'merchant_id' => $empresa['tef_merchant_id'],
                'merchant_key' => decrypt($empresa['tef_merchant_key']), // Descriptografar
                'terminal_id' => $empresa['tef_terminal_id'],
                'amount' => (int) ($valor * 100), // Centavos
                'payment_type' => $tipo,
                'installments' => $parcelas,
                'card' => $dadosCartao,
                'reference' => 'PDV-' . $idTransaction,
            ];
            
            // Salvar request para auditoria
            $this->transactionModel->update($idTransaction, [
                'request_payload' => json_encode($payload, JSON_PRETTY_PRINT)
            ]);
            
            // 6. Chamar API da adquirente
            log_message('info', "[TEF] Autorizando pagamento: empresa={$idEmpresa}, valor={$valor}, tipo={$tipo}");
            
            $response = $adapter->authorize($payload);
            
            // 7. Salvar response
            $this->transactionModel->update($idTransaction, [
                'response_payload' => json_encode($response, JSON_PRETTY_PRINT)
            ]);
            
            // 8. Processar resposta
            if ($response['success']) {
                $this->transactionModel->update($idTransaction, [
                    'status' => 'authorized',
                    'nsu' => $response['nsu'],
                    'authorization_code' => $response['authorization_code'],
                    'tid' => $response['tid'],
                    'card_brand' => $response['card_brand'] ?? null,
                    'card_last_digits' => $response['card_last_digits'] ?? null,
                ]);
                
                log_message('info', "[TEF] Pagamento autorizado: NSU={$response['nsu']}");
                
                return [
                    'success' => true,
                    'transaction' => $this->transactionModel->find($idTransaction),
                ];
            } else {
                $this->transactionModel->update($idTransaction, [
                    'status' => 'failed',
                    'error_code' => $response['error_code'] ?? 'UNKNOWN',
                    'error_message' => $response['error_message'] ?? 'Erro desconhecido',
                ]);
                
                log_message('error', "[TEF] Falha na autorização: {$response['error_message']}");
                
                return [
                    'success' => false,
                    'error' => $response['error_message'] ?? 'Falha ao processar pagamento',
                    'transaction' => $this->transactionModel->find($idTransaction),
                ];
            }
            
        } catch (\Throwable $e) {
            log_message('error', "[TEF] Exceção: " . $e->getMessage());
            
            if (isset($idTransaction)) {
                $this->transactionModel->update($idTransaction, [
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
            
            return [
                'success' => false,
                'error' => 'Erro interno ao processar pagamento: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Confirma pagamento autorizado (captura)
     */
    public function confirm(int $idTransaction): array
    {
        try {
            $transaction = $this->transactionModel->find($idTransaction);
            
            if (!$transaction) {
                return ['success' => false, 'error' => 'Transação não encontrada'];
            }
            
            if ($transaction['status'] !== 'authorized') {
                return ['success' => false, 'error' => 'Transação não está autorizada'];
            }
            
            $empresa = $this->empresaModel->find($transaction['id_empresa']);
            $adapter = $this->getAdapter($empresa['tef_adquirente'], $empresa);
            
            $response = $adapter->confirm($transaction['tid'], $transaction['valor'] * 100);
            
            if ($response['success']) {
                $this->transactionModel->update($idTransaction, [
                    'status' => 'confirmed',
                ]);
                
                log_message('info', "[TEF] Pagamento confirmado: NSU={$transaction['nsu']}");
                
                return ['success' => true];
            } else {
                log_message('error', "[TEF] Falha na confirmação: {$response['error_message']}");
                
                return ['success' => false, 'error' => $response['error_message']];
            }
            
        } catch (\Throwable $e) {
            log_message('error', "[TEF] Exceção na confirmação: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancela pagamento
     */
    public function cancel(int $idTransaction): array
    {
        try {
            $transaction = $this->transactionModel->find($idTransaction);
            
            if (!$transaction) {
                return ['success' => false, 'error' => 'Transação não encontrada'];
            }
            
            if (!in_array($transaction['status'], ['authorized', 'confirmed'])) {
                return ['success' => false, 'error' => 'Transação não pode ser cancelada'];
            }
            
            $empresa = $this->empresaModel->find($transaction['id_empresa']);
            $adapter = $this->getAdapter($empresa['tef_adquirente'], $empresa);
            
            $response = $adapter->cancel($transaction['tid'], $transaction['valor'] * 100);
            
            if ($response['success']) {
                $this->transactionModel->update($idTransaction, [
                    'status' => 'cancelled',
                ]);
                
                log_message('info', "[TEF] Pagamento cancelado: NSU={$transaction['nsu']}");
                
                return ['success' => true];
            } else {
                log_message('error', "[TEF] Falha no cancelamento: {$response['error_message']}");
                
                return ['success' => false, 'error' => $response['error_message']];
            }
            
        } catch (\Throwable $e) {
            log_message('error', "[TEF] Exceção no cancelamento: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Consulta status de transação
     */
    public function query(int $idTransaction): array
    {
        $transaction = $this->transactionModel->find($idTransaction);
        
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transação não encontrada'];
        }
        
        try {
            $empresa = $this->empresaModel->find($transaction['id_empresa']);
            $adapter = $this->getAdapter($empresa['tef_adquirente'], $empresa);
            
            $response = $adapter->query($transaction['tid']);
            
            if ($response['success']) {
                // Atualizar status se mudou
                if (isset($response['status']) && $response['status'] !== $transaction['status']) {
                    $this->transactionModel->update($idTransaction, [
                        'status' => $response['status'],
                    ]);
                }
                
                return [
                    'success' => true,
                    'transaction' => $this->transactionModel->find($idTransaction),
                ];
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtém adapter da adquirente
     */
    protected function getAdapter(string $adquirente, array $empresa): object
    {
        $config = $this->config->adquirentes[$adquirente] ?? null;
        
        if (!$config) {
            throw new \RuntimeException("Adquirente '{$adquirente}' não configurada");
        }
        
        $class = $config['class'];
        
        if (!class_exists($class)) {
            throw new \RuntimeException("Classe '{$class}' não encontrada");
        }
        
        $ambiente = $empresa['tef_ambiente'] ?? 'homolog';
        $endpoint = $ambiente === 'prod' ? $config['endpoint_prod'] : $config['endpoint_homolog'];
        
        return new $class($endpoint, $config);
    }
}
```

---

### 📦 Tarefa 1.6: Criar Adapters por Adquirente (12h)

#### Adapter Cielo
```php
// app/Libraries/Tef/CieloAdapter.php
<?php
namespace App\Libraries\Tef;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CieloAdapter implements TefAdapterInterface
{
    protected string $endpoint;
    protected array $config;
    protected Client $http;
    
    public function __construct(string $endpoint, array $config)
    {
        $this->endpoint = $endpoint;
        $this->config = $config;
        $this->http = new Client([
            'base_uri' => $endpoint,
            'timeout' => $config['timeout'] ?? 60,
            'verify' => true,
        ]);
    }
    
    public function authorize(array $payload): array
    {
        try {
            $body = [
                'MerchantOrderId' => $payload['reference'],
                'Customer' => [
                    'Name' => $payload['card']['titular'] ?? 'Cliente PDV',
                ],
                'Payment' => [
                    'Type' => $this->mapPaymentType($payload['payment_type']),
                    'Amount' => $payload['amount'],
                    'Installments' => $payload['installments'],
                    'SoftDescriptor' => 'PDV',
                    'Capture' => false, // Autorização apenas (captura depois)
                    'CreditCard' => [
                        'CardNumber' => $payload['card']['numero'],
                        'Holder' => $payload['card']['titular'],
                        'ExpirationDate' => $payload['card']['validade'],
                        'SecurityCode' => $payload['card']['cvv'],
                        'Brand' => $this->detectBrand($payload['card']['numero']),
                    ],
                ],
            ];
            
            $response = $this->http->post('/1/sales', [
                'json' => $body,
                'headers' => [
                    'MerchantId' => $payload['merchant_id'],
                    'MerchantKey' => $payload['merchant_key'],
                    'Content-Type' => 'application/json',
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            // Verificar status
            $paymentData = $data['Payment'] ?? [];
            $status = (int) ($paymentData['Status'] ?? 0);
            
            // Status Cielo: 1=Autorizado, 2=Capturado
            if (in_array($status, [1, 2])) {
                return [
                    'success' => true,
                    'nsu' => $paymentData['ProofOfSale'] ?? '',
                    'authorization_code' => $paymentData['AuthorizationCode'] ?? '',
                    'tid' => $paymentData['PaymentId'] ?? '',
                    'card_brand' => strtolower($paymentData['Provider'] ?? ''),
                    'card_last_digits' => substr($payload['card']['numero'], -4),
                ];
            }
            
            return [
                'success' => false,
                'error_code' => (string) $status,
                'error_message' => $paymentData['ReturnMessage'] ?? 'Transação negada',
            ];
            
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error_code' => 'HTTP_' . $e->getCode(),
                'error_message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error_code' => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ];
        }
    }
    
    public function confirm(string $tid, int $amount): array
    {
        try {
            $response = $this->http->put("/1/sales/{$tid}/capture", [
                'query' => ['amount' => $amount],
                'headers' => [
                    'MerchantId' => request()->getHeaderLine('MerchantId'),
                    'MerchantKey' => request()->getHeaderLine('MerchantKey'),
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $status = (int) ($data['Status'] ?? 0);
            
            return [
                'success' => ($status === 2), // 2 = Capturado
                'error_message' => $data['ReturnMessage'] ?? '',
            ];
            
        } catch (\Throwable $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }
    
    public function cancel(string $tid, int $amount): array
    {
        try {
            $response = $this->http->put("/1/sales/{$tid}/void", [
                'query' => ['amount' => $amount],
                'headers' => [
                    'MerchantId' => request()->getHeaderLine('MerchantId'),
                    'MerchantKey' => request()->getHeaderLine('MerchantKey'),
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $status = (int) ($data['Status'] ?? 0);
            
            return [
                'success' => ($status === 10 || $status === 11), // 10/11 = Cancelado
                'error_message' => $data['ReturnMessage'] ?? '',
            ];
            
        } catch (\Throwable $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }
    
    public function query(string $tid): array
    {
        try {
            $response = $this->http->get("/1/sales/{$tid}", [
                'headers' => [
                    'MerchantId' => request()->getHeaderLine('MerchantId'),
                    'MerchantKey' => request()->getHeaderLine('MerchantKey'),
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $paymentData = $data['Payment'] ?? [];
            
            return [
                'success' => true,
                'status' => $this->mapCieloStatus((int) ($paymentData['Status'] ?? 0)),
            ];
            
        } catch (\Throwable $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }
    
    protected function mapPaymentType(string $type): string
    {
        return match($type) {
            'credit' => 'CreditCard',
            'debit' => 'DebitCard',
            default => 'CreditCard',
        };
    }
    
    protected function detectBrand(string $cardNumber): string
    {
        $firstDigit = substr($cardNumber, 0, 1);
        
        return match($firstDigit) {
            '4' => 'Visa',
            '5' => 'Master',
            '6' => 'Discover',
            '3' => 'Amex',
            default => 'Elo',
        };
    }
    
    protected function mapCieloStatus(int $status): string
    {
        return match($status) {
            0 => 'pending',
            1 => 'authorized',
            2 => 'confirmed',
            10, 11 => 'cancelled',
            3, 13 => 'failed',
            default => 'pending',
        };
    }
}
```

#### Interface Padrão
```php
// app/Libraries/Tef/TefAdapterInterface.php
<?php
namespace App\Libraries\Tef;

interface TefAdapterInterface
{
    public function authorize(array $payload): array;
    public function confirm(string $tid, int $amount): array;
    public function cancel(string $tid, int $amount): array;
    public function query(string $tid): array;
}
```

---

### 📦 Tarefa 1.7: Integrar TEF no Pos Controller (3h)

```php
// app/Controllers/Api/Pos.php (adicionar ao finalize)

// Linha ~195, após validação de venda
use App\Libraries\TefService;

// ...

// Se pagamento é eletrônico, processar TEF
if (in_array($data['payment_type'], ['credit', 'debit'])) {
    $tefService = new TefService();
    
    $dadosCartao = [
        'numero' => $payload['card_number'] ?? '',
        'titular' => $payload['card_holder'] ?? '',
        'validade' => $payload['card_expiry'] ?? '',
        'cvv' => $payload['card_cvv'] ?? '',
    ];
    
    $parcelas = (int) ($payload['installments'] ?? 1);
    
    // Autorizar pagamento
    $tefResult = $tefService->authorize(
        $idEmpresa,
        (float) $data['total'],
        $data['payment_type'],
        $parcelas,
        $dadosCartao
    );
    
    if (!$tefResult['success']) {
        $db->transRollback();
        return $this->fail('Pagamento recusado: ' . $tefResult['error'], 402);
    }
    
    $idTefTransaction = $tefResult['transaction']['id_tef_transaction'];
    
    // Confirmar (capturar) pagamento
    $confirmResult = $tefService->confirm($idTefTransaction);
    
    if (!$confirmResult['success']) {
        // Falha na confirmação: cancelar autorização
        $tefService->cancel($idTefTransaction);
        $db->transRollback();
        return $this->fail('Falha ao confirmar pagamento', 500);
    }
    
    // Vincular transação TEF à venda
    $data['id_tef_transaction'] = $idTefTransaction;
}

// Continuar com fluxo normal...
```

---

### 📦 Tarefa 1.8: Testes e Homologação (2h)

```php
// tests/Libraries/TefServiceTest.php
<?php
namespace Tests\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\TefService;

class TefServiceTest extends CIUnitTestCase
{
    protected TefService $tefService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->tefService = new TefService();
    }
    
    public function testAuthorizeCreditCard()
    {
        $result = $this->tefService->authorize(
            idEmpresa: 1,
            valor: 100.00,
            tipo: 'credit',
            parcelas: 1,
            dadosCartao: [
                'numero' => '4111111111111111',
                'titular' => 'TESTE CIELO',
                'validade' => '12/2030',
                'cvv' => '123',
            ]
        );
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction', $result);
        $this->assertNotEmpty($result['transaction']['nsu']);
    }
    
    public function testCancelTransaction()
    {
        // Autorizar
        $auth = $this->tefService->authorize(1, 50.00, 'credit', 1, []);
        $this->assertTrue($auth['success']);
        
        $idTransaction = $auth['transaction']['id_tef_transaction'];
        
        // Cancelar
        $cancel = $this->tefService->cancel($idTransaction);
        $this->assertTrue($cancel['success']);
    }
}
```

---

## 🟡 SPRINT 2: MÚLTIPLAS FORMAS DE PAGAMENTO (16 horas)

### Objetivo
Permitir que uma venda seja paga com múltiplas formas (Ex: R$ 50 dinheiro + R$ 50 cartão).

---

### 📦 Tarefa 2.1: Criar Tabela pos_sale_payments (2h)

```php
// app/Database/Migrations/2025-10-03-000001_CreatePosSalePayments.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePosSalePayments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_payment' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            
            'payment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'comment'    => 'cash|credit|debit|pix|voucher',
            ],
            
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            
            // Vínculo com transação TEF (se aplicável)
            'id_tef_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            
            // Vínculo com transação PIX (se aplicável)
            'id_pix_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            
            // Parcelamento (se crédito)
            'installments' => [
                'type'    => 'TINYINT',
                'default' => 1,
            ],
            
            // Auditoria
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_payment', true);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('id_tef_transaction');
        $this->forge->addKey('id_pix_transaction');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('pos_sale_payments');
    }
    
    public function down()
    {
        $this->forge->dropTable('pos_sale_payments');
    }
}
```

---

### 📦 Tarefa 2.2: Criar Model PosSalePaymentModel (1h)

```php
// app/Models/PosSalePaymentModel.php
<?php
namespace App\Models;

class PosSalePaymentModel extends BaseAppModel
{
    protected $enforceTenant = false; // Não possui id_contador/id_empresa diretamente
    
    protected $table = 'pos_sale_payments';
    protected $primaryKey = 'id_payment';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'payment_type', 'amount',
        'id_tef_transaction', 'id_pix_transaction',
        'installments',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'id_pos_sale'  => 'required|integer',
        'payment_type' => 'required|in_list[cash,credit,debit,pix,voucher]',
        'amount'       => 'required|decimal|greater_than[0]',
    ];
    
    /**
     * Busca pagamentos de uma venda
     */
    public function getBySale(int $idPosSale): array
    {
        return $this->where('id_pos_sale', $idPosSale)
                    ->orderBy('id_payment', 'ASC')
                    ->findAll();
    }
    
    /**
     * Calcula total pago em uma venda
     */
    public function getTotalPaid(int $idPosSale): float
    {
        $result = $this->selectSum('amount', 'total')
                       ->where('id_pos_sale', $idPosSale)
                       ->first();
        
        return (float) ($result['total'] ?? 0);
    }
}
```

---

### 📦 Tarefa 2.3: Alterar Método finalize() para Aceitar Array de Pagamentos (8h)

```php
// app/Controllers/Api/Pos.php (refatorar finalize)

public function finalize($id = null)
{
    if ($id === null) return $this->failValidationErrors('ID é obrigatório');
    
    $payload = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
        ? ($this->request->getJSON(true) ?? $this->request->getRawInput())
        : [];
    
    // Validar venda
    $sale = $this->model->find($id);
    if (!$sale) {
        return $this->failNotFound('Venda não encontrada');
    }
    
    // Extrair pagamentos
    $payments = $payload['payments'] ?? [];
    
    if (empty($payments)) {
        return $this->failValidationErrors('Nenhuma forma de pagamento informada');
    }
    
    // Validar que soma dos pagamentos = total da venda
    $totalSale = (float) (is_array($sale) ? ($sale['total'] ?? 0) : ($sale->total ?? 0));
    $totalPayments = array_reduce($payments, fn($sum, $p) => $sum + (float) ($p['amount'] ?? 0), 0);
    
    if (abs($totalPayments - $totalSale) > 0.01) { // Tolerância de 1 centavo
        return $this->failValidationErrors(
            "Soma dos pagamentos (R$ {$totalPayments}) difere do total da venda (R$ {$totalSale})"
        );
    }
    
    // Iniciar transação
    $db = \Config\Database::connect();
    $db->transStart();
    
    $session = session();
    $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
    
    // Processar cada pagamento
    $paymentModel = new \App\Models\PosSalePaymentModel();
    $tefService = new \App\Libraries\TefService();
    $pixService = new \App\Libraries\PixService();
    
    $processedPayments = [];
    
    foreach ($payments as $payment) {
        $paymentType = $payment['type'];
        $paymentAmount = (float) $payment['amount'];
        $installments = (int) ($payment['installments'] ?? 1);
        
        $paymentData = [
            'id_pos_sale' => $id,
            'payment_type' => $paymentType,
            'amount' => $paymentAmount,
            'installments' => $installments,
        ];
        
        // Se é pagamento eletrônico (TEF)
        if (in_array($paymentType, ['credit', 'debit'])) {
            $tefResult = $tefService->authorize(
                $idEmpresa,
                $paymentAmount,
                $paymentType,
                $installments,
                $payment['card'] ?? []
            );
            
            if (!$tefResult['success']) {
                $db->transRollback();
                return $this->fail("Pagamento {$paymentType} recusado: " . $tefResult['error'], 402);
            }
            
            $idTef = $tefResult['transaction']['id_tef_transaction'];
            
            // Confirmar
            $confirmResult = $tefService->confirm($idTef);
            if (!$confirmResult['success']) {
                $tefService->cancel($idTef);
                $db->transRollback();
                return $this->fail('Falha ao confirmar pagamento TEF', 500);
            }
            
            $paymentData['id_tef_transaction'] = $idTef;
        }
        
        // Se é PIX
        if ($paymentType === 'pix') {
            // Verifica se já foi pago (webhook confirmou)
            if (isset($payment['pix_txid'])) {
                $pixTransaction = $pixService->getByTxid($payment['pix_txid']);
                
                if (!$pixTransaction || $pixTransaction['status'] !== 'confirmed') {
                    $db->transRollback();
                    return $this->fail('Pagamento PIX não confirmado', 402);
                }
                
                $paymentData['id_pix_transaction'] = $pixTransaction['id_pix_transaction'];
            } else {
                // Gerar QR Code para pagar depois
                // (não bloqueia finalização, mas venda fica pending)
            }
        }
        
        // Registrar pagamento
        $paymentModel->insert($paymentData);
        $processedPayments[] = $paymentData;
    }
    
    // Atualizar venda
    $this->model->update($id, [
        'status' => 'finalized',
        'paid_amount' => $totalPayments,
        'change_amount' => 0, // Sem troco em múltiplos pagamentos
    ]);
    
    // Continuar fluxo normal (estoque, NFC-e, etc.)
    // ...
    
    $db->transComplete();
    
    if ($db->transStatus() === false) {
        return $this->failServerError('Falha ao finalizar venda');
    }
    
    $final = $this->model->find($id);
    $final['payments'] = $paymentModel->getBySale($id);
    
    return $this->respond($final);
}
```

---

### 📦 Tarefa 2.4: Atualizar Relatórios de Caixa (3h)

```php
// app/Models/CaixaSessaoModel.php (refatorar closeOpenSession)

public function closeOpenSession(int $idContador, int $idEmpresa, int $idUsuario, float $valorContado): array
{
    // ... código existente...
    
    // NOVO: Buscar totais por forma de pagamento da tabela pos_sale_payments
    $rows = $db->table('pos_sale_payments as pp')
        ->select('pp.payment_type, SUM(pp.amount) as valor')
        ->join('pos_sales as s', 's.id_pos_sale = pp.id_pos_sale')
        ->where('s.status', 'finalized')
        ->where('s.id_caixa_sessao', $idCaixa)
        ->groupBy('pp.payment_type')
        ->get()->getResultArray();
    
    $totais = ['cash'=>0.0,'credit'=>0.0,'debit'=>0.0,'pix'=>0.0,'voucher'=>0.0,'others'=>0.0];
    foreach ($rows as $r) {
        $pt = strtolower((string) ($r['payment_type'] ?? ''));
        $v  = (float) ($r['valor'] ?? 0);
        if (array_key_exists($pt, $totais)) { $totais[$pt] += $v; } else { $totais['others'] += $v; }
    }
    
    // Restante do código...
}
```

---

### 📦 Tarefa 2.5: Migração de Dados Existentes (2h)

```php
// app/Database/Migrations/2025-10-03-000002_MigrateExistingPayments.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigrateExistingPayments extends Migration
{
    public function up()
    {
        // Migrar vendas finalizadas existentes para tabela de pagamentos
        $db = \Config\Database::connect();
        
        $sales = $db->table('pos_sales')
            ->select('id_pos_sale, payment_type, total')
            ->where('status', 'finalized')
            ->whereNotNull('payment_type')
            ->get()->getResultArray();
        
        foreach ($sales as $sale) {
            $db->table('pos_sale_payments')->insert([
                'id_pos_sale' => $sale['id_pos_sale'],
                'payment_type' => $sale['payment_type'],
                'amount' => $sale['total'],
                'installments' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        log_message('info', "Migrados " . count($sales) . " pagamentos para pos_sale_payments");
    }
    
    public function down()
    {
        // Reverter migração (opcional)
        $db = \Config\Database::connect();
        $db->table('pos_sale_payments')->truncate();
    }
}
```

---

## 🟢 SPRINT 3: PIX COM QR CODE E WEBHOOK (32 horas)

### Objetivo
Integrar PIX com geração de QR Code dinâmico e confirmação automática via webhook.

---

### 📦 Tarefa 3.1: Criar Tabela pix_transactions (2h)

```php
// app/Database/Migrations/2025-10-04-000001_CreatePixTransactions.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePixTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pix_transaction' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            
            // Tenant
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            
            // Vínculo
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            
            // Dados da transação
            'txid' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'unique'     => true,
                'comment'    => 'ID único da transação PIX',
            ],
            'e2e_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => 'End-to-End ID (após confirmação)',
            ],
            'valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'pending|confirmed|expired|failed',
            ],
            
            // QR Code
            'qr_code' => [
                'type' => 'TEXT',
                'comment' => 'BR Code (copia-e-cola)',
            ],
            'qr_code_image' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Base64 da imagem do QR Code',
            ],
            
            // Provedor PIX
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'comment'    => 'mercadopago|pagseguro|banco',
            ],
            
            // Timeout
            'expires_at' => [
                'type' => 'DATETIME',
                'comment' => 'Data/hora de expiração do QR Code',
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            
            // Webhook
            'webhook_payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            
            // Auditoria
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_pix_transaction', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('txid');
        $this->forge->addKey('e2e_id');
        $this->forge->addKey(['status', 'created_at']);
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('pix_transactions');
    }
    
    public function down()
    {
        $this->forge->dropTable('pix_transactions');
    }
}
```

---

### 📦 Tarefa 3.2: Adicionar Credenciais PIX em Empresas (1h)

```php
// app/Database/Migrations/2025-10-04-000002_AddPixCredentialsToEmpresas.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPixCredentialsToEmpresas extends Migration
{
    public function up()
    {
        $fields = [
            'pix_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'comment'    => 'mercadopago|pagseguro|banco',
            ],
            'pix_access_token' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Token de acesso (criptografado)',
            ],
            'pix_webhook_secret' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => 'Secret para validar webhooks',
            ],
            'pix_expires_minutes' => [
                'type'    => 'SMALLINT',
                'default' => 5,
                'comment' => 'Tempo de expiração do QR Code em minutos',
            ],
        ];
        
        $this->forge->addColumn('empresas', $fields);
    }
    
    public function down()
    {
        $this->forge->dropColumn('empresas', [
            'pix_provider',
            'pix_access_token',
            'pix_webhook_secret',
            'pix_expires_minutes',
        ]);
    }
}
```

---

### 📦 Tarefa 3.3: Criar PixService (16h - Núcleo)

```php
// app/Libraries/PixService.php
<?php
namespace App\Libraries;

use App\Models\PixTransactionModel;
use App\Models\EmpresaModel;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;

class PixService
{
    protected PixTransactionModel $transactionModel;
    protected EmpresaModel $empresaModel;
    
    public function __construct()
    {
        $this->transactionModel = new PixTransactionModel();
        $this->empresaModel = new EmpresaModel();
    }
    
    /**
     * Gera QR Code PIX dinâmico
     * 
     * @param int $idEmpresa
     * @param float $valor
     * @param string $descricao
     * @return array ['success' => bool, 'transaction' => array, 'error' => string]
     */
    public function generate(int $idEmpresa, float $valor, string $descricao = 'Compra PDV'): array
    {
        try {
            // 1. Buscar credenciais do tenant
            $empresa = $this->empresaModel->find($idEmpresa);
            
            if (!$empresa || !$empresa['pix_access_token']) {
                return [
                    'success' => false,
                    'error' => 'Credenciais PIX não configuradas para esta empresa'
                ];
            }
            
            // 2. Gerar TXID único
            $txid = $this->generateTxid();
            
            // 3. Calcular expiração
            $expiresMinutes = (int) ($empresa['pix_expires_minutes'] ?? 5);
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresMinutes} minutes"));
            
            // 4. Criar registro de transação
            $transactionData = [
                'id_contador' => $empresa['id_contador'],
                'id_empresa' => $idEmpresa,
                'txid' => $txid,
                'valor' => $valor,
                'status' => 'pending',
                'provider' => $empresa['pix_provider'],
                'expires_at' => $expiresAt,
            ];
            
            $idTransaction = $this->transactionModel->insert($transactionData);
            
            // 5. Obter adapter do provedor
            $adapter = $this->getAdapter($empresa['pix_provider'], $empresa);
            
            // 6. Gerar cobrança no provedor
            $payload = [
                'txid' => $txid,
                'valor' => (int) ($valor * 100), // Centavos
                'chave_pix' => $empresa['chave_pix'] ?? '',
                'devedor_nome' => 'Cliente PDV',
                'descricao' => $descricao,
                'expiracao' => $expiresMinutes * 60, // Segundos
            ];
            
            $response = $adapter->createCharge($payload);
            
            if (!$response['success']) {
                $this->transactionModel->update($idTransaction, [
                    'status' => 'failed',
                ]);
                
                return [
                    'success' => false,
                    'error' => $response['error_message'] ?? 'Falha ao gerar QR Code PIX',
                ];
            }
            
            // 7. Gerar QR Code (imagem)
            $qrCodeImage = $this->generateQrCodeImage($response['qr_code']);
            
            // 8. Atualizar transação
            $this->transactionModel->update($idTransaction, [
                'qr_code' => $response['qr_code'],
                'qr_code_image' => $qrCodeImage,
            ]);
            
            log_message('info', "[PIX] QR Code gerado: TXID={$txid}, Valor={$valor}");
            
            return [
                'success' => true,
                'transaction' => $this->transactionModel->find($idTransaction),
            ];
            
        } catch (\Throwable $e) {
            log_message('error', "[PIX] Exceção ao gerar QR Code: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Erro interno ao gerar QR Code: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Confirma pagamento PIX (chamado pelo webhook)
     */
    public function confirm(string $txid, string $e2eId, array $webhookData = []): array
    {
        try {
            $transaction = $this->transactionModel->findByTxid($txid);
            
            if (!$transaction) {
                return ['success' => false, 'error' => 'Transação não encontrada'];
            }
            
            if ($transaction['status'] === 'confirmed') {
                return ['success' => true, 'message' => 'Já confirmado'];
            }
            
            // Atualizar transação
            $this->transactionModel->update($transaction['id_pix_transaction'], [
                'status' => 'confirmed',
                'e2e_id' => $e2eId,
                'confirmed_at' => date('Y-m-d H:i:s'),
                'webhook_payload' => json_encode($webhookData, JSON_PRETTY_PRINT),
            ]);
            
            log_message('info', "[PIX] Pagamento confirmado: TXID={$txid}, E2E={$e2eId}");
            
            // Se vinculado a uma venda, atualizar status
            if ($transaction['id_pos_sale']) {
                $saleModel = new \App\Models\PosSaleModel();
                $sale = $saleModel->find($transaction['id_pos_sale']);
                
                if ($sale && $sale['status'] === 'pending_payment') {
                    $saleModel->update($transaction['id_pos_sale'], [
                        'status' => 'finalized',
                    ]);
                    
                    log_message('info', "[PIX] Venda finalizada automaticamente: id_pos_sale={$transaction['id_pos_sale']}");
                }
            }
            
            return ['success' => true];
            
        } catch (\Throwable $e) {
            log_message('error', "[PIX] Exceção ao confirmar: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Expira QR Codes antigos
     */
    public function expireOld(): int
    {
        $expired = $this->transactionModel
            ->where('status', 'pending')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->findAll();
        
        foreach ($expired as $tx) {
            $this->transactionModel->update($tx['id_pix_transaction'], [
                'status' => 'expired',
            ]);
        }
        
        log_message('info', "[PIX] Expirados " . count($expired) . " QR Codes");
        
        return count($expired);
    }
    
    /**
     * Gera TXID único (26-35 caracteres alfanuméricos)
     */
    protected function generateTxid(): string
    {
        return strtoupper(bin2hex(random_bytes(13))); // 26 caracteres
    }
    
    /**
     * Gera imagem do QR Code em Base64
     */
    protected function generateQrCodeImage(string $brCode): string
    {
        $result = Builder::create()
            ->data($brCode)
            ->encoding(new Encoding('UTF-8'))
            ->size(300)
            ->margin(10)
            ->build();
        
        return base64_encode($result->getString());
    }
    
    /**
     * Obtém adapter do provedor PIX
     */
    protected function getAdapter(string $provider, array $empresa): object
    {
        $class = match($provider) {
            'mercadopago' => \App\Libraries\Pix\MercadoPagoAdapter::class,
            'pagseguro' => \App\Libraries\Pix\PagSeguroAdapter::class,
            'banco' => \App\Libraries\Pix\BancoAdapter::class,
            default => throw new \RuntimeException("Provedor PIX '{$provider}' não suportado"),
        };
        
        if (!class_exists($class)) {
            throw new \RuntimeException("Classe '{$class}' não encontrada");
        }
        
        return new $class($empresa);
    }
    
    /**
     * Busca transação por TXID
     */
    public function getByTxid(string $txid): ?array
    {
        return $this->transactionModel->findByTxid($txid);
    }
}
```

---

### 📦 Tarefa 3.4: Criar Webhook Controller (4h)

```php
// app/Controllers/Api/PixWebhook.php
<?php
namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\PixService;
use App\Models\EmpresaModel;

class PixWebhook extends ResourceController
{
    protected $format = 'json';
    
    /**
     * Webhook do Mercado Pago
     * POST /api/pix/webhook/mercadopago/{id_empresa}
     */
    public function mercadopago(int $idEmpresa = null)
    {
        if (!$idEmpresa) {
            return $this->failValidationErrors('ID empresa obrigatório na URL');
        }
        
        try {
            // Validar assinatura (HMAC)
            $empresaModel = new EmpresaModel();
            $empresa = $empresaModel->find($idEmpresa);
            
            if (!$empresa) {
                log_message('error', "[PIX Webhook] Empresa não encontrada: id={$idEmpresa}");
                return $this->failNotFound('Empresa não encontrada');
            }
            
            $secret = $empresa['pix_webhook_secret'] ?? '';
            
            if (!$this->validateSignature($secret)) {
                log_message('error', "[PIX Webhook] Assinatura inválida para empresa id={$idEmpresa}");
                return $this->fail('Assinatura inválida', 401);
            }
            
            // Processar payload
            $payload = $this->request->getJSON(true) ?? [];
            
            log_message('info', "[PIX Webhook] Payload recebido: " . json_encode($payload));
            
            // Mercado Pago envia notificação de cobrança aprovada
            $action = $payload['action'] ?? '';
            $dataId = $payload['data']['id'] ?? '';
            
            if ($action !== 'payment.created' || !$dataId) {
                return $this->respond(['message' => 'Evento ignorado']);
            }
            
            // Buscar detalhes do pagamento na API do Mercado Pago
            $paymentDetails = $this->fetchMercadoPagoPayment($dataId, $empresa);
            
            if (!$paymentDetails || $paymentDetails['status'] !== 'approved') {
                return $this->respond(['message' => 'Pagamento não aprovado']);
            }
            
            // Confirmar na nossa base
            $txid = $paymentDetails['external_reference'] ?? '';
            $e2eId = $paymentDetails['id'] ?? '';
            
            if ($txid) {
                $pixService = new PixService();
                $result = $pixService->confirm($txid, $e2eId, $payload);
                
                if ($result['success']) {
                    return $this->respond(['message' => 'Pagamento confirmado']);
                }
            }
            
            return $this->respond(['message' => 'TXID não encontrado']);
            
        } catch (\Throwable $e) {
            log_message('error', "[PIX Webhook] Exceção: " . $e->getMessage());
            return $this->failServerError('Erro ao processar webhook');
        }
    }
    
    /**
     * Valida assinatura HMAC do webhook
     */
    protected function validateSignature(string $secret): bool
    {
        $signature = $this->request->getHeaderLine('X-Signature');
        $body = $this->request->getBody();
        
        $expected = hash_hmac('sha256', $body, $secret);
        
        return hash_equals($expected, $signature);
    }
    
    /**
     * Busca detalhes do pagamento na API do Mercado Pago
     */
    protected function fetchMercadoPagoPayment(string $paymentId, array $empresa): ?array
    {
        $accessToken = decrypt($empresa['pix_access_token']);
        
        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->get("https://api.mercadopago.com/v1/payments/{$paymentId}", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
            ]);
            
            return json_decode($response->getBody(), true);
            
        } catch (\Throwable $e) {
            log_message('error', "[PIX] Erro ao buscar pagamento MP: " . $e->getMessage());
            return null;
        }
    }
}
```

---

### 📦 Tarefa 3.5: Integrar PIX no Fluxo de Venda (6h)

```php
// app/Controllers/Api/Pos.php (adicionar endpoint generatePix)

/**
 * Gera QR Code PIX para venda
 * POST /api/pos/{id}/pix
 */
public function generatePix(int $id = null)
{
    if (!$id) return $this->failValidationErrors('ID obrigatório');
    
    $sale = $this->model->find($id);
    
    if (!$sale) {
        return $this->failNotFound('Venda não encontrada');
    }
    
    $session = session();
    $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
    
    // Validar que venda não está finalizada
    if ($sale['status'] === 'finalized') {
        return $this->fail('Venda já finalizada', 409);
    }
    
    // Gerar PIX
    $pixService = new \App\Libraries\PixService();
    
    $valor = (float) ($sale['total'] ?? 0);
    $descricao = "PDV - Venda #{$sale['sale_number']}";
    
    $result = $pixService->generate($idEmpresa, $valor, $descricao);
    
    if (!$result['success']) {
        return $this->fail($result['error'], 500);
    }
    
    // Vincular PIX à venda
    $pixTransaction = $result['transaction'];
    
    $pixModel = new \App\Models\PixTransactionModel();
    $pixModel->update($pixTransaction['id_pix_transaction'], [
        'id_pos_sale' => $id,
    ]);
    
    // Atualizar venda para status pending_payment
    $this->model->update($id, [
        'status' => 'pending_payment',
    ]);
    
    return $this->respond([
        'qr_code' => $pixTransaction['qr_code'],
        'qr_code_image' => $pixTransaction['qr_code_image'],
        'txid' => $pixTransaction['txid'],
        'expires_at' => $pixTransaction['expires_at'],
        'valor' => $valor,
    ]);
}

/**
 * Verifica status do pagamento PIX
 * GET /api/pos/{id}/pix/status
 */
public function pixStatus(int $id = null)
{
    if (!$id) return $this->failValidationErrors('ID obrigatório');
    
    $pixModel = new \App\Models\PixTransactionModel();
    
    $pixTransaction = $pixModel->where('id_pos_sale', $id)
                               ->orderBy('created_at', 'DESC')
                               ->first();
    
    if (!$pixTransaction) {
        return $this->failNotFound('Transação PIX não encontrada');
    }
    
    return $this->respond([
        'status' => $pixTransaction['status'],
        'confirmed_at' => $pixTransaction['confirmed_at'],
        'e2e_id' => $pixTransaction['e2e_id'],
    ]);
}
```

---

### 📦 Tarefa 3.6: Cron Job para Expirar QR Codes (1h)

```php
// app/Commands/ExpirePixQrCodes.php
<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use App\Libraries\PixService;

class ExpirePixQrCodes extends BaseCommand
{
    protected $group = 'pix';
    protected $name = 'pix:expire';
    protected $description = 'Expira QR Codes PIX antigos';
    
    public function run(array $params)
    {
        $pixService = new PixService();
        $count = $pixService->expireOld();
        
        $this->write("✅ {$count} QR Codes expirados", 'green');
    }
}
```

**Crontab:**
```bash
*/1 * * * * cd /var/www/html && php spark pix:expire >> /dev/null 2>&1
```

---

### 📦 Tarefa 3.7: Testes (2h)

```php
// tests/Libraries/PixServiceTest.php
<?php
namespace Tests\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\PixService;

class PixServiceTest extends CIUnitTestCase
{
    protected PixService $pixService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->pixService = new PixService();
    }
    
    public function testGenerateQrCode()
    {
        $result = $this->pixService->generate(
            idEmpresa: 1,
            valor: 100.00,
            descricao: 'Teste PHPUnit'
        );
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction', $result);
        $this->assertNotEmpty($result['transaction']['qr_code']);
        $this->assertNotEmpty($result['transaction']['txid']);
    }
    
    public function testConfirmPayment()
    {
        // Gerar QR Code
        $gen = $this->pixService->generate(1, 50.00, 'Teste');
        $txid = $gen['transaction']['txid'];
        
        // Simular confirmação
        $confirm = $this->pixService->confirm($txid, 'E2E123456789');
        
        $this->assertTrue($confirm['success']);
        
        // Verificar status
        $tx = $this->pixService->getByTxid($txid);
        $this->assertEquals('confirmed', $tx['status']);
    }
}
```

---

## ✅ CONCLUSÃO DO ROADMAP

### Sprints Implementados
- ✅ **SPRINT 1:** Integração TEF (40h)
- ✅ **SPRINT 2:** Múltiplas Formas de Pagamento (16h)
- ✅ **SPRINT 3:** PIX com QR Code e Webhook (32h)

**Total:** 88 horas (2 semanas com 2 devs plenos)

### Próximos Passos (SPRINT 4-6)
- Sangria e Suprimento (12h)
- Suspensão/Retomada de Vendas (8h)
- Descontos com Validação (10h)
- Sistema Offline Completo (24h)
- Refatoração e Testes (40h)

---

**Documentação gerada em:** 01/10/2025  
**Autor:** AI Assistant  
**Sistema:** xFiscal ERP - PDV Multi-Tenant SaaS

