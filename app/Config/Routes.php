<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Teste de correções (remover após validação)
$routes->get('teste-correcoes', 'TesteCorrecoes::index');
$routes->group('teste-login-pdv', static function($routes) {
	$routes->post('testar', 'TesteLoginPDV::testar');
});
$routes->get('teste-session', 'TesteSession::index');
$routes->get('teste-pdv-access', 'TestePDVAccess::verificar');
$routes->get('pdv-debug', 'PDVDebug::index');
$routes->get('pdv-direct', 'PDV::index'); // PDV sem filtro// Teste ultra simples
$routes->get('simple-test', 'SimpleTest::index');

// Simular login admin para teste
$routes->get('login-admin-test', function() {
    $session = session();
    $session->set([
        'tipo' => 1,
        'usuario' => 'admin_teste',
        'id' => 1,
        'id_contador' => 1,
        'idEmpresa' => 1,
        'logged_in' => true
    ]);
    return 'Login admin simulado! <a href="/admin/backup-dashboard">Testar Dashboard</a>';
}); // PDV super simples

// Testes de Segurança TenantFilter
$routes->group('testsecurity', static function($routes) {
    $routes->get('/', 'TestSecurity::index');           // Deve ser bloqueado sem tenant
    $routes->get('violation', 'TestSecurity::testViolation'); // Teste de violação
    $routes->get('audit', 'TestSecurity::testAudit');   // Verificar logs
    $routes->get('publictest', 'TestSecurity::publicTest'); // Público (não bloqueado)
    $routes->get('session', 'TestSecurity::sessionInfo'); // Verificar sessão
});

// Testes do Sistema de Auditoria
$routes->group('test-audit', static function($routes) {
    $routes->get('/', 'TestAuditSystem::index');        // Teste completo
    $routes->get('helpers', 'TestAuditSystem::testHelpers'); // Teste helpers
    $routes->get('logs', 'TestAuditSystem::showLogs');  // Mostrar logs JSON
    $routes->get('clear', 'TestAuditSystem::clearTestLogs'); // Limpar logs
});

// ==================== CRON JOBS (HTTP) ==================== //
// Para hospedagens sem acesso a cron via SSH
// Configure webcron em: easycron.com, cron-job.org, etc
$routes->group('cron', static function($routes) {
	$routes->get('process-reports', 'Cron::processReports');
	$routes->get('status', 'Cron::status');
});
// =========================================================== //

// Login PDV (específico para caixas)
$routes->group('login-pdv', static function($routes) {
	$routes->get('/', 'LoginPDV::index');
	$routes->post('autenticar', 'LoginPDV::autenticar');
	$routes->get('logout', 'LoginPDV::logout');
	$routes->get('verificar-sessao', 'LoginPDV::verificarSessao');
});

// Painel da Empresa (para gerentes/donos - tipo 3)
$routes->group('painel/empresa', ['filter' => 'auth'], static function($routes) {
	$routes->get('/', 'PainelEmpresa::index');
	$routes->get('pdv', 'PainelEmpresa::pdv');
});

// Gestão de Usuários Caixa (apenas para gerentes)
$routes->group('usuarios-caixa', ['filter' => 'auth'], static function($routes) {
	$routes->get('/', 'UsuariosCaixa::index');
	$routes->match(['get','post'], 'criar', 'UsuariosCaixa::criar');
	$routes->match(['get','post'], 'editar/(:num)', 'UsuariosCaixa::editar/$1');
	$routes->delete('excluir/(:num)', 'UsuariosCaixa::excluir/$1');
});

// Verificação de usuário existente (para fluxo de cadastro/Stripe)
$routes->post('login/verificaUsuario', 'Login::verificaUsuario');

// Rotas para NFe e NFCe
$routes->get('nfe', 'Emissor::listaXMLsNFe');
$routes->get('nfce', 'Emissor::listaXMLsNFCe');

// Redirect para relatórios (atalho)
$routes->get('relatorios', function() {
    return redirect()->to('/relatorios-empresa');
});

