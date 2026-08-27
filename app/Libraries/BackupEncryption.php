<?php

namespace App\Libraries;

use Exception;

/**
 * Biblioteca de Criptografia para Backups
 * 
 * Implementa criptografia AES-256-CBC para proteger backups de tenants
 * com chaves únicas por tenant e armazenamento seguro
 */
class BackupEncryption
{
    /**
     * Algoritmo de criptografia
     */
    const CIPHER_METHOD = 'aes-256-cbc';
    
    /**
     * Tamanho da chave em bytes (256 bits = 32 bytes)
     */
    const KEY_LENGTH = 32;
    
    /**
     * Diretório das chaves de criptografia
     */
    protected string $keysDirectory;
    
    /**
     * Cache de chaves em memória
     */
    protected array $keyCache = [];
    
    public function __construct()
    {
        $this->keysDirectory = WRITEPATH . 'backups/keys/';
        
        // Criar diretório de chaves se não existir
        if (!is_dir($this->keysDirectory)) {
            if (!mkdir($this->keysDirectory, 0700, true)) {
                throw new Exception("Não foi possível criar diretório de chaves: {$this->keysDirectory}");
            }
        }
        
        // Proteger diretório com .htaccess
        $this->protectKeysDirectory();
    }
    
    /**
     * Gerar chave única para um tenant
     */
    public function generateTenantKey(string $tenantId): string
    {
        $key = random_bytes(self::KEY_LENGTH);
        $keyFile = $this->getKeyFilePath($tenantId);
        
        // Salvar chave com permissões restritivas
        if (file_put_contents($keyFile, base64_encode($key), LOCK_EX) === false) {
            throw new Exception("Erro ao salvar chave para tenant {$tenantId}");
        }
        
        // Definir permissões restritivas (apenas owner pode ler)
        chmod($keyFile, 0600);
        
        // Cache da chave
        $this->keyCache[$tenantId] = $key;
        
        return base64_encode($key);
    }
    
    /**
     * Obter chave do tenant
     */
    public function getTenantKey(string $tenantId): string
    {
        // Verificar cache primeiro
        if (isset($this->keyCache[$tenantId])) {
            return $this->keyCache[$tenantId];
        }
        
        $keyFile = $this->getKeyFilePath($tenantId);
        
        if (!file_exists($keyFile)) {
            throw new Exception("Chave não encontrada para tenant {$tenantId}");
        }
        
        $encodedKey = file_get_contents($keyFile);
        if ($encodedKey === false) {
            throw new Exception("Erro ao ler chave para tenant {$tenantId}");
        }
        
        $key = base64_decode($encodedKey);
        if ($key === false || strlen($key) !== self::KEY_LENGTH) {
            throw new Exception("Chave inválida para tenant {$tenantId}");
        }
        
        // Cache da chave
        $this->keyCache[$tenantId] = $key;
        
        return $key;
    }
    
    /**
     * Criptografar arquivo
     */
    public function encryptFile(string $inputFile, string $outputFile, string $tenantId): array
    {
        if (!file_exists($inputFile)) {
            throw new Exception("Arquivo de entrada não encontrado: {$inputFile}");
        }
        
        $key = $this->getTenantKey($tenantId);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        
        // Ler arquivo em chunks para economizar memória
        $inputHandle = fopen($inputFile, 'rb');
        $outputHandle = fopen($outputFile, 'wb');
        
        if (!$inputHandle || !$outputHandle) {
            throw new Exception("Erro ao abrir arquivos para criptografia");
        }
        
        // Escrever IV no início do arquivo
        fwrite($outputHandle, base64_encode($iv) . '::');
        
        $chunkSize = 8192; // 8KB chunks
        $totalSize = 0;
        $startTime = microtime(true);
        
        while (!feof($inputHandle)) {
            $chunk = fread($inputHandle, $chunkSize);
            if ($chunk === false) break;
            
            $encryptedChunk = openssl_encrypt($chunk, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputHandle, base64_encode($encryptedChunk) . '|');
            
            $totalSize += strlen($chunk);
            
            // Atualizar IV para próximo chunk (CBC chaining)
            $iv = substr($encryptedChunk, -openssl_cipher_iv_length(self::CIPHER_METHOD));
        }
        
        fclose($inputHandle);
        fclose($outputHandle);
        
        $duration = microtime(true) - $startTime;
        
        // Calcular checksum do arquivo criptografado
        $checksum = hash_file('sha256', $outputFile);
        
        return [
            'original_size' => $totalSize,
            'encrypted_size' => filesize($outputFile),
            'checksum_sha256' => $checksum,
            'encryption_time' => round($duration, 3),
            'algorithm' => self::CIPHER_METHOD
        ];
    }
    
