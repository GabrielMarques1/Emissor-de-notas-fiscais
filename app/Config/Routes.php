<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

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