<?php

/**
 * Arquivo de configuração de filtros para ambiente de testes (CI4).
 * Ajusta aliases reais usados pela API e desabilita CSRF em api/*.
 */

namespace Tests\Support\Config\Filters;

/**
 * @psalm-suppress UndefinedGlobalVariable
 */
$filters->aliases['subscription']  = \App\Filters\SubscriptionFilter::class;
$filters->aliases['pdvaccess']     = \App\Filters\PdvAccessFilter::class;
$filters->aliases['apithrottle']   = \App\Filters\ApiThrottleFilter::class;

// Desabilitar CSRF para endpoints REST nos testes
$filters->filters['csrf'] = ['except' => ['api/*']];
// Não aplicar CSRF por método em testes
$filters->methods = [];
