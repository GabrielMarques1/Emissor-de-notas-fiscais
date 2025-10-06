<?php

namespace App\Libraries;

use Exception;
use Config\Backup;

/**
 * Biblioteca de Storage Remoto para Backups
 * 
 * Suporta FTP, SFTP e S3-compatible storage
 */
class RemoteStorage
{
    /**
     * Configuração de backup
     */
    protected Backup $config;
    
    /**
     * Tipo de storage ativo
     */
    protected string $storageType;
    
    /**
     * Configuração do storage ativo
     */
    protected array $storageConfig;
    
    /**
     * Conexão ativa
     */
    protected $connection = null;
    
    public function __construct()
    {
        $this->config = new Backup();
        $this->storageType = $this->config->remoteStorage['type'];
        $this->storageConfig = $this->config->getRemoteStorageConfig();
    }
    
    /**
     * Conectar ao storage remoto
     */
    public function connect(): bool
    {
        if ($this->connection) {
            return true;
        }
        
        switch ($this->storageType) {
            case 'ftp':
                return $this->connectFTP();
            case 'sftp':
                return $this->connectSFTP();
            case 's3':
                return $this->connectS3();
            default:
                throw new Exception("Tipo de storage não suportado: {$this->storageType}");
        }
    }
    
    /**
     * Fazer upload de arquivo
     */
    public function upload(string $localFile, string $remotePath): bool
    {
        if (!$this->connect()) {
            throw new Exception("Não foi possível conectar ao storage remoto");
        }
        
        if (!file_exists($localFile)) {
            throw new Exception("Arquivo local não encontrado: {$localFile}");
        }
        
        $attempts = 0;
        $maxAttempts = $this->config->remoteStorage['retry_attempts'];
        
        while ($attempts < $maxAttempts) {
            try {
                switch ($this->storageType) {
                    case 'ftp':
                        return $this->uploadFTP($localFile, $remotePath);
                    case 'sftp':
                        return $this->uploadSFTP($localFile, $remotePath);
                    case 's3':
                        return $this->uploadS3($localFile, $remotePath);
                }
            } catch (Exception $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                
                // Aguardar antes de tentar novamente
                sleep(pow(2, $attempts)); // Backoff exponencial
            }
        }
        
        return false;
    }
    
    /**
     * Fazer download de arquivo
     */
    public function download(string $remotePath, string $localFile): bool
    {
        if (!$this->connect()) {
            throw new Exception("Não foi possível conectar ao storage remoto");
        }
        
        switch ($this->storageType) {
            case 'ftp':
                return $this->downloadFTP($remotePath, $localFile);
            case 'sftp':
                return $this->downloadSFTP($remotePath, $localFile);
            case 's3':
                return $this->downloadS3($remotePath, $localFile);
        }
        
        return false;
    }
    
    /**
     * Listar arquivos remotos
     */
    public function listFiles(string $remotePath = ''): array
    {
        if (!$this->connect()) {
            throw new Exception("Não foi possível conectar ao storage remoto");
        }
        
        switch ($this->storageType) {
            case 'ftp':
                return $this->listFilesFTP($remotePath);
            case 'sftp':
                return $this->listFilesSFTP($remotePath);
            case 's3':
                return $this->listFilesS3($remotePath);
        }
        
        return [];
    }
    
    /**
     * Deletar arquivo remoto
     */
    public function delete(string $remotePath): bool
    {
        if (!$this->connect()) {
            throw new Exception("Não foi possível conectar ao storage remoto");
        }
        
        switch ($this->storageType) {
            case 'ftp':
                return $this->deleteFTP($remotePath);
            case 'sftp':
                return $this->deleteSFTP($remotePath);
            case 's3':
                return $this->deleteS3($remotePath);
        }
        
        return false;
    }
    
    /**
     * Verificar se arquivo existe remotamente
     */
    public function exists(string $remotePath): bool
    {
        if (!$this->connect()) {
            return false;
        }
        
        switch ($this->storageType) {
            case 'ftp':
                return $this->existsFTP($remotePath);
            case 'sftp':
                return $this->existsSFTP($remotePath);
            case 's3':
                return $this->existsS3($remotePath);
        }
        
        return false;
    }
    
    /**
     * Obter tamanho do arquivo remoto
     */
    public function getSize(string $remotePath): int
    {
        if (!$this->connect()) {
            return 0;
        }
        
        switch ($this->storageType) {
            case 'ftp':
                return $this->getSizeFTP($remotePath);
            case 'sftp':
                return $this->getSizeSFTP($remotePath);
            case 's3':
                return $this->getSizeS3($remotePath);
        }
        
        return 0;
    }
    