// Rotas de Relatórios para Empresas (tipo 3)
$routes->group('relatorios-empresa', ['filter' => 'auth'], static function($routes) {
	$routes->get('/', 'RelatoriosEmpresa::index');
	$routes->get('vendas', 'RelatoriosEmpresa::vendas');
	$routes->get('produtos', 'RelatoriosEmpresa::produtos');
	$routes->get('turnos', 'RelatoriosEmpresa::turnos');
	$routes->get('fiscal', 'RelatoriosEmpresa::fiscal');
	$routes->get('comparativo', 'RelatoriosEmpresa::comparativo');
	$routes->get('evolucao', 'RelatoriosEmpresa::evolucao');
	$routes->get('clientes', 'RelatoriosEmpresa::clientes');
	$routes->get('alertas-estoque', 'RelatoriosEmpresa::alertasEstoque');
	$routes->get('agendamentos', 'RelatoriosEmpresa::agendamentos');
	$routes->post('agendamentos/salvar', 'RelatoriosEmpresa::salvarAgendamento');
	$routes->get('agendamentos/excluir/(:num)', 'RelatoriosEmpresa::excluirAgendamento/$1');
	$routes->match(['get','post'], 'customizar', 'RelatoriosEmpresa::customizarDashboard');
	// Exportações
	$routes->get('exportar-vendas-excel', 'RelatoriosEmpresa::exportarVendasExcel');
	$routes->get('exportar-vendas-pdf', 'RelatoriosEmpresa::exportarVendasPDF');
	$routes->get('exportar-produtos-excel', 'RelatoriosEmpresa::exportarProdutosExcel');
	$routes->get('exportar-produtos-pdf', 'RelatoriosEmpresa::exportarProdutosPDF');
	$routes->get('exportar-turnos-excel', 'RelatoriosEmpresa::exportarTurnosExcel');
	$routes->get('exportar-turnos-pdf', 'RelatoriosEmpresa::exportarTurnosPDF');
	$routes->get('exportar-fiscal-excel', 'RelatoriosEmpresa::exportarFiscalExcel');
	$routes->get('exportar-fiscal-pdf', 'RelatoriosEmpresa::exportarFiscalPDF');
	$routes->get('exportar-clientes-excel', 'RelatoriosEmpresa::exportarClientesExcel');
	$routes->get('exportar-clientes-pdf', 'RelatoriosEmpresa::exportarClientesPDF');
});

// Rotas de cobrança - apenas ADMIN (tipo 1) terá acesso via verificaPermissaoDeAcesso no controller
$routes->group('cobranca', static function($routes) {
	$routes->get('gerar', 'Cobranca::gerarCobrancasMensais');
	$routes->get('verificar', 'Cobranca::verificarInadimplencia');
	$routes->get('desbloquear/(:num)', 'Cobranca::desbloquearEmpresa/$1');
	$routes->get('bloquear-contador/(:num)', 'Cobranca::bloquearContador/$1');
	$routes->get('desbloquear-contador/(:num)', 'Cobranca::desbloquearContador/$1');
	$routes->get('minhas', 'Cobranca::minhasCobrancasEmpresa');
	$routes->get('empresas', 'Cobranca::minhasCobrancasContador');
	$routes->get('admin', 'Cobranca::adminLista');
});

// Rota de debug simples (TEMPORÁRIA)
$routes->get('debug-test', function() {
    return 'DEBUG: Rota funcionando! Timestamp: ' . date('Y-m-d H:i:s');
});

// Rota de debug para dashboard (SEM FILTROS)
$routes->get('debug-dashboard', 'Admin\TestDashboard::index');

// Rotas dos Dashboards Administrativos - apenas ADMIN (tipo 1)
$routes->group('admin', static function($routes) {
	// Master dashboard integrado no Inicio::admin
	$routes->get('test-dashboard', 'Admin\TestDashboard::index');
	$routes->get('backup-dashboard', 'Admin\BackupDashboard::index');
	$routes->get('backup-dashboard/stats', 'Admin\BackupDashboard::stats');
	$routes->post('backup-dashboard/run', 'Admin\BackupDashboard::run');
	$routes->post('backup-dashboard/test-restore', 'Admin\BackupDashboard::testRestore');
	$routes->post('backup-dashboard/cleanup', 'Admin\BackupDashboard::cleanup');
	$routes->get('cache-monitor', 'Admin\CacheMonitor::index');
	$routes->get('cache-monitor/stats', 'Admin\CacheMonitor::stats');
	$routes->post('cache-monitor/flush', 'Admin\CacheMonitor::flush');
	$routes->post('cache-monitor/invalidateGroup', 'Admin\CacheMonitor::invalidateGroup');
	$routes->post('cache-monitor/cleanup', 'Admin\CacheMonitor::cleanup');
	$routes->get('cache-monitor/cacheDetails', 'Admin\CacheMonitor::cacheDetails');
	$routes->get('audit-dashboard', 'Admin\AuditDashboard::index');
	$routes->get('audit-dashboard/logs', 'Admin\AuditDashboard::logs');
	$routes->get('audit-dashboard/search', 'Admin\AuditDashboard::search');
	$routes->get('security-dashboard', 'Admin\SecurityDashboard::index');
	$routes->get('security-dashboard/alerts', 'Admin\SecurityDashboard::getAlerts');
});

