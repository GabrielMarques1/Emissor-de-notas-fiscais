<?php

namespace App\Libraries;

use Config\Database;

class Outbox
{
    /**
     * Registra evento no outbox com validação de tenant
     * 
     * @param string $table Nome da tabela
     * @param array $primaryKey Chave primária ['id' => 123]
     * @param string $operation insert|update|delete
     * @param array|null $payload Dados completos do registro
     * @return void
     */
    public static function record(string $table, array $primaryKey, string $operation, ?array $payload = null): void
    {
        try {
            $db = Database::connect();
            if (! $db->tableExists('outbox_events')) {
                return; // sem tabela, não falha a operação original
            }
            
            // Extrair tenant_id do payload para indexação e validação
            [$idContador, $idEmpresa] = self::extractTenantIds($payload);
            
            // Validar que tenant está presente em operações críticas
            if ($idContador === 0 || $idEmpresa === 0) {
                log_message('warning', '[Outbox] Evento sem tenant_id válido', [
                    'table' => $table,
                    'operation' => $operation,
                    'pk' => json_encode($primaryKey)
                ]);
            }
            
            $eventData = [
                'table_name'       => $table,
                'primary_key_json' => json_encode($primaryKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'operation'        => $operation,
                'payload'          => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'id_contador'      => $idContador,
                'id_empresa'       => $idEmpresa,
                'created_at'       => date('Y-m-d H:i:s'),
                'retry_count'      => 0,
                'status'           => 'pending',
            ];
            
            $db->table('outbox_events')->insert($eventData);
            
            log_message('info', '[Outbox] Evento registrado', [
                'table' => $table,
                'operation' => $operation,
                'tenant' => "{$idContador}:{$idEmpresa}"
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Outbox] Erro ao registrar evento', [
                'error' => $e->getMessage(),
                'table' => $table,
                'operation' => $operation
            ]);
            // não interrompe fluxo do app
        }
    }
    
    /**
     * Extrai IDs de tenant do payload
     */
    private static function extractTenantIds(?array $payload): array
    {
        if (!$payload) {
            return self::resolveTenantFromSession();
        }
        
        $idContador = (int) ($payload['id_contador'] ?? 0);
        $idEmpresa  = (int) ($payload['id_empresa'] ?? 0);
        
        if ($idContador === 0 || $idEmpresa === 0) {
            return self::resolveTenantFromSession();
        }
        
        return [$idContador, $idEmpresa];
    }
    
    /**
     * Resolve tenant da sessão como fallback
     */
    private static function resolveTenantFromSession(): array
    {
        if (function_exists('resolve_tenant_ids')) {
            return resolve_tenant_ids();
        }
        
        $session = session();
        return [
            (int) ($session->get('id_contador') ?? 0),
            (int) ($session->get('id_empresa') ?? 0)
        ];
    }
    
    /**
     * Marca evento como processado
     */
    public static function markProcessed(int $eventId): void
    {
        try {
            $db = Database::connect();
            $db->table('outbox_events')->where('id', $eventId)->update([
                'processed_at' => date('Y-m-d H:i:s'),
                'status' => 'processed'
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Outbox] Erro ao marcar como processado', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Marca evento como falho e incrementa retry
     */
    public static function markFailed(int $eventId, string $errorMessage): void
    {
        try {
            $db = Database::connect();
            $event = $db->table('outbox_events')->where('id', $eventId)->get()->getRowArray();
            
            if (!$event) return;
            
            $retryCount = (int) ($event['retry_count'] ?? 0) + 1;
            $maxRetries = 5;
            
            $db->table('outbox_events')->where('id', $eventId)->update([
                'retry_count' => $retryCount,
                'status' => $retryCount >= $maxRetries ? 'failed' : 'pending',
                'error_message' => $errorMessage,
                'last_attempt_at' => date('Y-m-d H:i:s')
            ]);
            
            log_message('warning', '[Outbox] Evento falhou', [
                'event_id' => $eventId,
                'retry_count' => $retryCount,
                'error' => $errorMessage
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Outbox] Erro ao marcar como falho', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
        }
    }
}