    /**
     * Fechar conexão
     */
    public function disconnect(): void
    {
        if (!$this->connection) {
            return;
        }
        
        switch ($this->storageType) {
            case 'ftp':
                ftp_close($this->connection);
                break;
            case 'sftp':
                // SFTP connection cleanup
                break;
            case 's3':
                // S3 connection cleanup
                break;
        }
        
        $this->connection = null;
    }
    
    // ========================================
    // MÉTODOS FTP
    // ========================================
    
    protected function connectFTP(): bool
    {
        $config = $this->storageConfig;
        
        $this->connection = ftp_connect($config['host'], $config['port'], 30);
        
        if (!$this->connection) {
            throw new Exception("Não foi possível conectar ao servidor FTP");
        }
        
        if (!ftp_login($this->connection, $config['username'], $config['password'])) {
            throw new Exception("Falha na autenticação FTP");
        }
        
        if ($config['passive']) {
            ftp_pasv($this->connection, true);
        }
        
        if ($config['ssl'] && function_exists('ftp_ssl_connect')) {
            // Upgrade para SSL se disponível
        }
        
        return true;
    }
    
    protected function uploadFTP(string $localFile, string $remotePath): bool
    {
        $remoteDir = dirname($remotePath);
        $this->createDirectoryFTP($remoteDir);
        
        return ftp_put($this->connection, $remotePath, $localFile, FTP_BINARY);
    }
    
    protected function downloadFTP(string $remotePath, string $localFile): bool
    {
        // Criar diretório local se não existir
        $localDir = dirname($localFile);
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        
        return ftp_get($this->connection, $localFile, $remotePath, FTP_BINARY);
    }
    
    protected function listFilesFTP(string $remotePath): array
    {
        $files = ftp_nlist($this->connection, $remotePath ?: $this->storageConfig['path']);
        return $files ?: [];
    }
    
    protected function deleteFTP(string $remotePath): bool
    {
        return ftp_delete($this->connection, $remotePath);
    }
    
    protected function existsFTP(string $remotePath): bool
    {
        $size = ftp_size($this->connection, $remotePath);
        return $size !== -1;
    }
    
    protected function getSizeFTP(string $remotePath): int
    {
        $size = ftp_size($this->connection, $remotePath);
        return $size !== -1 ? $size : 0;
    }
    
