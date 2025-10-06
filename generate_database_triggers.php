<?php
echo "=== GERADOR DE TRIGGERS DE PROTEÇÃO MULTI-TENANT ===\n\n";

// Carregar análise do banco
if (!file_exists('database_analysis.json')) {
    die("Erro: Execute primeiro analyze_database_structure.php\n");
}

$analysis = json_decode(file_get_contents('database_analysis.json'), true);
$criticalTables = $analysis['critical_tables'];

echo "Gerando triggers para " . count($criticalTables) . " tabelas críticas...\n\n";

// Conectar ao banco
try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    // Array para armazenar todos os SQLs gerados
    $allTriggers = [];
    $rollbackSql = [];
    
    foreach ($criticalTables as $tableName => $tableInfo) {
        echo "Processando tabela: {$tableName}\n";
        
        $hasIdContador = $tableInfo['has_id_contador'];
        $hasIdEmpresa = $tableInfo['has_id_empresa'];
        $columns = $tableInfo['columns'];
        
        // Identificar chave primária
        $primaryKey = null;
        $primaryKeyResult = $db->query("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = 'erp_local' 
            AND TABLE_NAME = '{$tableName}' 
            AND COLUMN_KEY = 'PRI'
            LIMIT 1
        ");
        
        if ($primaryKeyResult && $row = $primaryKeyResult->fetch_assoc()) {
            $primaryKey = $row['COLUMN_NAME'];
        } else {
            // Fallback para nomes comuns
            $commonPrimaryKeys = [
                'pos_sales' => 'id_pos_sale',
                'pos_sale_items' => 'id_pos_sale_item',
                'pos_sale_payments' => 'id_pos_sale_payment',
                'produtos' => 'id_produto',
                'clientes' => 'id_cliente',
                'fornecedores' => 'id_fornecedor',
                'cash_registers' => 'id_cash_register',
                'cash_movements' => 'id_cash_movement',
                'inventory_movements' => 'id_inventory_movement',
                'shifts' => 'id_shift',
                'empresas' => 'id_empresa'
            ];
            $primaryKey = $commonPrimaryKeys[$tableName] ?? 'id';
        }
        
        // 1. TRIGGER DE VALIDAÇÃO INSERT
        if ($hasIdContador || $hasIdEmpresa) {
            $triggerName = "trg_{$tableName}_insert_tenant_validation";
            
            $conditions = [];
            if ($hasIdContador) {
                $conditions[] = "NEW.id_contador IS NULL OR NEW.id_contador = 0";
            }
            if ($hasIdEmpresa) {
                $conditions[] = "NEW.id_empresa IS NULL OR NEW.id_empresa = 0";
            }
            
            $conditionSql = implode(' OR ', $conditions);
            
            $triggerSql = "
-- TRIGGER DE VALIDAÇÃO INSERT para {$tableName}
DELIMITER $$
CREATE TRIGGER {$triggerName}
    BEFORE INSERT ON {$tableName}
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF ({$conditionSql}) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'SECURITY VIOLATION: Campos de tenant (id_contador, id_empresa) são obrigatórios',
            MYSQL_ERRNO = 1644;
    END IF;
    
    -- Log da operação para auditoria
    INSERT INTO security_audit (
        violation_type, 
        ip_address, 
        uri, 
        tenant_id, 
        context_data, 
        created_at
    ) VALUES (
        'DATABASE_INSERT',
        COALESCE(@client_ip, 'unknown'),
        CONCAT('INSERT INTO {$tableName}'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', '{$tableName}',
            'primary_key', '{$primaryKey}',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;
";
            
            $allTriggers[] = $triggerSql;
            $rollbackSql[] = "DROP TRIGGER IF EXISTS {$triggerName};";
        }
        
        // 2. TRIGGER DE PROTEÇÃO UPDATE
        if ($hasIdContador || $hasIdEmpresa) {
            $triggerName = "trg_{$tableName}_update_tenant_protection";
            
            $protectionConditions = [];
            if ($hasIdContador) {
                $protectionConditions[] = "OLD.id_contador != NEW.id_contador";
            }
            if ($hasIdEmpresa) {
                $protectionConditions[] = "OLD.id_empresa != NEW.id_empresa";
            }
            
            $protectionSql = implode(' OR ', $protectionConditions);
            
            $triggerSql = "
-- TRIGGER DE PROTEÇÃO UPDATE para {$tableName}
DELIMITER $$
CREATE TRIGGER {$triggerName}
    BEFORE UPDATE ON {$tableName}
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF ({$protectionSql}) THEN
        -- Log da tentativa suspeita
        INSERT INTO security_audit (
            violation_type, 
            ip_address, 
            uri, 
            tenant_id, 
            context_data, 
            created_at
        ) VALUES (
            'TENANT_FIELD_TAMPERING',
            COALESCE(@client_ip, 'unknown'),
            CONCAT('UPDATE {$tableName} SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', '{$tableName}',
                'record_id', OLD.{$primaryKey},
                'old_tenant', CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
                'new_tenant', CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
                'user', COALESCE(@current_user_id, 'unknown'),
                'session_id', COALESCE(@session_id, 'unknown')
            ),
            NOW()
        );
        
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'SECURITY VIOLATION: Alteração de campos de tenant não permitida',
            MYSQL_ERRNO = 1645;
    END IF;
END$$
DELIMITER ;
";
            
            $allTriggers[] = $triggerSql;
            $rollbackSql[] = "DROP TRIGGER IF EXISTS {$triggerName};";
        }
        
        // 3. TRIGGER DE AUDITORIA DELETE
        $triggerName = "trg_{$tableName}_delete_audit";
        
        // Construir lista de colunas para o snapshot
        $columnsList = implode(', ', array_map(function($col) {
            return "'{$col}', OLD.{$col}";
        }, $columns));
        
        $triggerSql = "
-- TRIGGER DE AUDITORIA DELETE para {$tableName}
DELIMITER $$
CREATE TRIGGER {$triggerName}
    BEFORE DELETE ON {$tableName}
    FOR EACH ROW
BEGIN
    -- Salvar snapshot do registro deletado
    INSERT INTO audit_deleted_records (
        table_name,
        record_id,
        tenant_id,
        id_contador,
        id_empresa,
        record_data,
        deleted_by_user,
        deleted_by_ip,
        deletion_reason,
        deleted_at,
        can_restore
    ) VALUES (
        '{$tableName}',
        OLD.{$primaryKey},
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT({$columnsList}),
        COALESCE(@current_user_id, 'unknown'),
        COALESCE(@client_ip, 'unknown'),
        COALESCE(@deletion_reason, 'No reason provided'),
        NOW(),
        1
    );
    
    -- Log da deleção para auditoria de segurança
    INSERT INTO security_audit (
        violation_type, 
        ip_address, 
        uri, 
        tenant_id, 
        context_data, 
        created_at
    ) VALUES (
        'DATABASE_DELETE',
        COALESCE(@client_ip, 'unknown'),
        CONCAT('DELETE FROM {$tableName}'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', '{$tableName}',
            'record_id', OLD.{$primaryKey},
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;
";
        
        $allTriggers[] = $triggerSql;
        $rollbackSql[] = "DROP TRIGGER IF EXISTS {$triggerName};";
    }
    
    // Salvar SQLs em arquivos
    $installSql = "-- TRIGGERS DE PROTEÇÃO MULTI-TENANT\n";
    $installSql .= "-- Gerado automaticamente em " . date('Y-m-d H:i:s') . "\n\n";
    $installSql .= "-- IMPORTANTE: Execute primeiro a migration da tabela audit_deleted_records\n\n";
    $installSql .= implode("\n\n", $allTriggers);
    
    file_put_contents('install_database_triggers.sql', $installSql);
    
    $rollbackSqlContent = "-- ROLLBACK DOS TRIGGERS DE PROTEÇÃO\n";
    $rollbackSqlContent .= "-- Gerado automaticamente em " . date('Y-m-d H:i:s') . "\n\n";
    $rollbackSqlContent .= implode("\n", $rollbackSql);
    
    file_put_contents('rollback_database_triggers.sql', $rollbackSqlContent);
    
    echo "\n✓ Triggers gerados:\n";
    echo "  - install_database_triggers.sql (" . count($allTriggers) . " triggers)\n";
    echo "  - rollback_database_triggers.sql\n";
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== GERAÇÃO CONCLUÍDA ===\n";
