<?php

namespace App\Libraries\TefAdapters;

interface AcquirerAdapterInterface
{
    /**
     * Nome da adquirente
     */
    public function getName(): string;
    
    /**
     * Autorizar transação
     * 
     * @param array $data [
     *   'amount' => 100.00,
     *   'card_type' => 'credit|debit',
     *   'installments' => 1,
     *   'card_data' => [
     *     'number' => '4111111111111111',
     *     'holder' => 'NOME TITULAR',
     *     'expiry' => '12/2030',
     *     'cvv' => '123',
     *   ],
     * ]
     * @return array ['success' => bool, 'authorization_code' => string, 'nsu' => string, ...]
     */
    public function authorize(array $data): array;
    
    /**
     * Confirmar transação (captura)
     * 
     * @param string $authorizationCode
     * @param float $amount
     * @return array ['success' => bool, ...]
     */
    public function confirm(string $authorizationCode, float $amount): array;
    
    /**
     * Cancelar/Estornar transação
     * 
     * @param string $authorizationCode
     * @param float $amount
     * @return array ['success' => bool, ...]
     */
    public function cancel(string $authorizationCode, float $amount): array;
    
    /**
     * Consultar status da transação
     * 
     * @param string $authorizationCode
     * @return array ['success' => bool, 'status' => string, ...]
     */
    public function checkStatus(string $authorizationCode): array;
    
    /**
     * Configurar credenciais
     * 
     * @param array $config ['merchant_id' => '...', 'merchant_key' => '...', 'environment' => 'sandbox|production']
     */
    public function configure(array $config): void;
}

