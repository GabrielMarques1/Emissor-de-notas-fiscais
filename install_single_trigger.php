<?php
echo "=== INSTALAÇÃO DE TRIGGER ÚNICO DE TESTE ===\n\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    echo "Instalando trigger de teste para pos_sales...\n";
    
    // Trigger simples de teste
    $triggerSql = "
    CREATE TRIGGER trg_pos_sales_insert_validation
        BEFORE INSERT ON pos_sales
        FOR EACH ROW
    BEGIN
        IF (NEW.id_contador IS NULL OR NEW.id_contador = 0 OR NEW.id_empresa IS NULL OR NEW.id_empresa = 0) THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'SECURITY: Campos de tenant obrigatórios';
        END IF;
    END
    ";
    
    // Remover trigger se existir
    $db->query("DROP TRIGGER IF EXISTS trg_pos_sales_insert_validation");
    
    if ($db->query($triggerSql)) {
        echo "✓ Trigger de validação INSERT instalado para pos_sales\n";
        
        // Verificar se foi criado
        $result = $db->query("
            SELECT TRIGGER_NAME 
            FROM information_schema.TRIGGERS 
            WHERE TRIGGER_SCHEMA = 'erp_local' 
            AND TRIGGER_NAME = 'trg_pos_sales_insert_validation'
        ");
        
        if ($result && $result->num_rows > 0) {
            echo "✓ Trigger confirmado no banco de dados\n";
        } else {
            echo "✗ Trigger não encontrado após criação\n";
        }
        
    } else {
        echo "✗ Erro ao criar trigger: " . $db->error . "\n";
    }
    
    // Testar o trigger
    echo "\nTestando trigger...\n";
    
    // Teste 1: INSERT sem tenant (deve falhar)
    echo "Teste 1: INSERT sem tenant_id...\n";
    $testSql = "INSERT INTO pos_sales (sale_number, total, status) VALUES ('TEST-001', 100.00, 'test')";
    
    if ($db->query($testSql)) {
        echo "✗ FALHA: INSERT sem tenant foi permitido!\n";
        // Limpar registro de teste
        $db->query("DELETE FROM pos_sales WHERE sale_number = 'TEST-001'");
    } else {
        if (strpos($db->error, 'SECURITY') !== false) {
            echo "✓ SUCESSO: INSERT sem tenant foi bloqueado pelo trigger\n";
        } else {
            echo "? INSERT falhou por outro motivo: " . $db->error . "\n";
        }
    }
    
    // Teste 2: INSERT com tenant (deve funcionar)
    echo "Teste 2: INSERT com tenant_id válido...\n";
    $testSql2 = "INSERT INTO pos_sales (id_contador, id_empresa, sale_number, total, status) VALUES (1, 1, 'TEST-002', 100.00, 'test')";
    
    if ($db->query($testSql2)) {
        echo "✓ SUCESSO: INSERT com tenant foi permitido\n";
        // Limpar registro de teste
        $db->query("DELETE FROM pos_sales WHERE sale_number = 'TEST-002'");
    } else {
        echo "✗ FALHA: INSERT com tenant foi bloqueado: " . $db->error . "\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
