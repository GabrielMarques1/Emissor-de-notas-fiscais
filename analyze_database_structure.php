<?php
echo "=== ANÁLISE DE ESTRUTURA - TABELAS MULTI-TENANT ===\n\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    echo "1. Identificando tabelas multi-tenant...\n";
    
    // Buscar todas as tabelas
    $result = $db->query("SHOW TABLES");
    $allTables = [];
    
    while ($row = $result->fetch_array()) {
        $allTables[] = $row[0];
    }
    
    echo "Total de tabelas encontradas: " . count($allTables) . "\n\n";
    
    // Identificar tabelas multi-tenant (que têm id_contador e/ou id_empresa)
    $multiTenantTables = [];
    
    foreach ($allTables as $table) {
        $columnsResult = $db->query("DESCRIBE `{$table}`");
        $columns = [];
        
        while ($col = $columnsResult->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        $hasIdContador = in_array('id_contador', $columns);
        $hasIdEmpresa = in_array('id_empresa', $columns);
        
        if ($hasIdContador || $hasIdEmpresa) {
            $multiTenantTables[$table] = [
                'has_id_contador' => $hasIdContador,
                'has_id_empresa' => $hasIdEmpresa,
                'columns' => $columns
            ];
            
            echo "✓ {$table}";
            if ($hasIdContador) echo " [id_contador]";
            if ($hasIdEmpresa) echo " [id_empresa]";
            echo "\n";
        }
    }
    
    echo "\nTabelas multi-tenant encontradas: " . count($multiTenantTables) . "\n\n";
    
    // Verificar triggers existentes
    echo "2. Verificando triggers existentes...\n";
    
    $triggersResult = $db->query("
        SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE 
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = 'erp_local'
        ORDER BY EVENT_OBJECT_TABLE, EVENT_MANIPULATION
    ");
    
    $existingTriggers = [];
    if ($triggersResult && $triggersResult->num_rows > 0) {
        while ($trigger = $triggersResult->fetch_assoc()) {
            $existingTriggers[] = $trigger;
            echo "  - {$trigger['TRIGGER_NAME']} ({$trigger['EVENT_MANIPULATION']}) em {$trigger['EVENT_OBJECT_TABLE']}\n";
        }
    } else {
        echo "  Nenhum trigger encontrado\n";
    }
    
    echo "\n3. Analisando necessidades de proteção...\n";
    
    $criticalTables = [
        'pos_sales' => 'Vendas do PDV',
        'pos_sale_items' => 'Itens de vendas',
        'pos_sale_payments' => 'Pagamentos de vendas',
        'produtos' => 'Produtos',
        'clientes' => 'Clientes',
        'fornecedores' => 'Fornecedores',
        'usuarios' => 'Usuários',
        'cash_registers' => 'Caixas registradoras',
        'cash_movements' => 'Movimentos de caixa',
        'inventory_movements' => 'Movimentos de estoque',
        'shifts' => 'Turnos',
        'configuracoes' => 'Configurações',
        'empresas' => 'Empresas'
    ];
    
    $needsProtection = [];
    $missingTables = [];
    
    foreach ($criticalTables as $table => $description) {
        if (isset($multiTenantTables[$table])) {
            $needsProtection[$table] = [
                'description' => $description,
                'has_id_contador' => $multiTenantTables[$table]['has_id_contador'],
                'has_id_empresa' => $multiTenantTables[$table]['has_id_empresa'],
                'columns' => $multiTenantTables[$table]['columns']
            ];
            echo "  ✓ {$table} - {$description}\n";
        } else {
            $missingTables[] = $table;
            echo "  ✗ {$table} - {$description} (NÃO ENCONTRADA)\n";
        }
    }
    
    echo "\n4. Resumo da análise...\n";
    echo "Tabelas que precisam de proteção: " . count($needsProtection) . "\n";
    echo "Tabelas não encontradas: " . count($missingTables) . "\n";
    echo "Triggers existentes: " . count($existingTriggers) . "\n";
    
    // Salvar análise em arquivo JSON para uso posterior
    $analysis = [
        'multi_tenant_tables' => $multiTenantTables,
        'critical_tables' => $needsProtection,
        'missing_tables' => $missingTables,
        'existing_triggers' => $existingTriggers,
        'analysis_date' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents('database_analysis.json', json_encode($analysis, JSON_PRETTY_PRINT));
    echo "\n✓ Análise salva em database_analysis.json\n";
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro na análise: " . $e->getMessage() . "\n";
}

echo "\n=== ANÁLISE CONCLUÍDA ===\n";
