-- ===============================================
-- SCRIPT DE VALIDAÇÃO DO SISTEMA PDV MULTI-TENANT
-- Execute este script para validar a implementação
-- ===============================================

-- 1. VERIFICAR TABELAS CRIADAS
SELECT 
    'Verificando tabelas...' as etapa,
    COUNT(*) as total_tabelas
FROM information_schema.tables 
WHERE table_schema = 'erp_local' 
AND table_name IN (
    'tef_transactions',
    'pix_transactions',
    'pos_sale_payments',
    'coupons',
    'discounts',
    'returns',
    'return_items'
);
-- Esperado: 7 tabelas

-- 2. VERIFICAR COLUNAS ADICIONADAS
SELECT 
    'Verificando colunas...' as etapa,
    table_name,
    column_name
FROM information_schema.columns
WHERE table_schema = 'erp_local'
AND (
    (table_name = 'pos_sales' AND column_name IN ('id_tef_transaction', 'id_pix_transaction', 'is_suspended', 'total_discount'))
    OR (table_name = 'empresas' AND column_name IN ('tef_acquirer', 'pix_provider', 'max_discount_percentage', 'return_days_limit'))
);
-- Esperado: 8+ linhas

-- 3. VERIFICAR CONFIGURAÇÕES DA EMPRESA
SELECT 
    'Configurações da Empresa 100' as etapa,
    id_empresa,
    xFant as nome,
    tef_acquirer,
    tef_environment,
    pix_provider,
    pix_key,
    max_discount_percentage,
    return_days_limit,
    CASE 
        WHEN tef_acquirer IS NOT NULL THEN '✓ TEF configurado'
        ELSE '✗ TEF não configurado'
    END as status_tef,
    CASE 
        WHEN pix_provider IS NOT NULL THEN '✓ PIX configurado'
        ELSE '✗ PIX não configurado'
    END as status_pix
FROM empresas 
WHERE id_empresa = 100;

-- 4. VERIFICAR PRODUTO DE TESTE
SELECT 
    'Produto de Teste' as etapa,
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ Produto 999 existe'
        ELSE '✗ Produto 999 não existe - CRIAR!'
    END as status
FROM produtos 
WHERE id_produto = 999 AND id_empresa = 100;

-- 5. VERIFICAR CUPOM DE TESTE
SELECT 
    'Cupom de Teste' as etapa,
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ Cupom PROMO10 existe'
        ELSE '✗ Cupom PROMO10 não existe - CRIAR!'
    END as status
FROM coupons 
WHERE code = 'PROMO10' AND id_empresa = 100;

-- 6. LISTAR TRANSAÇÕES PIX (se houver)
SELECT 
    'Transações PIX' as etapa,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pendentes,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmadas,
    COUNT(CASE WHEN status = 'expired' THEN 1 END) as expiradas
FROM pix_transactions 
WHERE id_empresa = 100;

-- 7. LISTAR TRANSAÇÕES TEF (se houver)
SELECT 
    'Transações TEF' as etapa,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'authorized' THEN 1 END) as autorizadas,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmadas,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as canceladas
FROM tef_transactions 
WHERE id_empresa = 100;

-- 8. LISTAR VENDAS COM MÚLTIPLOS PAGAMENTOS
SELECT 
    'Múltiplos Pagamentos' as etapa,
    COUNT(DISTINCT id_pos_sale) as vendas_com_multiplos_pagamentos,
    SUM(amount) as total_em_pagamentos_multiplos
FROM pos_sale_payments 
WHERE id_empresa = 100;

-- 9. LISTAR VENDAS SUSPENSAS
SELECT 
    'Vendas Suspensas' as etapa,
    COUNT(*) as total_suspensas,
    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as ainda_validas,
    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expiradas
FROM pos_sales 
WHERE id_empresa = 100 
AND is_suspended = 1;

-- 10. LISTAR DESCONTOS APLICADOS
SELECT 
    'Descontos Aplicados' as etapa,
    COUNT(*) as total_descontos,
    SUM(discount_amount) as total_em_descontos
FROM discounts 
WHERE id_empresa = 100;

-- 11. LISTAR DEVOLUÇÕES
SELECT 
    'Devoluções' as etapa,
    COUNT(*) as total_devolucoes,
    COUNT(CASE WHEN type = 'full_return' THEN 1 END) as devolucoes_totais,
    COUNT(CASE WHEN type = 'partial_return' THEN 1 END) as devolucoes_parciais,
    COUNT(CASE WHEN type = 'exchange' THEN 1 END) as trocas,
    SUM(total_returned) as total_devolvido
FROM returns 
WHERE id_empresa = 100;

-- 12. VERIFICAR ISOLAMENTO MULTI-TENANT
-- (deve retornar 0 se não houver vazamento)
SELECT 
    'Teste de Isolamento Multi-Tenant' as etapa,
    'PIX' as tabela,
    COUNT(*) as registros_sem_tenant_id
FROM pix_transactions 
WHERE id_contador IS NULL OR id_empresa IS NULL
UNION ALL
SELECT 
    'Teste de Isolamento Multi-Tenant',
    'TEF',
    COUNT(*)
FROM tef_transactions 
WHERE id_contador IS NULL OR id_empresa IS NULL
UNION ALL
SELECT 
    'Teste de Isolamento Multi-Tenant',
    'Multi-Payment',
    COUNT(*)
FROM pos_sale_payments 
WHERE id_contador IS NULL OR id_empresa IS NULL
UNION ALL
SELECT 
    'Teste de Isolamento Multi-Tenant',
    'Cupons',
    COUNT(*)
FROM coupons 
WHERE id_contador IS NULL OR id_empresa IS NULL
UNION ALL
SELECT 
    'Teste de Isolamento Multi-Tenant',
    'Devoluções',
    COUNT(*)
FROM returns 
WHERE id_contador IS NULL OR id_empresa IS NULL;
-- Esperado: TODOS devem retornar 0

-- ===============================================
-- RESUMO FINAL
-- ===============================================
SELECT 
    '=== RESUMO FINAL ===' as resultado,
    '' as valor
UNION ALL
SELECT 
    'Tabelas criadas',
    CAST(COUNT(*) AS CHAR)
FROM information_schema.tables 
WHERE table_schema = 'erp_local' 
AND table_name IN ('tef_transactions', 'pix_transactions', 'pos_sale_payments', 'coupons', 'discounts', 'returns', 'return_items')
UNION ALL
SELECT 
    'TEF configurado',
    CASE WHEN tef_acquirer IS NOT NULL THEN 'SIM ✓' ELSE 'NÃO ✗' END
FROM empresas WHERE id_empresa = 100
UNION ALL
SELECT 
    'PIX configurado',
    CASE WHEN pix_provider IS NOT NULL THEN 'SIM ✓' ELSE 'NÃO ✗' END
FROM empresas WHERE id_empresa = 100
UNION ALL
SELECT 
    'Produto teste existe',
    CASE WHEN COUNT(*) > 0 THEN 'SIM ✓' ELSE 'NÃO ✗' END
FROM produtos WHERE id_produto = 999 AND id_empresa = 100
UNION ALL
SELECT 
    'Cupom teste existe',
    CASE WHEN COUNT(*) > 0 THEN 'SIM ✓' ELSE 'NÃO ✗' END
FROM coupons WHERE code = 'PROMO10' AND id_empresa = 100;

-- ===============================================
-- FIM DA VALIDAÇÃO
-- ===============================================

