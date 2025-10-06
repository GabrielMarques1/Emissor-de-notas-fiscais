/// <reference types="cypress" />

/**
 * Testes E2E - Fluxo Completo de Pagamentos
 * 
 * Testa:
 * - PIX com QR Code
 * - TEF (Cartão Crédito/Débito)
 * - Retry automático
 * - Webhook de confirmação
 * - Isolamento multi-tenant
 */

describe('💳 Pagamentos - Fluxo Completo', () => {
    const tenant1 = {
        user: 'operador1@tenant1.com',
        pass: 'senha123',
        empresa: 100
    };
    
    const tenant2 = {
        user: 'operador2@tenant2.com',
        pass: 'senha123',
        empresa: 200
    };
    
    beforeEach(() => {
        cy.clearAllSessionStorage();
        cy.clearAllCookies();
    });
    
    /**
     * TESTE 1: PIX - Geração de QR Code
     */
    it('Deve gerar QR Code PIX corretamente', () => {
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        // Adicionar produto
        cy.get('[data-testid="search-product"]').type('7891234567890{enter}');
        cy.wait(500);
        
        cy.get('[data-testid="cart-item"]').should('have.length', 1);
        
        // Selecionar PIX
        cy.get('[data-testid="payment-pix"]').click();
        
        // Finalizar
        cy.finalizeSale();
        
        // Deve aparecer modal com QR Code
        cy.get('[data-testid="pix-modal"]', { timeout: 10000 }).should('be.visible');
        cy.get('[data-testid="pix-qr-code-image"]').should('exist');
        cy.get('[data-testid="pix-br-code"]').should('not.be.empty');
        cy.get('[data-testid="pix-txid"]').should('not.be.empty');
        
        // Verificar expiração
        cy.get('[data-testid="pix-expires-at"]').should('contain', 'minutos');
    });
    
    /**
     * TESTE 2: PIX - Confirmação via Webhook
     */
    it('Deve confirmar pagamento PIX via webhook', () => {
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        // Adicionar produto
        cy.addProduct('7891234567890', 1);
        
        // Finalizar com PIX
        cy.get('[data-testid="payment-pix"]').click();
        cy.finalizeSale();
        
        // Capturar TXID
        cy.get('[data-testid="pix-txid"]').invoke('text').then((txid) => {
            // Simular webhook de confirmação
            const payload = {
                txid: txid,
                e2e_id: 'E2E' + Date.now(),
                status: 'paid',
                amount: 100.00,
                timestamp: Math.floor(Date.now() / 1000)
            };
            
            // Gerar HMAC (em produção, viria do provedor)
            const webhookSecret = 'test_secret_key_12345';
            
            cy.request({
                method: 'POST',
                url: `/api/pix/webhook/${tenant1.empresa}`,
                headers: {
                    'X-Webhook-Signature': Cypress._.md5(JSON.stringify(payload) + webhookSecret)
                },
                body: payload
            }).then((response) => {
                expect(response.status).to.eq(200);
                expect(response.body.success).to.be.true;
            });
        });
        
        // Venda deve ser finalizada automaticamente
        cy.visit('/pos');
        cy.get('[data-testid="cart-empty-message"]').should('be.visible');
    });
    
    /**
     * TESTE 3: TEF - Pagamento com Cartão de Crédito
     */
    it('Deve processar pagamento com cartão de crédito', () => {
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        cy.addProduct('7891234567890', 2); // 2 produtos
        
        // Selecionar crédito
        cy.get('[data-testid="payment-credit"]').click();
        
        // Selecionar parcelamento
        cy.get('[data-testid="installments-select"]').select('3'); // 3x
        
        // Finalizar
        cy.finalizeSale();
        
        // Deve mostrar loading
        cy.get('[data-testid="payment-loading"]', { timeout: 2000 }).should('be.visible');
        
        // Aguardar autorização
        cy.get('[data-testid="payment-approved"]', { timeout: 40000 }).should('be.visible');
        
        // Verificar dados da transação
        cy.get('[data-testid="tef-nsu"]').should('not.be.empty');
        cy.get('[data-testid="tef-authorization"]').should('not.be.empty');
        
        // Imprimir comprovante
        cy.get('[data-testid="print-receipt"]').click();
    });
    
    /**
     * TESTE 4: TEF - Cartão Débito (sem parcelamento)
     */
    it('Não deve permitir parcelar no débito', () => {
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        cy.addProduct('7891234567890', 1);
        
        // Selecionar débito
        cy.get('[data-testid="payment-debit"]').click();
        
        // Campo de parcelamento deve estar desabilitado
        cy.get('[data-testid="installments-select"]').should('be.disabled');
        cy.get('[data-testid="installments-select"]').should('have.value', '1');
    });
    
    /**
     * TESTE 5: Retry Automático em Falha de Rede
     */
    it('Deve fazer retry automático em falha de rede', () => {
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        cy.addProduct('7891234567890', 1);
        cy.get('[data-testid="payment-credit"]').click();
        
        // Interceptar e simular falha na primeira chamada
        let callCount = 0;
        cy.intercept('POST', '**/api/pos/*/finalize', (req) => {
            callCount++;
            
            if (callCount === 1) {
                // Primeira chamada: simular timeout
                req.reply({ statusCode: 504, body: { error: 'Gateway Timeout' }});
            } else if (callCount === 2) {
                // Segunda chamada: simular erro de rede
                req.reply({ statusCode: 500, body: { error: 'Network Error' }});
            } else {
                // Terceira chamada: sucesso
                req.continue();
            }
        }).as('finalize');
        
        cy.finalizeSale();
        
        // Aguardar 3 tentativas
        cy.wait('@finalize');
        cy.wait('@finalize');
        cy.wait('@finalize');
        
        // Deve ter sucesso na terceira tentativa
        cy.get('[data-testid="payment-approved"]', { timeout: 45000 }).should('be.visible');
    });
    
    /**
     * TESTE 6: Isolamento Multi-Tenant em Pagamentos
     */
    it('Tenant não deve acessar transações de outro tenant', () => {
        // Criar transação PIX no tenant 1
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        cy.addProduct('7891234567890', 1);
        cy.get('[data-testid="payment-pix"]').click();
        cy.finalizeSale();
        
        // Capturar TXID
        cy.get('[data-testid="pix-txid"]').invoke('text').then((txid) => {
            // Fazer logout
            cy.logout();
            
            // Login no tenant 2
            cy.login(tenant2.user, tenant2.pass);
            
            // Tentar consultar status da transação do tenant 1
            cy.request({
                method: 'GET',
                url: `/api/pix/status/${txid}`,
                failOnStatusCode: false
            }).then((response) => {
                // Deve retornar 404 ou erro (não pode acessar)
                expect(response.status).to.be.oneOf([404, 403, 401]);
            });
        });
    });
    
    /**
     * TESTE 7: Webhook com HMAC Inválido Deve Falhar
     */
    it('Deve rejeitar webhook com HMAC inválido', () => {
        const payload = {
            txid: 'PIX123456789',
            e2e_id: 'E2E987654321',
            status: 'paid',
            amount: 100.00,
            timestamp: Math.floor(Date.now() / 1000)
        };
        
        const invalidSignature = 'invalid_hmac_signature_here';
        
        cy.request({
            method: 'POST',
            url: `/api/pix/webhook/${tenant1.empresa}`,
            headers: {
                'X-Webhook-Signature': invalidSignature
            },
            body: payload,
            failOnStatusCode: false
        }).then((response) => {
            expect(response.status).to.eq(403);
            expect(response.body.messages.error).to.include('inválido');
        });
    });
    
    /**
     * TESTE 8: Webhook Antigo Deve Ser Rejeitado (Replay Attack)
     */
    it('Deve rejeitar webhook muito antigo (replay attack)', () => {
        const payload = {
            txid: 'PIX123456789',
            e2e_id: 'E2E987654321',
            status: 'paid',
            amount: 100.00,
            timestamp: Math.floor(Date.now() / 1000) - 600 // 10 minutos atrás
        };
        
        cy.request({
            method: 'POST',
            url: `/api/pix/webhook/${tenant1.empresa}`,
            body: payload,
            failOnStatusCode: false
        }).then((response) => {
            expect(response.status).to.eq(403);
            expect(response.body.messages.error).to.include('expirado');
        });
    });
    
    /**
     * TESTE 9: Cancelamento TEF Deve Validar Ownership
     */
    it('Não deve permitir cancelar transação de outro tenant', () => {
        // Criar transação no tenant 1
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        cy.addProduct('7891234567890', 1);
        cy.get('[data-testid="payment-credit"]').click();
        cy.finalizeSale();
        
        cy.get('[data-testid="tef-transaction-id"]').invoke('text').then((idTransaction) => {
            // Logout e login no tenant 2
            cy.logout();
            cy.login(tenant2.user, tenant2.pass);
            
            // Tentar cancelar transação do tenant 1
            cy.request({
                method: 'POST',
                url: `/api/tef/cancel/${idTransaction}`,
                failOnStatusCode: false
            }).then((response) => {
                expect(response.status).to.be.oneOf([404, 403]);
            });
        });
    });
    
    /**
     * TESTE 10: Performance - Pagamento deve completar em <5s
     */
    it('Pagamento deve ser rápido (<5 segundos)', () => {
        cy.login(tenant1.user, tenant1.pass);
        cy.openCashRegister();
        
        cy.addProduct('7891234567890', 1);
        cy.get('[data-testid="payment-credit"]').click();
        
        const startTime = Date.now();
        
        cy.finalizeSale();
        
        cy.get('[data-testid="payment-approved"]', { timeout: 40000 }).should('be.visible').then(() => {
            const duration = Date.now() - startTime;
            
            // Deve completar em menos de 5 segundos (exceto retry)
            expect(duration).to.be.lessThan(5000);
            
            cy.log(`Pagamento processado em ${duration}ms`);
        });
    });
});

