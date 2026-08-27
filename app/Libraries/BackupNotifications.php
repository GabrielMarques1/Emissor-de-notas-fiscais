<?php

namespace App\Libraries;

use Config\Backup;
use Exception;

/**
 * Sistema de Notificações para Backups
 * 
 * Envia notificações por email, webhook e outros canais
 */
class BackupNotifications
{
    /**
     * Configuração de backup
     */
    protected Backup $config;
    
    /**
     * Canais de notificação ativos
     */
    protected array $activeChannels = [];
    
    public function __construct()
    {
        $this->config = new Backup();
        $this->initializeChannels();
    }
    
    /**
     * Inicializar canais de notificação
     */
    protected function initializeChannels(): void
    {
        if (!$this->config->notifications['enabled']) {
            return;
        }
        
        foreach ($this->config->notifications['channels'] as $channel) {
            if ($this->config->notifications[$channel]['enabled'] ?? false) {
                $this->activeChannels[] = $channel;
            }
        }
    }
    
    /**
     * Enviar notificação de backup bem-sucedido
     */
    public function notifyBackupSuccess(string $tenantId, array $backupInfo): void
    {
        if (!$this->config->shouldNotify('backup_success')) {
            return;
        }
        
        $subject = "✅ Backup Concluído - Tenant {$tenantId}";
        $message = $this->buildBackupSuccessMessage($tenantId, $backupInfo);
        
        $this->sendNotification($subject, $message, 'success', [
            'tenant_id' => $tenantId,
            'backup_info' => $backupInfo
        ]);
    }
    
    /**
     * Enviar notificação de falha no backup
     */
    public function notifyBackupFailure(string $tenantId, string $error, array $context = []): void
    {
        if (!$this->config->shouldNotify('backup_failed')) {
            return;
        }
        
        $subject = "🚨 Falha no Backup - Tenant {$tenantId}";
        $message = $this->buildBackupFailureMessage($tenantId, $error, $context);
        
        $this->sendNotification($subject, $message, 'error', [
            'tenant_id' => $tenantId,
            'error' => $error,
            'context' => $context
        ]);
    }
    
    /**
     * Enviar notificação de restore concluído
     */
    public function notifyRestoreCompleted(string $tenantId, array $restoreInfo): void
    {
        if (!$this->config->shouldNotify('restore_completed')) {
            return;
        }
        
        $subject = "🔄 Restore Concluído - Tenant {$tenantId}";
        $message = $this->buildRestoreCompletedMessage($tenantId, $restoreInfo);
        
        $this->sendNotification($subject, $message, 'info', [
            'tenant_id' => $tenantId,
            'restore_info' => $restoreInfo
        ]);
    }
    
    /**
     * Enviar notificação de falha no restore
     */
    public function notifyRestoreFailure(string $tenantId, string $error): void
    {
        if (!$this->config->shouldNotify('restore_failed')) {
            return;
        }
        
        $subject = "❌ Falha no Restore - Tenant {$tenantId}";
        $message = $this->buildRestoreFailureMessage($tenantId, $error);
        
        $this->sendNotification($subject, $message, 'error', [
            'tenant_id' => $tenantId,
            'error' => $error
        ]);
    }
    
    /**
     * Enviar notificação de falha no teste de restore
     */
    public function notifyTestRestoreFailure(array $failedTests): void
    {
        if (!$this->config->shouldNotify('test_restore_failed')) {
            return;
        }
        
        $subject = "🧪 Falha no Teste de Restore - " . count($failedTests) . " tenant(s)";
        $message = $this->buildTestRestoreFailureMessage($failedTests);
        
        $this->sendNotification($subject, $message, 'warning', [
            'failed_tests' => $failedTests
        ]);
    }
    
    /**
     * Enviar notificação de espaço em disco baixo
     */
    public function notifyDiskSpaceLow(array $diskInfo): void
    {
        if (!$this->config->shouldNotify('disk_space_low')) {
            return;
        }
        
        $subject = "⚠️ Espaço em Disco Baixo - Sistema de Backup";
        $message = $this->buildDiskSpaceLowMessage($diskInfo);
        
        $this->sendNotification($subject, $message, 'warning', [
            'disk_info' => $diskInfo
        ]);
    }
    
