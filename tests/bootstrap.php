<?php

/**
 * PHPUnit Bootstrap para testes do PDV Multi-Tenant
 */

// Define o caminho do projeto
define('ROOTPATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);

// Carregar autoloader do Composer
require ROOTPATH . 'vendor/autoload.php';

// Autoload customizado para Tests\Support
require __DIR__ . '/bootstrap-custom.php';

// Definir ambiente de teste
$_SERVER['CI_ENVIRONMENT'] = 'testing';
putenv('CI_ENVIRONMENT=testing');

// Bootstrap do sistema CodeIgniter
require ROOTPATH . 'system/Test/bootstrap.php';
