<?php
echo "=== REFATORAÇÃO COMPLETA - SEGURANÇA CRÍTICA 1 & 2 ===\n\n";

// 1. Analisar controllers API existentes
echo "1. Analisando controllers API...\n";

$controllersDir = 'app/Controllers/Api/';
$controllers = [];

if (is_dir($controllersDir)) {
    $files = scandir($controllersDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $controllers[] = $file;
        }
    }
}

echo "Controllers encontrados: " . count($controllers) . "\n";
foreach ($controllers as $controller) {
    echo "  - {$controller}\n";
}
echo "\n";

// 2. Analisar quais controllers precisam de refatoração
echo "2. Analisando necessidade de refatoração...\n";

$criticalControllers = [
    'Products.php' => ['show', 'update', 'delete', 'create'],
    'Pos.php' => ['show', 'update', 'delete'], // Já refatorado
    'Settings.php' => ['show', 'update', 'delete'],
    'Shifts.php' => ['show', 'update', 'delete', 'close'],
    'CashMovements.php' => ['show', 'update', 'delete'],
    'Cart.php' => ['show', 'update', 'delete'],
    'Caixa.php' => ['show', 'update', 'delete']
];

$refactoredControllers = [];
$pendingControllers = [];

foreach ($criticalControllers as $controller => $methods) {
    $filePath = $controllersDir . $controller;
    
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        // Verificar se já tem validação de ownership
        if (strpos($content, 'validateOwnershipOrFail') !== false) {
            $refactoredControllers[] = $controller;
            echo "  ✓ {$controller}: JÁ REFATORADO\n";
        } else {
            $pendingControllers[] = $controller;
            echo "  ✗ {$controller}: PRECISA REFATORAR\n";
            
            // Verificar quais métodos existem
            foreach ($methods as $method) {
                if (strpos($content, "public function {$method}") !== false) {
                    echo "    - Método {$method}(): ENCONTRADO\n";
                } else {
                    echo "    - Método {$method}(): NÃO ENCONTRADO\n";
                }
            }
        }
    } else {
        echo "  ⚠ {$controller}: ARQUIVO NÃO ENCONTRADO\n";
    }
}

echo "\n";

// 3. Verificar BaseAppModel
echo "3. Verificando BaseAppModel...\n";

if (file_exists('app/Models/BaseAppModel.php')) {
    $baseModelContent = file_get_contents('app/Models/BaseAppModel.php');
    
    $features = [
        'enforceTenant' => 'Enforcement de tenant automático',
        'applyTenantOnFind' => 'Filtro automático em find',
        'applyTenantOnInsert' => 'Tenant automático em insert',
        'findOptimized' => 'Busca otimizada com cache',
        'findMultipleOptimized' => 'Busca múltipla otimizada'
    ];
    
    foreach ($features as $feature => $description) {
        if (strpos($baseModelContent, $feature) !== false) {
            echo "  ✓ {$description}\n";
        } else {
            echo "  ✗ {$description}: FALTANDO\n";
        }
    }
} else {
    echo "  ✗ BaseAppModel não encontrado\n";
}
echo "\n";

// 4. Verificar Models críticos
echo "4. Verificando models críticos...\n";

$criticalModels = [
    'ProdutoModel.php',
    'ClienteModel.php', 
    'PosSaleModel.php',
    'PosSaleItemModel.php',
    'PosSalePaymentModel.php',
    'CashRegisterModel.php',
    'ShiftModel.php'
];

$modelsDir = 'app/Models/';
foreach ($criticalModels as $model) {
    $filePath = $modelsDir . $model;
    
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        if (strpos($content, 'extends BaseAppModel') !== false) {
            echo "  ✓ {$model}: Herda de BaseAppModel\n";
        } else {
            echo "  ✗ {$model}: NÃO herda de BaseAppModel\n";
        }
    } else {
        echo "  ⚠ {$model}: Não encontrado\n";
    }
}
echo "\n";

// 5. Verificar TenantFilter
echo "5. Verificando TenantFilter...\n";

if (file_exists('app/Filters/TenantFilter.php')) {
    $filterContent = file_get_contents('app/Filters/TenantFilter.php');
    
    $features = [
        'isMasterUser' => 'Detecção de usuário master',
        'validateTenantStatus' => 'Validação de status do tenant',
        'checkTenantQuota' => 'Verificação de quotas',
        'checkRateLimit' => 'Rate limiting',
        'logSecurityViolation' => 'Log de violações'
    ];
    
    foreach ($features as $feature => $description) {
        if (strpos($filterContent, $feature) !== false) {
            echo "  ✓ {$description}\n";
        } else {
            echo "  ✗ {$description}: FALTANDO\n";
        }
    }
} else {
    echo "  ✗ TenantFilter não encontrado\n";
}
echo "\n";