    /**
     * Descriptografar arquivo
     */
    public function decryptFile(string $inputFile, string $outputFile, string $tenantId): array
    {
        if (!file_exists($inputFile)) {
            throw new Exception("Arquivo criptografado não encontrado: {$inputFile}");
        }
        
        $key = $this->getTenantKey($tenantId);
        
        $inputHandle = fopen($inputFile, 'rb');
        $outputHandle = fopen($outputFile, 'wb');
        
        if (!$inputHandle || !$outputHandle) {
            throw new Exception("Erro ao abrir arquivos para descriptografia");
        }
        
        $startTime = microtime(true);
        $totalSize = 0;
        
        // Ler IV do início do arquivo
        $ivLine = '';
        while (($char = fgetc($inputHandle)) !== false) {
            if ($char === ':' && substr($ivLine, -1) === ':') {
                break;
            }
            $ivLine .= $char;
        }
        
        $iv = base64_decode(rtrim($ivLine, ':'));
        
        // Descriptografar chunks
        $buffer = '';
        while (!feof($inputHandle)) {
            $char = fgetc($inputHandle);
            if ($char === '|' || feof($inputHandle)) {
                if (!empty($buffer)) {
                    $encryptedChunk = base64_decode($buffer);
                    $decryptedChunk = openssl_decrypt($encryptedChunk, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
                    
                    if ($decryptedChunk === false) {
                        throw new Exception("Erro na descriptografia - chave ou dados corrompidos");
                    }
                    
                    fwrite($outputHandle, $decryptedChunk);
                    $totalSize += strlen($decryptedChunk);
                    
                    // Atualizar IV
                    $iv = substr($encryptedChunk, -openssl_cipher_iv_length(self::CIPHER_METHOD));
                    $buffer = '';
                }
            } else {
                $buffer .= $char;
            }
        }
        
        fclose($inputHandle);
        fclose($outputHandle);
        
        $duration = microtime(true) - $startTime;
        
        return [
            'decrypted_size' => $totalSize,
            'decryption_time' => round($duration, 3),
            'checksum_sha256' => hash_file('sha256', $outputFile)
        ];
    }
    
    /**
     * Criptografar string diretamente
     */
    public function encryptString(string $data, string $tenantId): string
    {
        $key = $this->getTenantKey($tenantId);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        
        $encrypted = openssl_encrypt($data, self::CIPHER_METHOD, $key, 0, $iv);
        
        return base64_encode($iv) . '::' . $encrypted;
    }
    
    /**
     * Descriptografar string
     */
    public function decryptString(string $encryptedData, string $tenantId): string
    {
        $key = $this->getTenantKey($tenantId);
        
        list($iv, $encrypted) = explode('::', $encryptedData, 2);
        $iv = base64_decode($iv);
        
        $decrypted = openssl_decrypt($encrypted, self::CIPHER_METHOD, $key, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception("Erro na descriptografia da string");
        }
        
        return $decrypted;
    }
    
    /**
     * Verificar integridade da chave
     */
    public function verifyTenantKey(string $tenantId): bool
    {
        try {
            $key = $this->getTenantKey($tenantId);
            
            // Teste de criptografia/descriptografia
            $testData = 'TESTE_INTEGRIDADE_' . time();
            $encrypted = $this->encryptString($testData, $tenantId);
            $decrypted = $this->decryptString($encrypted, $tenantId);
            
            return $decrypted === $testData;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Rotacionar chave do tenant (gerar nova)
     */
    public function rotateTenantKey(string $tenantId): string
    {
        // Backup da chave antiga
        $oldKeyFile = $this->getKeyFilePath($tenantId);
        if (file_exists($oldKeyFile)) {
            $backupFile = $oldKeyFile . '.backup.' . date('Y-m-d-H-i-s');
            copy($oldKeyFile, $backupFile);
        }
        
        // Limpar cache
        unset($this->keyCache[$tenantId]);
        
        // Gerar nova chave
        return $this->generateTenantKey($tenantId);
    }
    
    /**
     * Listar tenants com chaves
     */
    public function listTenantKeys(): array
    {
        $keys = [];
        $files = glob($this->keysDirectory . 'tenant_*.key');
        
        foreach ($files as $file) {
            $filename = basename($file, '.key');
            $tenantId = str_replace('tenant_', '', $filename);
            
            $keys[] = [
                'tenant_id' => $tenantId,
                'key_file' => $file,
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => filesize($file),
                'valid' => $this->verifyTenantKey($tenantId)
            ];
        }
        
        return $keys;
    }
    
    /**
     * Obter caminho do arquivo de chave
     */
    protected function getKeyFilePath(string $tenantId): string
    {
        // Sanitizar tenant ID para nome de arquivo seguro
        $safeTenantId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantId);
        return $this->keysDirectory . "tenant_{$safeTenantId}.key";
    }
    
    /**
     * Proteger diretório de chaves
     */
    protected function protectKeysDirectory(): void
    {
        $htaccessFile = $this->keysDirectory . '.htaccess';
        
        if (!file_exists($htaccessFile)) {
            $htaccessContent = <<<EOT
# Proteção do diretório de chaves de backup
Order Deny,Allow
Deny from all

# Bloquear acesso a todos os arquivos
<Files "*">
    Order Deny,Allow
    Deny from all
</Files>

# Bloquear listagem de diretório
Options -Indexes

# Bloquear execução de scripts
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
EOT;
            
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }
    
    /**
     * Limpar cache de chaves (segurança)
     */
    public function clearKeyCache(): void
    {
        // Sobrescrever chaves na memória antes de limpar
        foreach ($this->keyCache as $tenantId => $key) {
            $this->keyCache[$tenantId] = str_repeat("\0", strlen($key));
        }
        
        $this->keyCache = [];
    }
    
    /**
     * Destructor - limpar chaves da memória
     */
    public function __destruct()
    {
        $this->clearKeyCache();
    }
}