// Rotas Stripe
$routes->group('stripe', static function($routes) {
	$routes->post('checkout', 'Stripe::createCheckoutSession');
	$routes->post('portal', 'Stripe::createPortalSession');
	$routes->post('webhook', 'Stripe::webhook');
	$routes->get('success', 'Inicio::emissor');
	$routes->get('cancel', 'Inicio::emissor');
});

// Proteger o emissor com filtro de assinatura (TEMPORARIAMENTE DESABILITADO)
$routes->group('inicio', static function($routes) {
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

// PDV - protegido por acesso PDV
$routes->group('pdv', ['filter' => 'pdvaccess'], static function($routes) {
	$routes->get('/', 'PDV::index');
	$routes->get('adicionar', 'PDV::adicionar');
	$routes->get('remover/(:num)', 'PDV::remover/$1');
	$routes->get('limpar', 'PDV::limpar');
	$routes->get('finalizar', 'PDV::finalizar');
	$routes->get('buscar-por-barras/(:any)', 'PDV::buscarPorBarras/$1');
});

// API do PDV - RESTful resources
$routes->group('api', ['namespace' => 'App\\Controllers\\Api'], static function($routes) {
		// Settings
		$routes->get('settings/company', 'Settings::company');
		$routes->post('settings/company', 'Settings::companyUpdate');
		$routes->get('settings/company/logo', 'Settings::companyLogo');
		$routes->match(['get','post'], 'settings/printing', 'Settings::printing');
		$routes->match(['get','post'], 'settings/payments', 'Settings::payments');
	// Rotas específicas devem vir antes do resource para evitar captura por show/(:segment)
	$routes->get('pos/active', 'Pos::active');
	$routes->get('pos/stats', 'Pos::stats');
	$routes->get('pos/report-sales', 'Pos::reportSales');
	$routes->get('pos/report-products', 'Pos::reportProducts');
	$routes->get('pos/report-payments', 'Pos::reportPayments');
	$routes->get('pos/report-categories', 'Pos::reportCategories');
	$routes->post('pos/(:num)/finalize', 'Pos::finalize/$1');
	$routes->post('pos/(:num)/cancel', 'Pos::cancel/$1');
	$routes->get('pos/(:num)/receipt', 'Pos::receipt/$1');
	$routes->get('pos/(:num)/receipt/html', 'Pos::receiptHtml/$1');
	$routes->get('pos/(:num)/items', 'Pos::items/$1');
	$routes->get('pos/(:num)/receipt/non-fiscal', 'Pos::receiptNonFiscal/$1');
	// Shifts: open/close + aliases PT-BR
	$routes->post('shifts/open', 'Shifts::open');
	$routes->post('shifts/close/(:num)', 'Shifts::close/$1');
	$routes->post('shifts/abrir', 'Shifts::abrir');
	$routes->post('shifts/fechar', 'Shifts::fechar');
	$routes->get('shifts/status', 'Shifts::status');
	// Resource após as rotas específicas
	$routes->resource('pos', ['controller' => 'Pos']);
	// cash-registers consolidado em Shifts
	$routes->get('cash-registers', 'Shifts::cashRegistersIndex');
	$routes->post('cash-registers', 'Shifts::cashRegistersCreate');
	$routes->get('cash-registers/(:num)', 'Shifts::cashRegistersShow/$1');
	$routes->resource('shifts', ['controller' => 'Shifts']);
	$routes->get('shifts/(:num)/report', 'Shifts::report/$1');
	$routes->get('products/barcode/(:any)', 'Products::barcode/$1');
	$routes->get('products/search', 'Products::search');
	$routes->match(['get','post'], 'products/inventory-movements', 'Products::inventoryMovements');
	$routes->get('products', 'Products::index');
	$routes->get('cart', 'Cart::index');
	$routes->post('cart', 'Cart::create');
	$routes->delete('cart/(:num)', 'Cart::delete/$1');
	$routes->put('cart/(:num)', 'Cart::update/$1');
	$routes->patch('cart/(:num)', 'Cart::update/$1');
	$routes->delete('cart', 'Cart::clear');
	// Diagnostics
	$routes->get('diagnostics', 'Diagnostics::index');
	$routes->get('diagnostics/logs', 'Diagnostics::logs');
	// Caixa - reconstruído (mantido por compatibilidade, mas preferir Shifts)
	$routes->post('caixa/abrir', 'Caixa::abrir');
	$routes->post('caixa/fechar', 'Caixa::fechar');
	// Ping - verificação de conexão
	$routes->get('ping', 'Ping::index');
	// Sync - sincronização offline
	$routes->post('sync/outbox', 'Sync::outbox');
});