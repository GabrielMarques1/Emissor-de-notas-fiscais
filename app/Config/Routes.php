<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// ==================== ROTAS ADMINISTRATIVAS ====================
$routes->group('admin', function($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('contadores', 'Admin::contadores');
    $routes->get('novoContador', 'Admin::novoContador');
    $routes->get('editarContador/(:num)', 'Admin::editarContador/$1');
    $routes->post('salvarContador', 'Admin::salvarContador');
    $routes->get('alterarStatus/(:num)', 'Admin::alterarStatus/$1');
    $routes->get('excluirContador/(:num)', 'Admin::excluirContador/$1');
    $routes->get('verEmpresas/(:num)', 'Admin::verEmpresas/$1');
});

// ==================== ROTAS DE LOGIN ====================
$routes->get('login', 'Login::index');
$routes->post('login/autenticar', 'Login::autenticar');
$routes->get('logout', 'Login::logout');
$routes->post('login/verificaUsuario', 'Login::verificaUsuario');

// ==================== ROTAS DE INÍCIO ====================
$routes->get('inicio/admin', 'Inicio::admin');
$routes->get('inicio/contador', 'Inicio::contador');
$routes->get('inicio/emissor', 'Inicio::emissor');

// ==================== ROTAS DE EMPRESAS (CONTADOR) ====================
$routes->group('empresas', function($routes) {
    $routes->get('/', 'Empresas::index');
    $routes->get('create', 'Empresas::create');
    $routes->get('show/(:num)', 'Empresas::show/$1');
    $routes->get('edit/(:num)', 'Empresas::edit/$1');
    $routes->post('store', 'Empresas::store');
    $routes->get('delete/(:num)', 'Empresas::delete/$1');
    $routes->get('baixarCertificado/(:any)', 'Empresas::baixarCertificado/$1');
    $routes->post('trocarCertificado', 'Empresas::trocarCertificado');
    $routes->get('listaXMLsNFe/(:num)', 'Empresas::listaXMLsNFe/$1');
    $routes->get('listaXMLsNFCe/(:num)', 'Empresas::listaXMLsNFCe/$1');
    
    // Pagamentos
    $routes->get('novoPagamento/(:num)', 'Empresas::novoPagamento/$1');
    $routes->get('editPagamento/(:num)/(:num)', 'Empresas::editPagamento/$1/$2');
    $routes->post('storePagamento/(:num)', 'Empresas::storePagamento/$1');
    $routes->get('deletePagamento/(:num)/(:num)', 'Empresas::deletePagamento/$1/$2');
});

// ==================== ROTAS DE CLIENTES ====================
$routes->group('clientes', function($routes) {
    $routes->get('/', 'Clientes::index');
    $routes->get('show/(:num)', 'Clientes::show/$1');
    $routes->get('create', 'Clientes::create');
    $routes->get('edit/(:num)', 'Clientes::edit/$1');
    $routes->post('store', 'Clientes::store');
    $routes->get('delete/(:num)', 'Clientes::delete/$1');
});

// ==================== ROTAS DE PRODUTOS ====================
$routes->group('produtos', function($routes) {
    $routes->get('/', 'Produtos::index');
    $routes->get('show/(:num)', 'Produtos::show/$1');
    $routes->get('create', 'Produtos::create');
    $routes->get('edit/(:num)', 'Produtos::edit/$1');
    $routes->post('store', 'Produtos::store');
    $routes->get('delete/(:num)', 'Produtos::delete/$1');
    $routes->post('addPorCSV', 'Produtos::addPorCSV');
});

// ==================== ROTAS DE NFe ====================
$routes->group('nfe', function($routes) {
    $routes->get('baixarXML/(:num)', 'NFe::baixarXML/$1');
    $routes->get('baixarXmlContador/(:num)/(:num)', 'NFe::baixarXmlContador/$1/$2');
    $routes->get('baixaXMLS/(:any)/(:any)', 'NFe::baixaXMLS/$1/$2');
    $routes->get('baixaXMLsContador/(:any)/(:any)/(:num)', 'NFe::baixaXMLsContador/$1/$2/$3');
    $routes->post('emitirNotaDeSaida', 'NFe::emitirNotaDeSaida');
    $routes->post('emitirNotaDeEntrada', 'NFe::emitirNotaDeEntrada');
    $routes->post('emitirNotaDeDevolucao', 'NFe::emitirNotaDeDevolucao');
    $routes->post('cancelar', 'NFe::cancelar');
});

// ==================== ROTAS DE NFCe ====================
$routes->group('nfce', function($routes) {
    $routes->get('baixarXML/(:num)', 'NFCe::baixarXML/$1');
    $routes->get('baixarXmlContador/(:num)/(:num)', 'NFCe::baixarXmlContador/$1/$2');
    $routes->get('baixaXMLS/(:any)/(:any)', 'NFCe::baixaXMLS/$1/$2');
    $routes->get('baixaXMLsContador/(:any)/(:any)/(:num)', 'NFCe::baixaXMLsContador/$1/$2/$3');
    $routes->post('emitir', 'NFCe::emitir');
    $routes->post('cancelar', 'NFCe::cancelar');
});

// ==================== ROTAS DE NOTAS DE SAÍDA ====================
$routes->group('notaDeSaida', function($routes) {
    $routes->get('emitir', 'NotaDeSaida::emitir');
    $routes->post('adicionaProduto', 'NotaDeSaida::adicionaProduto');
    $routes->post('alteraDadosDoProduto', 'NotaDeSaida::alteraDadosDoProduto');
    $routes->get('removeProduto/(:num)', 'NotaDeSaida::removeProduto/$1');
    $routes->get('preparaEmissao', 'NotaDeSaida::preparaEmissao');
});

// ==================== ROTAS DE NOTAS DE ENTRADA ====================
$routes->group('notaDeEntrada', function($routes) {
    $routes->get('emitir', 'NotaDeEntrada::emitir');
    $routes->post('adicionaProduto', 'NotaDeEntrada::adicionaProduto');
    $routes->post('alteraDadosDoProduto', 'NotaDeEntrada::alteraDadosDoProduto');
    $routes->get('removeProduto/(:num)', 'NotaDeEntrada::removeProduto/$1');
    $routes->get('preparaEmissao', 'NotaDeEntrada::preparaEmissao');
});

// ==================== ROTAS DE NOTAS DE DEVOLUÇÃO ====================
$routes->group('notaDeDevolucao', function($routes) {
    $routes->get('emitir', 'NotaDeDevolucao::emitir');
    $routes->post('adicionaProduto', 'NotaDeDevolucao::adicionaProduto');
    $routes->post('alteraDadosDoProduto', 'NotaDeDevolucao::alteraDadosDoProduto');
    $routes->get('removeProduto/(:num)', 'NotaDeDevolucao::removeProduto/$1');
    $routes->get('preparaEmissao', 'NotaDeDevolucao::preparaEmissao');
});

// ==================== ROTAS AUXILIARES ====================
$routes->get('uf/carregaMunicipios/(:num)', 'UF::carregaMunicipios/$1');
