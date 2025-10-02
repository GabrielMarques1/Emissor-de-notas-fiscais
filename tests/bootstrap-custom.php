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
