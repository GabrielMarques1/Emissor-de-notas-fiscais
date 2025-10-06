-- TRIGGERS DE PROTEÇÃO MULTI-TENANT
-- Gerado automaticamente em 2025-10-05 23:58:44

-- IMPORTANTE: Execute primeiro a migration da tabela audit_deleted_records


-- TRIGGER DE VALIDAÇÃO INSERT para pos_sales
DELIMITER $$
CREATE TRIGGER trg_pos_sales_insert_tenant_validation
    BEFORE INSERT ON pos_sales
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO pos_sales'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'pos_sales',
            'primary_key', 'id_pos_sale',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para pos_sales
DELIMITER $$
CREATE TRIGGER trg_pos_sales_update_tenant_protection
    BEFORE UPDATE ON pos_sales
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE pos_sales SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'pos_sales',
                'record_id', OLD.id_pos_sale,
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



-- TRIGGER DE AUDITORIA DELETE para pos_sales
DELIMITER $$
CREATE TRIGGER trg_pos_sales_delete_audit
    BEFORE DELETE ON pos_sales
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
        'pos_sales',
        OLD.id_pos_sale,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_pos_sale', OLD.id_pos_sale, 'id_shift', OLD.id_shift, 'id_caixa_sessao', OLD.id_caixa_sessao, 'id_cash_register', OLD.id_cash_register, 'sale_number', OLD.sale_number, 'total', OLD.total, 'total_discount', OLD.total_discount, 'discount', OLD.discount, 'paid_amount', OLD.paid_amount, 'change_amount', OLD.change_amount, 'payment_type', OLD.payment_type, 'is_multi_payment', OLD.is_multi_payment, 'total_paid', OLD.total_paid, 'id_tef_transaction', OLD.id_tef_transaction, 'id_pix_transaction', OLD.id_pix_transaction, 'id_cliente', OLD.id_cliente, 'notes', OLD.notes, 'status', OLD.status, 'is_suspended', OLD.is_suspended, 'suspended_at', OLD.suspended_at, 'suspended_by', OLD.suspended_by, 'suspended_reason', OLD.suspended_reason, 'resumed_at', OLD.resumed_at, 'resumed_by', OLD.resumed_by, 'suspension_expires_at', OLD.suspension_expires_at, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at, 'id_nfce', OLD.id_nfce, 'chave_nfce', OLD.chave_nfce),
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
        CONCAT('DELETE FROM pos_sales'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'pos_sales',
            'record_id', OLD.id_pos_sale,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para pos_sale_items
DELIMITER $$
CREATE TRIGGER trg_pos_sale_items_insert_tenant_validation
    BEFORE INSERT ON pos_sale_items
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO pos_sale_items'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'pos_sale_items',
            'primary_key', 'id_item',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para pos_sale_items
DELIMITER $$
CREATE TRIGGER trg_pos_sale_items_update_tenant_protection
    BEFORE UPDATE ON pos_sale_items
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE pos_sale_items SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'pos_sale_items',
                'record_id', OLD.id_item,
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



-- TRIGGER DE AUDITORIA DELETE para pos_sale_items
DELIMITER $$
CREATE TRIGGER trg_pos_sale_items_delete_audit
    BEFORE DELETE ON pos_sale_items
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
        'pos_sale_items',
        OLD.id_item,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_item', OLD.id_item, 'id_pos_sale', OLD.id_pos_sale, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'id_produto', OLD.id_produto, 'nome', OLD.nome, 'codigo_de_barras', OLD.codigo_de_barras, 'unidade', OLD.unidade, 'quantidade', OLD.quantidade, 'valor_unitario', OLD.valor_unitario, 'desconto', OLD.desconto, 'CFOP_NFe', OLD.CFOP_NFe, 'CFOP_NFCe', OLD.CFOP_NFCe, 'CFOP_Externo', OLD.CFOP_Externo, 'NCM', OLD.NCM, 'CSOSN', OLD.CSOSN, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at),
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
        CONCAT('DELETE FROM pos_sale_items'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'pos_sale_items',
            'record_id', OLD.id_item,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para pos_sale_payments
DELIMITER $$
CREATE TRIGGER trg_pos_sale_payments_insert_tenant_validation
    BEFORE INSERT ON pos_sale_payments
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO pos_sale_payments'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'pos_sale_payments',
            'primary_key', 'id_payment',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para pos_sale_payments
DELIMITER $$
CREATE TRIGGER trg_pos_sale_payments_update_tenant_protection
    BEFORE UPDATE ON pos_sale_payments
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE pos_sale_payments SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'pos_sale_payments',
                'record_id', OLD.id_payment,
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



-- TRIGGER DE AUDITORIA DELETE para pos_sale_payments
DELIMITER $$
CREATE TRIGGER trg_pos_sale_payments_delete_audit
    BEFORE DELETE ON pos_sale_payments
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
        'pos_sale_payments',
        OLD.id_payment,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_payment', OLD.id_payment, 'id_pos_sale', OLD.id_pos_sale, 'payment_type', OLD.payment_type, 'amount', OLD.amount, 'installments', OLD.installments, 'id_tef_transaction', OLD.id_tef_transaction, 'id_pix_transaction', OLD.id_pix_transaction, 'change_amount', OLD.change_amount, 'status', OLD.status, 'confirmed_at', OLD.confirmed_at, 'metadata', OLD.metadata, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at),
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
        CONCAT('DELETE FROM pos_sale_payments'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'pos_sale_payments',
            'record_id', OLD.id_payment,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para produtos
DELIMITER $$
CREATE TRIGGER trg_produtos_insert_tenant_validation
    BEFORE INSERT ON produtos
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO produtos'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'produtos',
            'primary_key', 'id_produto',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para produtos
DELIMITER $$
CREATE TRIGGER trg_produtos_update_tenant_protection
    BEFORE UPDATE ON produtos
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE produtos SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'produtos',
                'record_id', OLD.id_produto,
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



-- TRIGGER DE AUDITORIA DELETE para produtos
DELIMITER $$
CREATE TRIGGER trg_produtos_delete_audit
    BEFORE DELETE ON produtos
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
        'produtos',
        OLD.id_produto,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_produto', OLD.id_produto, 'nome', OLD.nome, 'codigo_de_barras', OLD.codigo_de_barras, 'valor_unitario', OLD.valor_unitario, 'CFOP_NFe', OLD.CFOP_NFe, 'CFOP_NFCe', OLD.CFOP_NFCe, 'CFOP_Externo', OLD.CFOP_Externo, 'NCM', OLD.NCM, 'CSOSN', OLD.CSOSN, 'id_unidade', OLD.id_unidade, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at, 'estoque', OLD.estoque, 'estoque_minimo', OLD.estoque_minimo),
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
        CONCAT('DELETE FROM produtos'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'produtos',
            'record_id', OLD.id_produto,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para clientes
DELIMITER $$
CREATE TRIGGER trg_clientes_insert_tenant_validation
    BEFORE INSERT ON clientes
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO clientes'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'clientes',
            'primary_key', 'id_cliente',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para clientes
DELIMITER $$
CREATE TRIGGER trg_clientes_update_tenant_protection
    BEFORE UPDATE ON clientes
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE clientes SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'clientes',
                'record_id', OLD.id_cliente,
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



-- TRIGGER DE AUDITORIA DELETE para clientes
DELIMITER $$
CREATE TRIGGER trg_clientes_delete_audit
    BEFORE DELETE ON clientes
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
        'clientes',
        OLD.id_cliente,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_cliente', OLD.id_cliente, 'tipo', OLD.tipo, 'nome', OLD.nome, 'cpf', OLD.cpf, 'cnpj', OLD.cnpj, 'razao_social', OLD.razao_social, 'isento', OLD.isento, 'ie', OLD.ie, 'logradouro', OLD.logradouro, 'numero', OLD.numero, 'complemento', OLD.complemento, 'bairro', OLD.bairro, 'cep', OLD.cep, 'fone', OLD.fone, 'id_uf', OLD.id_uf, 'id_municipio', OLD.id_municipio, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at),
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
        CONCAT('DELETE FROM clientes'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'clientes',
            'record_id', OLD.id_cliente,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para fornecedores
DELIMITER $$
CREATE TRIGGER trg_fornecedores_insert_tenant_validation
    BEFORE INSERT ON fornecedores
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO fornecedores'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'fornecedores',
            'primary_key', 'id_fornecedor',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para fornecedores
DELIMITER $$
CREATE TRIGGER trg_fornecedores_update_tenant_protection
    BEFORE UPDATE ON fornecedores
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE fornecedores SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'fornecedores',
                'record_id', OLD.id_fornecedor,
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



-- TRIGGER DE AUDITORIA DELETE para fornecedores
DELIMITER $$
CREATE TRIGGER trg_fornecedores_delete_audit
    BEFORE DELETE ON fornecedores
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
        'fornecedores',
        OLD.id_fornecedor,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_fornecedor', OLD.id_fornecedor, 'tipo', OLD.tipo, 'nome', OLD.nome, 'cpf', OLD.cpf, 'cnpj', OLD.cnpj, 'razao_social', OLD.razao_social, 'isento', OLD.isento, 'ie', OLD.ie, 'logradouro', OLD.logradouro, 'numero', OLD.numero, 'complemento', OLD.complemento, 'bairro', OLD.bairro, 'cep', OLD.cep, 'id_uf', OLD.id_uf, 'id_municipio', OLD.id_municipio, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at),
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
        CONCAT('DELETE FROM fornecedores'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'fornecedores',
            'record_id', OLD.id_fornecedor,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para cash_registers
DELIMITER $$
CREATE TRIGGER trg_cash_registers_insert_tenant_validation
    BEFORE INSERT ON cash_registers
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO cash_registers'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'cash_registers',
            'primary_key', 'id_cash_register',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para cash_registers
DELIMITER $$
CREATE TRIGGER trg_cash_registers_update_tenant_protection
    BEFORE UPDATE ON cash_registers
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE cash_registers SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'cash_registers',
                'record_id', OLD.id_cash_register,
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



-- TRIGGER DE AUDITORIA DELETE para cash_registers
DELIMITER $$
CREATE TRIGGER trg_cash_registers_delete_audit
    BEFORE DELETE ON cash_registers
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
        'cash_registers',
        OLD.id_cash_register,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_cash_register', OLD.id_cash_register, 'name', OLD.name, 'location', OLD.location, 'status', OLD.status, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at),
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
        CONCAT('DELETE FROM cash_registers'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'cash_registers',
            'record_id', OLD.id_cash_register,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para cash_movements
DELIMITER $$
CREATE TRIGGER trg_cash_movements_insert_tenant_validation
    BEFORE INSERT ON cash_movements
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO cash_movements'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'cash_movements',
            'primary_key', 'id_movement',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para cash_movements
DELIMITER $$
CREATE TRIGGER trg_cash_movements_update_tenant_protection
    BEFORE UPDATE ON cash_movements
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE cash_movements SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'cash_movements',
                'record_id', OLD.id_movement,
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



-- TRIGGER DE AUDITORIA DELETE para cash_movements
DELIMITER $$
CREATE TRIGGER trg_cash_movements_delete_audit
    BEFORE DELETE ON cash_movements
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
        'cash_movements',
        OLD.id_movement,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_movement', OLD.id_movement, 'id_shift', OLD.id_shift, 'id_cash_register', OLD.id_cash_register, 'type', OLD.type, 'amount', OLD.amount, 'reason', OLD.reason, 'notes', OLD.notes, 'performed_by', OLD.performed_by, 'authorized_by', OLD.authorized_by, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at),
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
        CONCAT('DELETE FROM cash_movements'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'cash_movements',
            'record_id', OLD.id_movement,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para inventory_movements
DELIMITER $$
CREATE TRIGGER trg_inventory_movements_insert_tenant_validation
    BEFORE INSERT ON inventory_movements
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO inventory_movements'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'inventory_movements',
            'primary_key', 'id_inventory_movement',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para inventory_movements
DELIMITER $$
CREATE TRIGGER trg_inventory_movements_update_tenant_protection
    BEFORE UPDATE ON inventory_movements
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE inventory_movements SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'inventory_movements',
                'record_id', OLD.id_inventory_movement,
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



-- TRIGGER DE AUDITORIA DELETE para inventory_movements
DELIMITER $$
CREATE TRIGGER trg_inventory_movements_delete_audit
    BEFORE DELETE ON inventory_movements
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
        'inventory_movements',
        OLD.id_inventory_movement,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_inventory_movement', OLD.id_inventory_movement, 'id_produto', OLD.id_produto, 'tipo', OLD.tipo, 'quantidade', OLD.quantidade, 'motivo', OLD.motivo, 'id_pos_sale', OLD.id_pos_sale, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at),
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
        CONCAT('DELETE FROM inventory_movements'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'inventory_movements',
            'record_id', OLD.id_inventory_movement,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para shifts
DELIMITER $$
CREATE TRIGGER trg_shifts_insert_tenant_validation
    BEFORE INSERT ON shifts
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO shifts'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'shifts',
            'primary_key', 'id_shift',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para shifts
DELIMITER $$
CREATE TRIGGER trg_shifts_update_tenant_protection
    BEFORE UPDATE ON shifts
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE shifts SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'shifts',
                'record_id', OLD.id_shift,
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



-- TRIGGER DE AUDITORIA DELETE para shifts
DELIMITER $$
CREATE TRIGGER trg_shifts_delete_audit
    BEFORE DELETE ON shifts
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
        'shifts',
        OLD.id_shift,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_shift', OLD.id_shift, 'id_cash_register', OLD.id_cash_register, 'opened_by', OLD.opened_by, 'closed_by', OLD.closed_by, 'opened_at', OLD.opened_at, 'closed_at', OLD.closed_at, 'opening_amount', OLD.opening_amount, 'closing_amount', OLD.closing_amount, 'status', OLD.status, 'id_contador', OLD.id_contador, 'id_empresa', OLD.id_empresa, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at),
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
        CONCAT('DELETE FROM shifts'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'shifts',
            'record_id', OLD.id_shift,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE VALIDAÇÃO INSERT para empresas
DELIMITER $$
CREATE TRIGGER trg_empresas_insert_tenant_validation
    BEFORE INSERT ON empresas
    FOR EACH ROW
BEGIN
    -- Validar que campos de tenant não sejam NULL ou 0
    IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
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
        CONCAT('INSERT INTO empresas'),
        CONCAT(COALESCE(NEW.id_contador, 0), ':', COALESCE(NEW.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'empresas',
            'primary_key', 'id_empresa',
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;



-- TRIGGER DE PROTEÇÃO UPDATE para empresas
DELIMITER $$
CREATE TRIGGER trg_empresas_update_tenant_protection
    BEFORE UPDATE ON empresas
    FOR EACH ROW
BEGIN
    -- Prevenir alteração de campos de tenant
    IF (OLD.id_contador != NEW.id_contador OR OLD.id_empresa != NEW.id_empresa) THEN
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
            CONCAT('UPDATE empresas SET tenant_id'),
            CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
            JSON_OBJECT(
                'table', 'empresas',
                'record_id', OLD.id_empresa,
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



-- TRIGGER DE AUDITORIA DELETE para empresas
DELIMITER $$
CREATE TRIGGER trg_empresas_delete_audit
    BEFORE DELETE ON empresas
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
        'empresas',
        OLD.id_empresa,
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        OLD.id_contador,
        OLD.id_empresa,
        JSON_OBJECT('id_empresa', OLD.id_empresa, 'status', OLD.status, 'CNPJ', OLD.CNPJ, 'xNome', OLD.xNome, 'xFant', OLD.xFant, 'tef_acquirer', OLD.tef_acquirer, 'tef_merchant_id', OLD.tef_merchant_id, 'tef_merchant_key', OLD.tef_merchant_key, 'tef_environment', OLD.tef_environment, 'tef_timeout', OLD.tef_timeout, 'tef_max_installments', OLD.tef_max_installments, 'pix_provider', OLD.pix_provider, 'pix_key', OLD.pix_key, 'pix_access_token', OLD.pix_access_token, 'pix_webhook_secret', OLD.pix_webhook_secret, 'pix_expiration_minutes', OLD.pix_expiration_minutes, 'suspension_timeout_hours', OLD.suspension_timeout_hours, 'max_suspended_sales', OLD.max_suspended_sales, 'max_discount_percentage', OLD.max_discount_percentage, 'max_discount_amount', OLD.max_discount_amount, 'require_discount_approval', OLD.require_discount_approval, 'discount_approval_threshold', OLD.discount_approval_threshold, 'return_days_limit', OLD.return_days_limit, 'require_return_approval', OLD.require_return_approval, 'allow_partial_returns', OLD.allow_partial_returns, 'allow_exchanges', OLD.allow_exchanges, 'IE', OLD.IE, 'dia_do_pagamento', OLD.dia_do_pagamento, 'CEP', OLD.CEP, 'xLgr', OLD.xLgr, 'nro', OLD.nro, 'xCpl', OLD.xCpl, 'xBairro', OLD.xBairro, 'fone', OLD.fone, 'natOp', OLD.natOp, 'serie', OLD.serie, 'verProc', OLD.verProc, 'nNF_homologacao', OLD.nNF_homologacao, 'nNF_producao', OLD.nNF_producao, 'tpAmb_NFe', OLD.tpAmb_NFe, 'nNFC_homologacao', OLD.nNFC_homologacao, 'nNFC_producao', OLD.nNFC_producao, 'tpAmb_NFCe', OLD.tpAmb_NFCe, 'CSC_Id', OLD.CSC_Id, 'CSC', OLD.CSC, 'certificado', OLD.certificado, 'senha_do_certificado', OLD.senha_do_certificado, 'id_login', OLD.id_login, 'id_contador', OLD.id_contador, 'id_uf', OLD.id_uf, 'id_municipio', OLD.id_municipio, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at, 'valor_mensalidade', OLD.valor_mensalidade, 'data_bloqueio', OLD.data_bloqueio, 'motivo_bloqueio', OLD.motivo_bloqueio, 'stripe_customer_id', OLD.stripe_customer_id, 'stripe_subscription_id', OLD.stripe_subscription_id, 'stripe_price_id', OLD.stripe_price_id, 'stripe_product_id', OLD.stripe_product_id, 'stripe_status', OLD.stripe_status, 'current_period_end', OLD.current_period_end, 'trial_ends_at', OLD.trial_ends_at),
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
        CONCAT('DELETE FROM empresas'),
        CONCAT(COALESCE(OLD.id_contador, 0), ':', COALESCE(OLD.id_empresa, 0)),
        JSON_OBJECT(
            'table', 'empresas',
            'record_id', OLD.id_empresa,
            'user', COALESCE(@current_user_id, 'unknown'),
            'session_id', COALESCE(@session_id, 'unknown')
        ),
        NOW()
    );
END$$
DELIMITER ;
