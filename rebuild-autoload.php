<?php
/**
 * Script para recompilar o autoload manualmente
 * Uso: C:\xampp\php\php.exe rebuild-autoload.php
 */

echo "============================================\n";
echo "  RECOMPILANDO AUTOLOAD\n";
echo "============================================\n\n";

// Verificar se composer está disponível
$composerPaths = [
    'composer',
    'composer.phar',
    'C:\\ProgramData\\ComposerSetup\\bin\\composer.bat',
    'C:\\Users\\' . get_current_user() . '\\AppData\\Roaming\\Composer\\vendor\\bin\\composer',
];

$composerFound = false;
$composerCmd = null;

foreach ($composerPaths as $path) {
    if (is_executable($path) || file_exists($path)) {
        echo "[INFO] Composer encontrado: $path\n";
        $composerCmd = $path;
        $composerFound = true;
        break;
    }
}

if (!$composerFound) {
    echo "[INFO] Composer não encontrado no PATH\n";
    echo "[INFO] Tentando alternativa manual...\n\n";
    
    // Alternativa: usar o autoload do CodeIgniter
    $autoloadFile = __DIR__ . '/vendor/autoload.php';
    
    if (!file_exists($autoloadFile)) {
        echo "[ERRO] vendor/autoload.php não encontrado!\n";
        exit(1);
    }
    
    // Verificar se a classe está no lugar certo
    $testFile = __DIR__ . '/tests/Support/MultiTenantTestCase.php';
    
    if (!file_exists($testFile)) {
        echo "[ERRO] tests/Support/MultiTenantTestCase.php não encontrado!\n";
        exit(1);
    }
    
    echo "[OK] Arquivo encontrado: tests/Support/MultiTenantTestCase.php\n";
    
    // Verificar composer.json
    $composerJson = __DIR__ . '/composer.json';
    $config = json_decode(file_get_contents($composerJson), true);
    
    if (isset($config['autoload-dev']['psr-4']['Tests\\Support\\'])) {
        echo "[OK] composer.json configurado: Tests\\Support\\ => tests/Support/\n";
    } else {
        echo "[ERRO] composer.json NÃO está configurado corretamente!\n";
        exit(1);
    }
    
    echo "\n[SOLUÇÃO ALTERNATIVA]\n";
    echo "Vou criar um autoload customizado...\n\n";
    
    // Criar autoload customizado
    $customAutoload = __DIR__ . '/tests/bootstrap-custom.php';
    
    $content = <<<'PHP'
<?php
/**
 * Autoload customizado para testes multi-tenant
 * Incluir em tests/bootstrap.php ou nos testes individuais
 */

// Registrar autoloader customizado para Tests\Support
spl_autoload_register(function ($class) {
    // Apenas para namespace Tests\Support
    if (strpos($class, 'Tests\\Support\\') === 0) {
        $file = __DIR__ . '/Support/' . str_replace('\\', '/', substr($class, 14)) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

PHP;
    
    file_put_contents($customAutoload, $content);
    echo "[OK] Criado: tests/bootstrap-custom.php\n";
    
    // Atualizar tests/bootstrap.php
    $bootstrapFile = __DIR__ . '/tests/bootstrap.php';
    
    if (file_exists($bootstrapFile)) {
        $bootstrap = file_get_contents($bootstrapFile);
        
        if (strpos($bootstrap, 'bootstrap-custom.php') === false) {
            $bootstrap .= "\n\n// Autoload customizado para Tests\\Support\nrequire __DIR__ . '/bootstrap-custom.php';\n";
            file_put_contents($bootstrapFile, $bootstrap);
            echo "[OK] Atualizado: tests/bootstrap.php\n";
        } else {
            echo "[OK] tests/bootstrap.php já está atualizado\n";
        }
    }
    
    echo "\n[SUCESSO] Autoload configurado!\n";
    echo "\nTente executar os testes novamente:\n";
    echo "  C:\\xampp\\php\\php.exe vendor/bin/phpunit --testdox\n\n";
    
} else {
    // Composer encontrado - usar normalmente
    echo "[INFO] Executando: $composerCmd dump-autoload\n\n";
    
    $output = [];
    $returnVar = 0;
    exec("$composerCmd dump-autoload 2>&1", $output, $returnVar);
    
    foreach ($output as $line) {
        echo $line . "\n";
    }
    
    if ($returnVar === 0) {
        echo "\n[SUCESSO] Autoload recompilado!\n";
    } else {
        echo "\n[ERRO] Falha ao recompilar autoload\n";
        exit(1);
    }
}

echo "\n============================================\n";
echo "  CONCLUÍDO\n";
echo "============================================\n";

