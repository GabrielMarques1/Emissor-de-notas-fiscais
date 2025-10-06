<?php
echo "=== INSTALAÇÃO CORRIGIDA DOS TRIGGERS ===\n\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    echo "Conectado ao banco de dados MySQL\n";
    
    // Ler arquivo de triggers
    if (!file_exists('install_database_triggers.sql')) {
        die("Erro: Arquivo install_database_triggers.sql não encontrado\n");
    }
    
    $sqlContent = file_get_contents('install_database_triggers.sql');
    
    // Dividir em triggers individuais
    $triggers = [];
    $lines = explode("\n", $sqlContent);
    $currentTrigger = '';
    $inTrigger = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Pular comentários e linhas vazias
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // Detectar início de trigger
        if (strpos($line, 'CREATE TRIGGER') !== false) {
            $inTrigger = true;
            $currentTrigger = $line . "\n";
            continue;
        }
        
        // Detectar fim de trigger
        if ($inTrigger && (strpos($line, 'END$$') !== false || strpos($line, 'END;') !== false)) {
            $currentTrigger .= $line;
            $triggers[] = $currentTrigger;
            $currentTrigger = '';
            $inTrigger = false;
            continue;
        }
        
        // Adicionar linha ao trigger atual
        if ($inTrigger) {
            $currentTrigger .= $line . "\n";
        }
    }
    
    echo "Encontrados " . count($triggers) . " triggers para instalar\n\n";
    
    $installedCount = 0;
    $errorCount = 0;
    
    // Instalar cada trigger individualmente
    foreach ($triggers as $index => $triggerSql) {
        // Limpar SQL
        $triggerSql = str_replace('DELIMITER $$', '', $triggerSql);
        $triggerSql = str_replace('DELIMITER ;', '', $triggerSql);
        $triggerSql = str_replace('$$', '', $triggerSql);
        $triggerSql = trim($triggerSql);
        
        if (empty($triggerSql)) {
            continue;
        }
        
        // Extrair nome do trigger
        preg_match('/CREATE TRIGGER\s+(\w+)/', $triggerSql, $matches);
        $triggerName = $matches[1] ?? "trigger_" . ($index + 1);
        
        echo "Instalando: {$triggerName}... ";
        
        // Remover trigger se existir
        $db->query("DROP TRIGGER IF EXISTS {$triggerName}");
        
        // Instalar trigger
        if ($db->query($triggerSql)) {
            echo "✓ OK\n";
            $installedCount++;
        } else {
            echo "✗ ERRO: " . $db->error . "\n";
            $errorCount++;
        }
    }
    
    echo "\n=== RESULTADO ===\n";
    echo "Triggers instalados: {$installedCount}\n";
    echo "Erros: {$errorCount}\n";
    
    // Verificar triggers instalados
    echo "\nVerificando triggers no banco...\n";
    $result = $db->query("
        SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE 
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = 'erp_local'
        ORDER BY EVENT_OBJECT_TABLE, EVENT_MANIPULATION
    ");
    
    $triggersByTable = [];
    if ($result && $result->num_rows > 0) {
        while ($trigger = $result->fetch_assoc()) {
            $table = $trigger['EVENT_OBJECT_TABLE'];
            if (!isset($triggersByTable[$table])) {
                $triggersByTable[$table] = [];
            }
            $triggersByTable[$table][] = $trigger['EVENT_MANIPULATION'] . ' (' . $trigger['TRIGGER_NAME'] . ')';
        }
    }
    
    if (!empty($triggersByTable)) {
        echo "\nTriggers ativos por tabela:\n";
        foreach ($triggersByTable as $table => $events) {
            echo "  {$table}:\n";
            foreach ($events as $event) {
                echo "    - {$event}\n";
            }
        }
    } else {
        echo "Nenhum trigger encontrado no banco\n";
    }
    
    $totalTriggers = array_sum(array_map('count', $triggersByTable));
    echo "\nTotal de triggers ativos: {$totalTriggers}\n";
    
    if ($installedCount > 0) {
        echo "\n🎉 INSTALAÇÃO CONCLUÍDA!\n";
        echo "✅ {$installedCount} triggers de proteção instalados\n";
        echo "✅ Proteção multi-tenant ativada no banco de dados\n";
        
        if ($errorCount > 0) {
            echo "⚠️ {$errorCount} triggers falharam - verifique os erros acima\n";
        }
    } else {
        echo "\n❌ NENHUM TRIGGER FOI INSTALADO\n";
        echo "Verifique os erros e a estrutura do arquivo SQL\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}

echo "\n=== INSTALAÇÃO FINALIZADA ===\n";
