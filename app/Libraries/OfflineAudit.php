<?php

namespace App\Libraries;

use Config\Database;

/**
 * Biblioteca de Auditoria para Operações Offline
 * Registra todas as operações realizadas em modo offline para rastreabilidade
 */
class OfflineAudit
{
    /**
     * Registra operação offline no log de auditoria
     * 
     * @param string $action Ação realizada (create_sale, update_product, etc)
     * @param string $entityType Tipo de entidade (sale, product, customer, etc)
     * @param int|string $entityId ID da entidade
     * @param array $data Dados da operação
     * @param string $status Status da operação (pending, synced, failed)
     * @return void
     */
    public static function log(
        string $action, 
        string $entityType, 
        $entityId, 
        array $data = [], 
        string $status = 'pending'
    ): void {
        try {
            if (!function_exists('is_offline_mode') || !is_offline_mode()) {
                return; // Só audita operações offline
            }
            
            $db = Database::connect();
            
            // Criar tabela se não existir
            self::ensureAuditTable($db);
            
            // Extrair tenant_id
            [$idContador, $idEmpresa] = self::resolveTenantIds($data);
            
            if ($idContador === 0 || $idEmpresa === 0) {
                log_message('warning', '[OfflineAudit] Operação sem tenant_id válido', [
                    'action' => $action,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId
                ]);
            }
            
            // Dados sensíveis que não devem ser logados
            $sanitizedData = self::sanitizeData($data);
            
            $auditData = [
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'data_snapshot' => json_encode($sanitizedData, JSON_UNESCAPED_UNICODE),
                'status' => $status,
                'user_id' => session()->get('id') ?? null,
                'user_name' => session()->get('nome') ?? 'Sistema',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            $db->table('offline_audit_log')->insert($auditData);
            
            log_message('info', '[OfflineAudit] Operação registrada', [
                'action' => $action,
                'entity' => "{$entityType}:{$entityId}",
                'tenant' => "{$idContador}:{$idEmpresa}"
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[OfflineAudit] Erro ao registrar auditoria', [
                'error' => $e->getMessage(),
                'action' => $action
            ]);
            // Não interrompe fluxo do app
        }
    }
    
    /**
     * Atualiza status de auditoria após sincronização
     */
    public static function updateStatus(int $auditId, string $status, ?string $syncDetails = null): void
    {
        try {
            $db = Database::connect();
            
            $updateData = [
                'status' => $status,
                'synced_at' => date('Y-m-d H:i:s')
            ];
            
            if ($syncDetails) {
                $updateData['sync_details'] = $syncDetails;
            }
            
            $db->table('offline_audit_log')->where('id', $auditId)->update($updateData);
            
        } catch (\Throwable $e) {
            log_message('error', '[OfflineAudit] Erro ao atualizar status', [
                'audit_id' => $auditId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Busca operações pendentes de sincronização
     */
    public static function getPendingOperations(int $idContador, int $idEmpresa, int $limit = 100): array
    {
        try {
            $db = Database::connect();
            
            return $db->table('offline_audit_log')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->where('status', 'pending')
                ->orderBy('created_at', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();
                
        } catch (\Throwable $e) {
            log_message('error', '[OfflineAudit] Erro ao buscar operações pendentes', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Retorna estatísticas de sincronização
     */
    public static function getSyncStats(int $idContador, int $idEmpresa): array
    {
        try {
            $db = Database::connect();
            
            $pending = $db->table('offline_audit_log')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->where('status', 'pending')
                ->countAllResults();
                
            $synced = $db->table('offline_audit_log')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->where('status', 'synced')
                ->where('synced_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->countAllResults();
                
            $failed = $db->table('offline_audit_log')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->where('status', 'failed')
                ->countAllResults();
                
            return [
                'pending' => $pending,
                'synced_last_24h' => $synced,
                'failed' => $failed,
                'total' => $pending + $synced + $failed
            ];
            
        } catch (\Throwable $e) {
            log_message('error', '[OfflineAudit] Erro ao obter estatísticas', [
                'error' => $e->getMessage()
            ]);
            return ['pending' => 0, 'synced_last_24h' => 0, 'failed' => 0, 'total' => 0];
        }
    }
    
    /**
     * Garante que a tabela de auditoria existe
     */
    private static function ensureAuditTable($db): void
    {
        if ($db->tableExists('offline_audit_log')) {
            return;
        }
        
        $forge = Database::forge();
        
        $forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_contador' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'entity_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'data_snapshot' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'synced', 'failed'],
                'default' => 'pending',
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sync_details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $forge->addKey('id', true);
        $forge->addKey(['id_contador', 'id_empresa', 'status']);
        $forge->addKey('created_at');
        
        $forge->createTable('offline_audit_log', true);
        
        log_message('info', '[OfflineAudit] Tabela offline_audit_log criada');
    }
    
    /**
     * Remove dados sensíveis antes de logar
     */
    private static function sanitizeData(array $data): array
    {
        $sensitiveFields = ['senha', 'password', 'token', 'api_key', 'secret', 'cvv', 'card_number'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }
        
        return $data;
    }
    
    /**
     * Resolve tenant IDs
     */
    private static function resolveTenantIds(array $data): array
    {
        $idContador = (int) ($data['id_contador'] ?? 0);
        $idEmpresa  = (int) ($data['id_empresa'] ?? 0);
        
        if ($idContador === 0 || $idEmpresa === 0) {
            if (function_exists('resolve_tenant_ids')) {
                return resolve_tenant_ids();
            }
            
            $session = session();
            return [
                (int) ($session->get('id_contador') ?? 0),
                (int) ($session->get('id_empresa') ?? 0)
            ];
        }
        
        return [$idContador, $idEmpresa];
    }
}
