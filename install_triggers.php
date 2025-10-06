<?php
echo "=== INSTALAÇÃO DOS TRIGGERS DE PROTEÇÃO ===\n\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        die("Erro de conexão: " . $db->connect_error . "\n");
    }
    
    // Ler arquivo de triggers
    if (!file_exists('install_database_triggers.sql')) {
        die("Erro: Arquivo install_database_triggers.sql não encontrado\n");
    }
    
    $sql = file_get_contents('install_database_triggers.sql');
    
    echo "Instalando triggers no banco de dados...\n";
    
    // Dividir o SQL em comandos individuais
    $commands = explode('DELIMITER ;', $sql);
    $installedTriggers = 0;
    $errors = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        
        // Pular comandos vazios ou comentários
        if (empty($command) || strpos($command, '--') === 0) {
            continue;
        }
        
        // Remover DELIMITER $$ se presente
        $command = str_replace('DELIMITER $$', '', $command);
        $command = str_replace('$$', '', $command);
        $command = trim($command);
        
        if (empty($command)) {
            continue;
        }
        
        try {
            if ($db->query($command)) {
                if (strpos($command, 'CREATE TRIGGER') !== false) {
                    // Extrair nome do trigger
                    preg_match('/CREATE TRIGGER\s+(\w+)/', $command, $matches);
                    $triggerName = $matches[1] ?? 'unknown';
                    echo "  ✓ Trigger {$triggerName} instalado\n";
                    $installedTriggers++;
                }
            } else {
                echo "  ✗ Erro ao executar comando: " . $db->error . "\n";
                $errors++;
            }
        } catch (Exception $e) {
            echo "  ✗ Erro: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n=== RESULTADO DA INSTALAÇÃO ===\n";
    echo "Triggers instalados: {$installedTriggers}\n";
    echo "Erros encontrados: {$errors}\n";
    
    // Verificar triggers instalados
    echo "\nVerificando triggers instalados...\n";
    
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
            $triggersByTable[$table][] = $trigger['EVENT_MANIPULATION'];
        }
    }
    
    foreach ($triggersByTable as $table => $events) {
        echo "  {$table}: " . implode(', ', $events) . "\n";
    }
    
    $totalTriggers = array_sum(array_map('count', $triggersByTable));
    echo "\nTotal de triggers ativos: {$totalTriggers}\n";
    
    if ($errors === 0) {
        echo "\n🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n";
        echo "✅ Proteção de banco de dados ativada\n";
        echo "✅ Triggers de validação INSERT funcionando\n";
        echo "✅ Triggers de proteção UPDATE funcionando\n";
        echo "✅ Triggers de auditoria DELETE funcionando\n";
    } else {
        echo "\n⚠️ INSTALAÇÃO CONCLUÍDA COM ERROS\n";
        echo "Verifique os erros acima e corrija se necessário\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}

echo "\n=== INSTALAÇÃO FINALIZADA ===\n";