    protected function createDirectoryFTP(string $path): void
    {
        $parts = explode('/', trim($path, '/'));
        $currentPath = $this->storageConfig['path'];
        
        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            $currentPath .= '/' . $part;
            
            // Tentar criar diretório (ignorar se já existe)
            @ftp_mkdir($this->connection, $currentPath);
        }
    }
    
    // ========================================
    // MÉTODOS SFTP (usando SSH2)
    // ========================================
    
    protected function connectSFTP(): bool
    {
        if (!extension_loaded('ssh2')) {
            throw new Exception("Extensão SSH2 não está instalada");
        }
        
        $config = $this->storageConfig;
        
        $this->connection = ssh2_connect($config['host'], $config['port']);
        
        if (!$this->connection) {
            throw new Exception("Não foi possível conectar ao servidor SFTP");
        }
        
        // Autenticação
        if (!empty($config['private_key'])) {
            // Autenticação por chave privada
            if (!ssh2_auth_pubkey_file($this->connection, $config['username'], 
                                       $config['public_key'], $config['private_key'])) {
                throw new Exception("Falha na autenticação SFTP com chave privada");
            }
        } else {
            // Autenticação por senha
            if (!ssh2_auth_password($this->connection, $config['username'], $config['password'])) {
                throw new Exception("Falha na autenticação SFTP com senha");
            }
        }
        
        return true;
    }
    
    protected function uploadSFTP(string $localFile, string $remotePath): bool
    {
        $sftp = ssh2_sftp($this->connection);
        
        $remoteDir = dirname($remotePath);
        $this->createDirectorySFTP($remoteDir);
        
        return ssh2_scp_send($this->connection, $localFile, $remotePath);
    }
    
    protected function downloadSFTP(string $remotePath, string $localFile): bool
    {
        $localDir = dirname($localFile);
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        
        return ssh2_scp_recv($this->connection, $remotePath, $localFile);
    }
    
    protected function listFilesSFTP(string $remotePath): array
    {
        $sftp = ssh2_sftp($this->connection);
        $handle = opendir("ssh2.sftp://{$sftp}" . ($remotePath ?: $this->storageConfig['path']));
        
        $files = [];
        while (($file = readdir($handle)) !== false) {
            if ($file !== '.' && $file !== '..') {
                $files[] = $file;
            }
        }
        
        closedir($handle);
        return $files;
    }
    
    protected function deleteSFTP(string $remotePath): bool
    {
        $sftp = ssh2_sftp($this->connection);
        return unlink("ssh2.sftp://{$sftp}" . $remotePath);
    }
    
    protected function existsSFTP(string $remotePath): bool
    {
        $sftp = ssh2_sftp($this->connection);
        return file_exists("ssh2.sftp://{$sftp}" . $remotePath);
    }
    
    protected function getSizeSFTP(string $remotePath): int
    {
        $sftp = ssh2_sftp($this->connection);
        $size = filesize("ssh2.sftp://{$sftp}" . $remotePath);
        return $size ?: 0;
    }
    
    protected function createDirectorySFTP(string $path): void
    {
        $sftp = ssh2_sftp($this->connection);
        $parts = explode('/', trim($path, '/'));
        $currentPath = $this->storageConfig['path'];
        
        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            $currentPath .= '/' . $part;
            
            if (!file_exists("ssh2.sftp://{$sftp}" . $currentPath)) {
                ssh2_sftp_mkdir($sftp, $currentPath, 0755, true);
            }
        }
    }
    
    // ========================================
    // MÉTODOS S3 (simulação básica)
    // ========================================
    
    protected function connectS3(): bool
    {
        // Para implementação completa, usar AWS SDK ou biblioteca S3
        // Aqui é uma simulação básica usando cURL
        
        $config = $this->storageConfig;
        
        // Verificar se credenciais estão configuradas
        if (empty($config['access_key']) || empty($config['secret_key'])) {
            throw new Exception("Credenciais S3 não configuradas");
        }
        
        $this->connection = [
            'endpoint' => $config['endpoint'],
            'region' => $config['region'],
            'bucket' => $config['bucket'],
            'access_key' => $config['access_key'],
            'secret_key' => $config['secret_key'],
            'path_prefix' => $config['path_prefix']
        ];
        
        return true;
    }
    
    protected function uploadS3(string $localFile, string $remotePath): bool
    {
        // Implementação básica usando cURL
        // Para produção, usar AWS SDK
        
        $config = $this->connection;
        $fullPath = $config['path_prefix'] . $remotePath;
        
        $url = $config['endpoint'] . '/' . $config['bucket'] . '/' . $fullPath;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => fopen($localFile, 'rb'),
            CURLOPT_INFILESIZE => filesize($localFile),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->remoteStorage['timeout'],
            CURLOPT_HTTPHEADER => $this->getS3Headers('PUT', $fullPath, filesize($localFile))
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    protected function downloadS3(string $remotePath, string $localFile): bool
    {
        $config = $this->connection;
        $fullPath = $config['path_prefix'] . $remotePath;
        
        $url = $config['endpoint'] . '/' . $config['bucket'] . '/' . $fullPath;
        
        $localDir = dirname($localFile);
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->remoteStorage['timeout'],
            CURLOPT_HTTPHEADER => $this->getS3Headers('GET', $fullPath)
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return file_put_contents($localFile, $result) !== false;
        }
        
        return false;
    }
    
    protected function listFilesS3(string $remotePath): array
    {
        // Implementação básica de listagem S3
        // Para produção, usar AWS SDK
        return [];
    }
    
    protected function deleteS3(string $remotePath): bool
    {
        $config = $this->connection;
        $fullPath = $config['path_prefix'] . $remotePath;
        
        $url = $config['endpoint'] . '/' . $config['bucket'] . '/' . $fullPath;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->remoteStorage['timeout'],
            CURLOPT_HTTPHEADER => $this->getS3Headers('DELETE', $fullPath)
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    protected function existsS3(string $remotePath): bool
    {
        $config = $this->connection;
        $fullPath = $config['path_prefix'] . $remotePath;
        
        $url = $config['endpoint'] . '/' . $config['bucket'] . '/' . $fullPath;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $this->getS3Headers('HEAD', $fullPath)
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    protected function getSizeS3(string $remotePath): int
    {
        $config = $this->connection;
        $fullPath = $config['path_prefix'] . $remotePath;
        
        $url = $config['endpoint'] . '/' . $config['bucket'] . '/' . $fullPath;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $this->getS3Headers('HEAD', $fullPath)
        ]);
        
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        return $size > 0 ? (int)$size : 0;
    }
    
    /**
     * Gerar headers de autenticação S3
     */
    protected function getS3Headers(string $method, string $path, int $contentLength = 0): array
    {
        $config = $this->connection;
        $date = gmdate('D, d M Y H:i:s T');
        $contentType = 'application/octet-stream';
        
        // Criar string para assinatura
        $stringToSign = $method . "\n\n" . $contentType . "\n" . $date . "\n/" . $config['bucket'] . "/" . $path;
        
        // Gerar assinatura
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $config['secret_key'], true));
        
        $headers = [
            'Date: ' . $date,
            'Authorization: AWS ' . $config['access_key'] . ':' . $signature,
            'Content-Type: ' . $contentType
        ];
        
        if ($contentLength > 0) {
            $headers[] = 'Content-Length: ' . $contentLength;
        }
        
        return $headers;
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
