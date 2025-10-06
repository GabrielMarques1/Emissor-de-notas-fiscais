<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>> [filter_name => classname]
     *                                                     or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'tenant'        => \App\Filters\TenantFilter::class,
        'audit'         => \App\Filters\AuditFilter::class,
        'pdvaccess'     => \App\Filters\PdvAccessFilter::class,
        'apithrottle'   => \App\Filters\ApiThrottleFilter::class,
        'role'          => \App\Filters\RoleFilter::class,
        'auth'          => \App\Filters\AuthFilter::class,
    ];
    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // TODOS OS FILTROS GLOBAIS DESABILITADOS TEMPORARIAMENTE
            // 'honeypot',
            // 'csrf',
            // 'invalidchars',
        ],
        'after' => [
            // 'toolbar',  // Desabilitar toolbar também
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     */
    public array $methods = [
        // Desabilitar CSRF por método para API (tratado por exceção abaixo)
        // 'post' => ['csrf'],
    ];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */
    public array $filters = [
        'csrf' => [
            'except' => [
                'stripe/webhook',
                'UF/preparaMunicipios',
                'receitaWS/pegaDadosDoCNPJ',
                'login/verificaUsuario',
                'api/*',
            ],
        ],
        // TENANT FILTER REATIVADO COM CONFIGURAÇÃO PARA SEU SISTEMA
        'tenant' => [
            'before' => [
                'api/*',           // Todas as APIs precisam de tenant
                'pos/*',           // PDV precisa de tenant
                'dashboard/*',     // Dashboard precisa de tenant
                'vendas/*',        // Vendas precisam de tenant
                'produtos/*',      // Produtos precisam de tenant
                'clientes/*',      // Clientes precisam de tenant
                'relatorios/*',    // Relatórios precisam de tenant
                'configuracoes/*', // Configurações precisam de tenant
                'caixa/*',         // Caixa precisa de tenant
                'estoque/*',       // Estoque precisa de tenant
                'financeiro/*',    // Financeiro precisa de tenant
                'nfce/*',          // NFCe precisa de tenant
                'nfe/*',           // NFe precisa de tenant
                'testsecurity',    // ADICIONAR: Rota de teste (sem barra)
                'testsecurity/*',  // ADICIONAR: Todas as rotas de teste
            ],
            'except' => [
                // Rotas públicas que NÃO precisam de tenant
                'login',
                'login/*',
                'register',
                'register/*',
                'password/*',
                'health',
                'health/*',
                'api/ping',
                'api/diagnostics',
                'api/diagnostics/*',
                'stripe/webhook',
                'pix/webhook',
                'pix/webhook/*',
                'UF/preparaMunicipios',
                'receitaWS/pegaDadosDoCNPJ',
                'assets/*',
                'favicon.ico',
                'robots.txt',
                'sitemap.xml',
                'testsecurity/publictest',  // Teste público (mantém exceção)
                'testsecurity/session',     // Verificar sessão (mantém exceção para debug)
                'relatorios/contadores',    // TEMPORÁRIO: Liberar esta rota específica
            ],
        ],
        // AUDIT FILTER - Log automático de todas as requisições
        'audit' => [
            'before' => [
                'api/*',           // Auditar todas as APIs
                'pos/*',           // Auditar PDV
                'dashboard/*',     // Auditar dashboard
                'admin/*',         // Auditar área admin
            ],
            'except' => [
                'api/ping',        // Não auditar ping
                'api/diagnostics', // Não auditar diagnósticos
                'assets/*',        // Não auditar assets
                'favicon.ico',     // Não auditar favicon
            ],
        ],
    ];
}
