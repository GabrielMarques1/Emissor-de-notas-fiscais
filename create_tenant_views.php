<?php
echo "=== CRIAÇÃO DE VIEWS COM FILTRO TENANT ===\n\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    echo "Criando views com filtro automático de tenant...\n\n";
    
    // Lista de views a serem criadas
    $views = [
        'vw_tenant_sales' => [
            'table' => 'pos_sales',
            'description' => 'Vendas filtradas por tenant',
            'columns' => 'id_pos_sale, sale_number, total, status, created_at, id_contador, id_empresa'
        ],
        'vw_tenant_products' => [
            'table' => 'produtos',
            'description' => 'Produtos filtrados por tenant',
            'columns' => 'id_produto, nome, codigo_de_barras, preco, estoque, status, id_contador, id_empresa'
        ],
        'vw_tenant_customers' => [
            'table' => 'clientes',
            'description' => 'Clientes filtrados por tenant',
            'columns' => 'id_cliente, nome, cpf_cnpj, telefone, email, status, id_contador, id_empresa'
        ],
        'vw_tenant_cash_movements' => [
            'table' => 'cash_movements',
            'description' => 'Movimentos de caixa filtrados por tenant',
            'columns' => 'id_cash_movement, type, amount, description, created_at, id_contador, id_empresa'
        ]
    ];
    
    $createdViews = 0;
    $errors = 0;
    
    foreach ($views as $viewName => $viewInfo) {
        echo "Criando view: {$viewName}\n";
        
        // Remover view se existir
        $db->query("DROP VIEW IF EXISTS {$viewName}");
        
        // Criar view com filtro tenant usando variáveis de sessão
        $viewSql = "
        CREATE VIEW {$viewName} AS
        SELECT {$viewInfo['columns']}
        FROM {$viewInfo['table']}
        WHERE id_contador = COALESCE(@tenant_id_contador, 0)
        AND id_empresa = COALESCE(@tenant_id_empresa, 0)
        ";
        
        if ($db->query($viewSql)) {
            echo "  ✓ {$viewName} criada - {$viewInfo['description']}\n";
            $createdViews++;
        } else {
            echo "  ✗ Erro ao criar {$viewName}: " . $db->error . "\n";
            $errors++;
        }
    }
    
    // Criar view especial para auditoria de segurança do tenant atual
    echo "\nCriando view de auditoria de segurança...\n";
    
    $db->query("DROP VIEW IF EXISTS vw_tenant_security_audit");
    
    $auditViewSql = "
    CREATE VIEW vw_tenant_security_audit AS
    SELECT 
        id,
        violation_type,
        ip_address,
        uri,
        tenant_id,
        context_data,
        created_at
    FROM security_audit
    WHERE tenant_id = CONCAT(COALESCE(@tenant_id_contador, 0), ':', COALESCE(@tenant_id_empresa, 0))
    ORDER BY created_at DESC
    ";
    
    if ($db->query($auditViewSql)) {
        echo "  ✓ vw_tenant_security_audit criada\n";
        $createdViews++;
    } else {
        echo "  ✗ Erro ao criar vw_tenant_security_audit: " . $db->error . "\n";
        $errors++;
    }
    
    // Criar view para registros deletados do tenant
    echo "Criando view de registros deletados...\n";
    
    $db->query("DROP VIEW IF EXISTS vw_tenant_deleted_records");
    
    $deletedViewSql = "
    CREATE VIEW vw_tenant_deleted_records AS
    SELECT 
        id,
        table_name,
        record_id,
        tenant_id,
        record_data,
        deleted_by_user,
        deleted_by_ip,
        deletion_reason,
        deleted_at,
        can_restore
    FROM audit_deleted_records
    WHERE id_contador = COALESCE(@tenant_id_contador, 0)
    AND id_empresa = COALESCE(@tenant_id_empresa, 0)
    AND can_restore = 1
    ORDER BY deleted_at DESC
    ";
    
    if ($db->query($deletedViewSql)) {
        echo "  ✓ vw_tenant_deleted_records criada\n";
        $createdViews++;
    } else {
        echo "  ✗ Erro ao criar vw_tenant_deleted_records: " . $db->error . "\n";
        $errors++;
    }
    
    // Verificar views criadas
    echo "\nVerificando views criadas...\n";
    $result = $db->query("
        SELECT TABLE_NAME, TABLE_TYPE 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'erp_local' 
        AND TABLE_TYPE = 'VIEW'
        AND TABLE_NAME LIKE 'vw_tenant_%'
    ");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  ✓ {$row['TABLE_NAME']}\n";
        }
    } else {
        echo "  Nenhuma view encontrada\n";
    }
    
    echo "\n=== RESULTADO ===\n";
    echo "Views criadas: {$createdViews}\n";
    echo "Erros: {$errors}\n";
    
    if ($errors === 0) {
        echo "\n✅ TODAS AS VIEWS CRIADAS COM SUCESSO!\n";
        echo "\nComo usar as views:\n";
        echo "1. Definir tenant antes da consulta:\n";
        echo "   SET @tenant_id_contador = 1;\n";
        echo "   SET @tenant_id_empresa = 1;\n";
        echo "\n2. Consultar a view:\n";
        echo "   SELECT * FROM vw_tenant_sales;\n";
        echo "\n3. As views automaticamente filtram pelos IDs do tenant definidos\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== VIEWS CRIADAS ===\n";
