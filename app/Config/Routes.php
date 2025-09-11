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

// Rota de preços aberta
$routes->get('precos', 'Site::precos');

// Aplica filtro de assinatura a rotas críticas
$routes->group('', ['filter' => 'subscription'], static function($routes) {
	$routes->get('inicio/emissor', 'Inicio::emissor');
	$routes->group('nfce', static function($routes) {
		$routes->get('/', 'NFCe::index');
		$routes->get('emitir', 'NFCe::emitir');
		$routes->post('emitir', 'NFCe::emitir');
		$routes->get('imprimir/(:num)', 'NFCe::imprimir/$1');
	});
	$routes->group('nfe', static function($routes) {
		$routes->get('/', 'NFe::index');
		$routes->get('emitir', 'NFe::emitir');
		$routes->post('emitir', 'NFe::emitir');
		$routes->get('imprimir/(:num)', 'NFe::imprimir/$1');
	});
	$routes->group('produtos', static function($routes) {
		$routes->get('/', 'Produtos::index');
		$routes->get('form', 'Produtos::form');
		$routes->post('form', 'Produtos::form');
		$routes->get('show/(:num)', 'Produtos::show/$1');
	});
	$routes->group('clientes', static function($routes) {
		$routes->get('/', 'Clientes::index');
		$routes->get('form', 'Clientes::form');
		$routes->post('form', 'Clientes::form');
		$routes->get('show/(:num)', 'Clientes::show/$1');
	});
	$routes->group('fornecedores', static function($routes) {
		$routes->get('/', 'Fornecedores::index');
		$routes->get('form', 'Fornecedores::form');
		$routes->post('form', 'Fornecedores::form');
		$routes->get('show/(:num)', 'Fornecedores::show/$1');
	});
	$routes->group('transportadoras', static function($routes) {
		$routes->get('/', 'Transportadoras::index');
		$routes->get('form', 'Transportadoras::form');
		$routes->post('form', 'Transportadoras::form');
		$routes->get('show/(:num)', 'Transportadoras::show/$1');
	});
	$routes->group('relatorios', static function($routes) {
		$routes->get('/', 'Relatorios::index');
		$routes->get('nfe', 'Relatorios::nfe');
		$routes->get('nfce', 'Relatorios::nfce');
	});
	$routes->group('configuracoes', static function($routes) {
		$routes->get('/', 'Configuracoes::index');
		$routes->post('/', 'Configuracoes::index');
	});
});