    /**
     * Enviar notificação de falha no upload
     */
    public function notifyUploadFailure(string $tenantId, string $error, array $uploadInfo): void
    {
        if (!$this->config->shouldNotify('upload_failed')) {
            return;
        }
        
        $subject = "☁️ Falha no Upload - Tenant {$tenantId}";
        $message = $this->buildUploadFailureMessage($tenantId, $error, $uploadInfo);
        
        $this->sendNotification($subject, $message, 'error', [
            'tenant_id' => $tenantId,
            'error' => $error,
            'upload_info' => $uploadInfo
        ]);
    }
    
    /**
     * Enviar notificação de limpeza concluída
     */
    public function notifyCleanupCompleted(array $cleanupStats): void
    {
        if (!$this->config->shouldNotify('cleanup_completed')) {
            return;
        }
        
        $subject = "🧹 Limpeza de Backups Concluída";
        $message = $this->buildCleanupCompletedMessage($cleanupStats);
        
        $this->sendNotification($subject, $message, 'info', [
            'cleanup_stats' => $cleanupStats
        ]);
    }
    
    /**
     * Enviar notificação personalizada
     */
    public function sendCustomNotification(string $subject, string $message, string $type = 'info', array $data = []): void
    {
        $this->sendNotification($subject, $message, $type, $data);
    }
    
