<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Verificação de usuário existente (para fluxo de cadastro/Stripe)
$routes->post('login/verificaUsuario', 'Login::verificaUsuario');

// Rotas de cobrança - apenas ADMIN (tipo 1) terá acesso via verificaPermissaoDeAcesso no controller
$routes->group('cobranca', static function($routes) {
	$routes->get('gerar', 'Cobranca::gerarCobrancasMensais');
	$routes->get('verificar', 'Cobranca::verificarInadimplencia');
	$routes->get('bloquear/(:num)', 'Cobranca::bloquearEmpresa/$1');
	$routes->get('desbloquear/(:num)', 'Cobranca::desbloquearEmpresa/$1');
	$routes->get('bloquear-contador/(:num)', 'Cobranca::bloquearContador/$1');
	$routes->get('desbloquear-contador/(:num)', 'Cobranca::desbloquearContador/$1');
	$routes->get('minhas', 'Cobranca::minhasCobrancasEmpresa');
	$routes->get('empresas', 'Cobranca::minhasCobrancasContador');
	$routes->get('admin', 'Cobranca::adminLista');
});

// Rotas Stripe
$routes->group('stripe', static function($routes) {
	$routes->post('checkout', 'Stripe::createCheckoutSession');
	$routes->post('portal', 'Stripe::createPortalSession');
	$routes->post('webhook', 'Stripe::webhook');
	$routes->get('success', 'Inicio::emissor');
	$routes->get('cancel', 'Inicio::emissor');
});

// Proteger o emissor com filtro de assinatura
$routes->group('inicio', ['filter' => 'subscription'], static function($routes) {
	$routes->get('emissor', 'Inicio::emissor');
});

// Página pública de planos/checkout
$routes->get('planos', 'Inicio::planos');

// Empresa <-> Contador
$routes->group('empresa', static function($routes) {
	$routes->post('adicionar-contador', 'EmpresaContador::adicionarContador');
});

// Auth - recuperação de senha
$routes->group('auth', static function($routes) {
	$routes->match(['get','post'], 'forgot', 'Auth\Password::forgot');
	$routes->match(['get','post'], 'reset/(:segment)', 'Auth\Password::reset/$1');
});

// PDV - protegido por assinatura
$routes->group('pdv', ['filter' => 'subscription'], static function($routes) {
	$routes->get('/', 'PDV::index');
	$routes->get('adicionar', 'PDV::adicionar');
	$routes->get('remover/(:num)', 'PDV::remover/$1');
	$routes->get('limpar', 'PDV::limpar');
	$routes->get('finalizar', 'PDV::finalizar');
	$routes->get('buscar-por-barras/(:any)', 'PDV::buscarPorBarras/$1');
});

// API do PDV - RESTful resources
$routes->group('api', ['namespace' => 'App\\Controllers\\Api', 'filter' => 'subscription|pdvaccess|apithrottle'], static function($routes) {
	$routes->resource('pos', ['controller' => 'Pos']);
	$routes->resource('cash-registers', ['controller' => 'CashRegisters']);
	$routes->resource('shifts', ['controller' => 'Shifts']);
	$routes->get('shifts/(:num)/report', 'Shifts::report/$1');
	$routes->post('pos/(:num)/finalize', 'Pos::finalize/$1');
	$routes->post('pos/(:num)/cancel', 'Pos::cancel/$1');
	$routes->get('pos/(:num)/receipt', 'Pos::receipt/$1');
	$routes->get('pos/(:num)/receipt/html', 'Pos::receiptHtml/$1');
	$routes->get('pos/active', 'Pos::active');
	$routes->get('products/barcode/(:any)', 'Products::barcode/$1');
	$routes->get('cart', 'Cart::index');
	$routes->post('cart', 'Cart::create');
	$routes->delete('cart/(:num)', 'Cart::delete/$1');
	$routes->delete('cart', 'Cart::clear');
});