<?php
echo "=== CRIAÇÃO DE STORED PROCEDURES SEGURAS ===\n\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    echo "Criando stored procedures seguras...\n\n";
    
    // 1. Procedure para deletar venda com validação de tenant
    echo "1. Criando sp_secure_delete_sale...\n";
    
    $db->query("DROP PROCEDURE IF EXISTS sp_secure_delete_sale");
    
    $procedureSql = "
    CREATE PROCEDURE sp_secure_delete_sale(
        IN p_sale_id INT,
        IN p_tenant_id_contador INT,
        IN p_tenant_id_empresa INT,
        IN p_user_id VARCHAR(100),
        IN p_deletion_reason VARCHAR(255)
    )
    BEGIN
        DECLARE v_count INT DEFAULT 0;
        DECLARE v_current_tenant_contador INT;
        DECLARE v_current_tenant_empresa INT;
        
        -- Verificar se venda existe e pertence ao tenant
        SELECT COUNT(*), id_contador, id_empresa 
        INTO v_count, v_current_tenant_contador, v_current_tenant_empresa
        FROM pos_sales 
        WHERE id_pos_sale = p_sale_id;
        
        -- Validar existência
        IF v_count = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Venda não encontrada';
        END IF;
        
        -- Validar ownership
        IF v_current_tenant_contador != p_tenant_id_contador OR v_current_tenant_empresa != p_tenant_id_empresa THEN
            -- Log da tentativa suspeita
            INSERT INTO security_audit (
                violation_type, ip_address, uri, tenant_id, context_data, created_at
            ) VALUES (
                'UNAUTHORIZED_DELETE_ATTEMPT',
                'stored_procedure',
                CONCAT('sp_secure_delete_sale(', p_sale_id, ')'),
                CONCAT(p_tenant_id_contador, ':', p_tenant_id_empresa),
                JSON_OBJECT(
                    'sale_id', p_sale_id,
                    'requested_tenant', CONCAT(p_tenant_id_contador, ':', p_tenant_id_empresa),
                    'actual_tenant', CONCAT(v_current_tenant_contador, ':', v_current_tenant_empresa),
                    'user', p_user_id
                ),
                NOW()
            );
            
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acesso negado: venda não pertence ao tenant';
        END IF;
        
        -- Definir variáveis de contexto para os triggers
        SET @current_user_id = p_user_id;
        SET @deletion_reason = p_deletion_reason;
        SET @client_ip = 'stored_procedure';
        
        -- Deletar itens da venda primeiro
        DELETE FROM pos_sale_items WHERE id_pos_sale = p_sale_id;
        
        -- Deletar pagamentos da venda
        DELETE FROM pos_sale_payments WHERE id_pos_sale = p_sale_id;
        
        -- Deletar a venda
        DELETE FROM pos_sales WHERE id_pos_sale = p_sale_id;
        
        -- Log de sucesso
        INSERT INTO security_audit (
            violation_type, ip_address, uri, tenant_id, context_data, created_at
        ) VALUES (
            'SECURE_DELETE_SUCCESS',
            'stored_procedure',
            CONCAT('sp_secure_delete_sale(', p_sale_id, ')'),
            CONCAT(p_tenant_id_contador, ':', p_tenant_id_empresa),
            JSON_OBJECT(
                'sale_id', p_sale_id,
                'user', p_user_id,
                'reason', p_deletion_reason
            ),
            NOW()
        );
        
    END
    ";
    
    if ($db->query($procedureSql)) {
        echo "✓ sp_secure_delete_sale criada\n";
    } else {
        echo "✗ Erro ao criar sp_secure_delete_sale: " . $db->error . "\n";
    }
    
    // 2. Procedure para buscar vendas com filtro automático de tenant
    echo "2. Criando sp_get_tenant_sales...\n";
    
    $db->query("DROP PROCEDURE IF EXISTS sp_get_tenant_sales");
    
    $procedureSql2 = "
    CREATE PROCEDURE sp_get_tenant_sales(
        IN p_tenant_id_contador INT,
        IN p_tenant_id_empresa INT,
        IN p_limit INT,
        IN p_offset INT,
        IN p_status VARCHAR(20),
        IN p_date_from DATE,
        IN p_date_to DATE
    )
    BEGIN
        SELECT 
            id_pos_sale,
            sale_number,
            total,
            status,
            created_at,
            id_contador,
            id_empresa
        FROM pos_sales 
        WHERE id_contador = p_tenant_id_contador 
        AND id_empresa = p_tenant_id_empresa
        AND (p_status IS NULL OR status = p_status)
        AND (p_date_from IS NULL OR DATE(created_at) >= p_date_from)
        AND (p_date_to IS NULL OR DATE(created_at) <= p_date_to)
        ORDER BY created_at DESC
        LIMIT p_limit OFFSET p_offset;
    END
    ";
    
    if ($db->query($procedureSql2)) {
        echo "✓ sp_get_tenant_sales criada\n";
    } else {
        echo "✗ Erro ao criar sp_get_tenant_sales: " . $db->error . "\n";
    }
    
    // 3. Procedure para atualizar produto com validação
    echo "3. Criando sp_secure_update_product...\n";
    
    $db->query("DROP PROCEDURE IF EXISTS sp_secure_update_product");
    
    $procedureSql3 = "
    CREATE PROCEDURE sp_secure_update_product(
        IN p_product_id INT,
        IN p_tenant_id_contador INT,
        IN p_tenant_id_empresa INT,
        IN p_nome VARCHAR(255),
        IN p_preco DECIMAL(10,2),
        IN p_user_id VARCHAR(100)
    )
    BEGIN
        DECLARE v_count INT DEFAULT 0;
        DECLARE v_current_tenant_contador INT;
        DECLARE v_current_tenant_empresa INT;
        
        -- Verificar se produto existe e pertence ao tenant
        SELECT COUNT(*), id_contador, id_empresa 
        INTO v_count, v_current_tenant_contador, v_current_tenant_empresa
        FROM produtos 
        WHERE id_produto = p_product_id;
        
        -- Validar existência
        IF v_count = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto não encontrado';
        END IF;
        
        -- Validar ownership
        IF v_current_tenant_contador != p_tenant_id_contador OR v_current_tenant_empresa != p_tenant_id_empresa THEN
            -- Log da tentativa suspeita
            INSERT INTO security_audit (
                violation_type, ip_address, uri, tenant_id, context_data, created_at
            ) VALUES (
                'UNAUTHORIZED_UPDATE_ATTEMPT',
                'stored_procedure',
                CONCAT('sp_secure_update_product(', p_product_id, ')'),
                CONCAT(p_tenant_id_contador, ':', p_tenant_id_empresa),
                JSON_OBJECT(
                    'product_id', p_product_id,
                    'requested_tenant', CONCAT(p_tenant_id_contador, ':', p_tenant_id_empresa),
                    'actual_tenant', CONCAT(v_current_tenant_contador, ':', v_current_tenant_empresa),
                    'user', p_user_id
                ),
                NOW()
            );
            
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acesso negado: produto não pertence ao tenant';
        END IF;
        
        -- Atualizar produto
        UPDATE produtos 
        SET nome = p_nome, 
            preco = p_preco,
            updated_at = NOW()
        WHERE id_produto = p_product_id;
        
        -- Log de sucesso
        INSERT INTO security_audit (
            violation_type, ip_address, uri, tenant_id, context_data, created_at
        ) VALUES (
            'SECURE_UPDATE_SUCCESS',
            'stored_procedure',
            CONCAT('sp_secure_update_product(', p_product_id, ')'),
            CONCAT(p_tenant_id_contador, ':', p_tenant_id_empresa),
            JSON_OBJECT(
                'product_id', p_product_id,
                'user', p_user_id
            ),
            NOW()
        );
        
    END
    ";
    
    if ($db->query($procedureSql3)) {
        echo "✓ sp_secure_update_product criada\n";
    } else {
        echo "✗ Erro ao criar sp_secure_update_product: " . $db->error . "\n";
    }
    
    // Verificar procedures criadas
    echo "\nVerificando procedures criadas...\n";
    $result = $db->query("
        SELECT ROUTINE_NAME, ROUTINE_TYPE 
        FROM information_schema.ROUTINES 
        WHERE ROUTINE_SCHEMA = 'erp_local' 
        AND ROUTINE_NAME LIKE 'sp_secure_%'
    ");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  ✓ {$row['ROUTINE_NAME']} ({$row['ROUTINE_TYPE']})\n";
        }
    } else {
        echo "  Nenhuma procedure encontrada\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== PROCEDURES CRIADAS ===\n";