    /**
     * Enviar notificação para todos os canais ativos
     */
    protected function sendNotification(string $subject, string $message, string $type, array $data): void
    {
        foreach ($this->activeChannels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        $this->sendEmailNotification($subject, $message, $type, $data);
                        break;
                    case 'webhook':
                        $this->sendWebhookNotification($subject, $message, $type, $data);
                        break;
                    case 'slack':
                        $this->sendSlackNotification($subject, $message, $type, $data);
                        break;
                }
            } catch (Exception $e) {
                // Log erro mas não interromper outros canais
                $this->logNotificationError($channel, $e->getMessage());
            }
        }
    }
    
    /**
     * Enviar notificação por email
     */
    protected function sendEmailNotification(string $subject, string $message, string $type, array $data): void
    {
        $config = $this->config->notifications['email'];
        
        if (!$config['enabled']) {
            return;
        }
        
        $email = \Config\Services::email();
        
        $email->setFrom($config['from'], 'Sistema de Backup');
        
        // Adicionar destinatários administrativos
        foreach ($config['admin_emails'] as $adminEmail) {
            $email->setTo($adminEmail);
        }
        
        // Adicionar tenant se aplicável e configurado
        if ($config['tenant_notification'] && isset($data['tenant_id'])) {
            $tenantEmail = $this->getTenantEmail($data['tenant_id']);
            if ($tenantEmail) {
                $email->setBCC($tenantEmail);
            }
        }
        
        $email->setSubject($subject);
        
        // Construir corpo do email em HTML
        $htmlMessage = $this->buildHtmlEmailBody($subject, $message, $type, $data);
        $email->setMessage($htmlMessage);
        
        if (!$email->send()) {
            throw new Exception("Falha ao enviar email: " . $email->printDebugger());
        }
    }
    
    /**
     * Enviar notificação via webhook
     */
    protected function sendWebhookNotification(string $subject, string $message, string $type, array $data): void
    {
        $config = $this->config->notifications['webhook'];
        
        if (!$config['enabled'] || empty($config['url'])) {
            return;
        }
        
        $payload = [
            'timestamp' => date('c'),
            'subject' => $subject,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'source' => 'backup_system'
        ];
        
        // Adicionar assinatura se configurada
        if (!empty($config['secret'])) {
            $payload['signature'] = hash_hmac('sha256', json_encode($payload), $config['secret']);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['url'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: BackupSystem/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL no webhook: {$error}");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Webhook retornou código {$httpCode}: {$response}");
        }
    }
    
    /**
     * Enviar notificação para Slack
     */
    protected function sendSlackNotification(string $subject, string $message, string $type, array $data): void
    {
        $config = $this->config->notifications['slack'];
        
        if (!$config['enabled'] || empty($config['webhook_url'])) {
            return;
        }
        
        // Mapear tipo para cor do Slack
        $colors = [
            'success' => 'good',
            'warning' => 'warning',
            'error' => 'danger',
            'info' => '#36a64f'
        ];
        
        $color = $colors[$type] ?? '#36a64f';
        
        // Construir payload do Slack
        $payload = [
            'channel' => $config['channel'],
            'username' => $config['username'],
            'icon_emoji' => $this->getSlackIcon($type),
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $subject,
                    'text' => $message,
                    'timestamp' => time(),
                    'footer' => 'Sistema de Backup',
                    'fields' => $this->buildSlackFields($data)
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['webhook_url'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Slack webhook retornou código {$httpCode}: {$response}");
        }
    }
    
    /**
     * Construir mensagem de backup bem-sucedido
     */
    protected function buildBackupSuccessMessage(string $tenantId, array $backupInfo): string
    {
        $message = "Backup do tenant {$tenantId} concluído com sucesso!\n\n";
        $message .= "📊 Detalhes:\n";
        $message .= "• Tipo: " . ($backupInfo['type'] ?? 'N/A') . "\n";
        $message .= "• Tabelas: " . ($backupInfo['tables_count'] ?? 'N/A') . "\n";
        $message .= "• Registros: " . ($backupInfo['rows_count'] ?? 'N/A') . "\n";
        $message .= "• Arquivos: " . ($backupInfo['files_count'] ?? 'N/A') . "\n";
        $message .= "• Tamanho: " . $this->formatFileSize($backupInfo['size'] ?? 0) . "\n";
        $message .= "• Duração: " . round($backupInfo['duration'] ?? 0, 2) . "s\n";
        
        if (isset($backupInfo['uploaded']) && $backupInfo['uploaded']) {
            $message .= "• Status: ✅ Backup local e remoto\n";
        } else {
            $message .= "• Status: ✅ Backup local\n";
        }
        
        return $message;
    }
    
    /**
     * Construir mensagem de falha no backup
     */
    protected function buildBackupFailureMessage(string $tenantId, string $error, array $context): string
    {
        $message = "❌ Falha no backup do tenant {$tenantId}\n\n";
        $message .= "🚨 Erro: {$error}\n\n";
        
        if (!empty($context)) {
            $message .= "📋 Contexto:\n";
            foreach ($context as $key => $value) {
                $message .= "• {$key}: {$value}\n";
            }
        }
        
        $message .= "\n🔧 Ações recomendadas:\n";
        $message .= "1. Verificar logs detalhados\n";
        $message .= "2. Verificar espaço em disco\n";
        $message .= "3. Tentar backup manual\n";
        $message .= "4. Contatar administrador se persistir\n";
        
        return $message;
    }
    
    /**
     * Construir mensagem de restore concluído
     */
    protected function buildRestoreCompletedMessage(string $tenantId, array $restoreInfo): string
    {
        $message = "Restore do tenant {$tenantId} concluído!\n\n";
        $message .= "📊 Detalhes:\n";
        $message .= "• Backup de: " . ($restoreInfo['backup_date'] ?? 'N/A') . "\n";
        $message .= "• Tabelas restauradas: " . ($restoreInfo['tables_count'] ?? 'N/A') . "\n";
        $message .= "• Registros restaurados: " . ($restoreInfo['rows_count'] ?? 'N/A') . "\n";
        $message .= "• Duração: " . round($restoreInfo['duration'] ?? 0, 2) . "s\n";
        
        return $message;
    }
    
    /**
     * Construir mensagem de falha no restore
     */
    protected function buildRestoreFailureMessage(string $tenantId, string $error): string
    {
        $message = "❌ Falha no restore do tenant {$tenantId}\n\n";
        $message .= "🚨 Erro: {$error}\n\n";
        $message .= "⚠️ ATENÇÃO: Dados podem estar em estado inconsistente!\n";
        $message .= "Contate o administrador imediatamente.\n";
        
        return $message;
    }
    
    /**
     * Construir mensagem de falha no teste de restore
     */
    protected function buildTestRestoreFailureMessage(array $failedTests): string
    {
        $message = "🧪 Falha no teste automático de restore!\n\n";
        $message .= "📊 Resumo:\n";
        $message .= "• Testes falharam: " . count($failedTests) . "\n\n";
        
        $message .= "❌ Detalhes das falhas:\n";
        foreach ($failedTests as $test) {
            $message .= "• Tenant {$test['tenant']}: {$test['error']}\n";
        }
        
        $message .= "\n🔧 Ação necessária:\n";
        $message .= "Verificar integridade dos backups listados acima.\n";
        
        return $message;
    }
    
    /**
     * Construir mensagem de espaço em disco baixo
     */
    protected function buildDiskSpaceLowMessage(array $diskInfo): string
    {
        $message = "⚠️ Espaço em disco baixo no sistema de backup!\n\n";
        $message .= "📊 Status do disco:\n";
        $message .= "• Espaço livre: " . $this->formatFileSize($diskInfo['free_space']) . "\n";
        $message .= "• Espaço total: " . $this->formatFileSize($diskInfo['total_space']) . "\n";
        $message .= "• Uso atual: " . round($diskInfo['usage_percent'], 1) . "%\n\n";
        
        $message .= "🔧 Ações recomendadas:\n";
        $message .= "1. Executar limpeza de backups antigos\n";
        $message .= "2. Verificar uploads para storage remoto\n";
        $message .= "3. Considerar aumentar espaço em disco\n";
        
        return $message;
    }
    
    /**
     * Construir mensagem de falha no upload
     */
    protected function buildUploadFailureMessage(string $tenantId, string $error, array $uploadInfo): string
    {
        $message = "☁️ Falha no upload do backup - Tenant {$tenantId}\n\n";
        $message .= "🚨 Erro: {$error}\n\n";
        $message .= "📊 Detalhes:\n";
        $message .= "• Arquivo: " . ($uploadInfo['file'] ?? 'N/A') . "\n";
        $message .= "• Tamanho: " . $this->formatFileSize($uploadInfo['size'] ?? 0) . "\n";
        $message .= "• Destino: " . ($uploadInfo['destination'] ?? 'N/A') . "\n";
        
        return $message;
    }
    
    /**
     * Construir mensagem de limpeza concluída
     */
    protected function buildCleanupCompletedMessage(array $cleanupStats): string
    {
        $message = "🧹 Limpeza de backups concluída!\n\n";
        $message .= "📊 Estatísticas:\n";
        $message .= "• Backups locais removidos: " . ($cleanupStats['local_deleted'] ?? 0) . "\n";
        $message .= "• Backups remotos removidos: " . ($cleanupStats['remote_deleted'] ?? 0) . "\n";
        $message .= "• Espaço local liberado: " . $this->formatFileSize($cleanupStats['local_size_freed'] ?? 0) . "\n";
        $message .= "• Espaço remoto liberado: " . $this->formatFileSize($cleanupStats['remote_size_freed'] ?? 0) . "\n";
        
        return $message;
    }
    
    /**
     * Construir corpo HTML do email
     */
    protected function buildHtmlEmailBody(string $subject, string $message, string $type, array $data): string
    {
        $colors = [
            'success' => '#28a745',
            'warning' => '#ffc107',
            'error' => '#dc3545',
            'info' => '#17a2b8'
        ];
        
        $color = $colors[$type] ?? '#17a2b8';
        
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: {$color}; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; color: #666; font-size: 12px; }
                pre { background: #f4f4f4; padding: 10px; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$subject}</h2>
                </div>
                <div class='content'>
                    <pre>" . htmlspecialchars($message) . "</pre>
                </div>
                <div class='footer'>
                    Sistema de Backup - " . date('Y-m-d H:i:s') . "
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Construir campos para Slack
     */
    protected function buildSlackFields(array $data): array
    {
        $fields = [];
        
        if (isset($data['tenant_id'])) {
            $fields[] = [
                'title' => 'Tenant',
                'value' => $data['tenant_id'],
                'short' => true
            ];
        }
        
        if (isset($data['backup_info']['duration'])) {
            $fields[] = [
                'title' => 'Duração',
                'value' => round($data['backup_info']['duration'], 2) . 's',
                'short' => true
            ];
        }
        
        return $fields;
    }
    
    /**
     * Obter ícone do Slack por tipo
     */
    protected function getSlackIcon(string $type): string
    {
        $icons = [
            'success' => ':white_check_mark:',
            'warning' => ':warning:',
            'error' => ':x:',
            'info' => ':information_source:'
        ];
        
        return $icons[$type] ?? ':gear:';
    }
    
    /**
     * Obter email do tenant
     */
    protected function getTenantEmail(string $tenantId): ?string
    {
        try {
            list($idContador, $idEmpresa) = explode(':', $tenantId);
            
            $db = \Config\Database::connect();
            $query = $db->query(
                "SELECT email FROM empresas WHERE id_contador = ? AND id = ?",
                [$idContador, $idEmpresa]
            );
            
            $result = $query->getRow();
            return $result ? $result->email : null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Log de erro de notificação
     */
    protected function logNotificationError(string $channel, string $error): void
    {
        $logEntry = [
            'timestamp' => date('c'),
            'channel' => $channel,
            'error' => $error
        ];
        
        $logFile = WRITEPATH . 'logs/notification_errors_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Formatar tamanho de arquivo
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