// 6. Verificar configuração de filtros
echo "6. Verificando configuração de filtros...\n";

if (file_exists('app/Config/Filters.php')) {
    $filtersContent = file_get_contents('app/Config/Filters.php');
    
    // Contar rotas protegidas
    preg_match_all("/\s*'([^']+\*?)'/", $filtersContent, $matches);
    $protectedRoutes = array_filter($matches[1], function($route) {
        return strpos($route, '*') !== false || in_array($route, ['api', 'pos', 'dashboard']);
    });
    
    echo "  ✓ Rotas protegidas encontradas: " . count($protectedRoutes) . "\n";
    
    if (strpos($filtersContent, "'tenant'") !== false) {
        echo "  ✓ TenantFilter registrado\n";
    } else {
        echo "  ✗ TenantFilter NÃO registrado\n";
    }
    
    // Verificar exceções
    if (strpos($filtersContent, 'except') !== false) {
        echo "  ✓ Exceções configuradas\n";
    } else {
        echo "  ✗ Exceções NÃO configuradas\n";
    }
} else {
    echo "  ✗ Arquivo de configuração de filtros não encontrado\n";
}
echo "\n";

// 7. Verificar helper de tenant
echo "7. Verificando helper de tenant...\n";

if (file_exists('app/Helpers/tenant_helper.php')) {
    $helperContent = file_get_contents('app/Helpers/tenant_helper.php');
    
    $functions = [
        'validateOwnership',
        'validateOwnershipOrFail',
        'isMasterUser',
        'logOwnershipViolation',
        'getCurrentTenantData',
        'addTenantToQuery'
    ];
    
    $implementedFunctions = 0;
    foreach ($functions as $function) {
        if (strpos($helperContent, "function {$function}") !== false) {
            echo "  ✓ {$function}()\n";
            $implementedFunctions++;
        } else {
            echo "  ✗ {$function}(): FALTANDO\n";
        }
    }
    
    echo "  Total: {$implementedFunctions}/" . count($functions) . " funções implementadas\n";
} else {
    echo "  ✗ Helper de tenant não encontrado\n";
}
echo "\n";

// 8. Verificar tabelas de auditoria
echo "8. Verificando tabelas de auditoria...\n";

try {
    $db = new mysqli('localhost', 'root', '', 'erp_local');
    
    if ($db->connect_error) {
        echo "  ✗ Erro de conexão: " . $db->connect_error . "\n";
    } else {
        // Verificar security_audit
        $result = $db->query("SHOW TABLES LIKE 'security_audit'");
        if ($result && $result->num_rows > 0) {
            echo "  ✓ Tabela security_audit: EXISTE\n";
            
            // Contar registros
            $result = $db->query("SELECT COUNT(*) as total FROM security_audit");
            $row = $result->fetch_assoc();
            echo "    - Registros de auditoria: " . $row['total'] . "\n";
        } else {
            echo "  ✗ Tabela security_audit: NÃO EXISTE\n";
        }
        
        // Verificar outbox_events
        $result = $db->query("SHOW TABLES LIKE 'outbox_events'");
        if ($result && $result->num_rows > 0) {
            echo "  ✓ Tabela outbox_events: EXISTE\n";
        } else {
            echo "  ✗ Tabela outbox_events: NÃO EXISTE\n";
        }
    }
    
    $db->close();
} catch (Exception $e) {
    echo "  ✗ Erro ao verificar banco: " . $e->getMessage() . "\n";
}
echo "\n";

// 9. Resumo e plano de ação
echo "=== RESUMO DA ANÁLISE ===\n";
echo "Controllers refatorados: " . count($refactoredControllers) . "\n";
echo "Controllers pendentes: " . count($pendingControllers) . "\n";

if (!empty($pendingControllers)) {
    echo "\nControllers que precisam ser refatorados:\n";
    foreach ($pendingControllers as $controller) {
        echo "  - {$controller}\n";
    }
}

echo "\n=== PLANO DE REFATORAÇÃO ===\n";
echo "1. Refatorar controllers pendentes com validateOwnershipOrFail\n";
echo "2. Garantir que todos os models herdem de BaseAppModel\n";
echo "3. Verificar se TenantFilter está aplicado em todas as rotas\n";
echo "4. Implementar testes de segurança para controllers refatorados\n";
echo "5. Executar auditoria de integridade\n";

echo "\n🔧 REFATORAÇÃO NECESSÁRIA IDENTIFICADA!\n";
echo "===============================================\n";
