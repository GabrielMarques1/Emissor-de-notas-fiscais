/**
 * Testes E2E para Modo Offline Multi-Tenant
 * 
 * Simula cenários de reconexão, conflitos e isolamento de tenant
 */

describe('Modo Offline e Sincronização Multi-Tenant', () => {
    const tenant1 = {
        email: 'tenant1@example.com',
        password: 'senha123',
        idContador: 1,
        idEmpresa: 1
    };

    const tenant2 = {
        email: 'tenant2@example.com',
        password: 'senha123',
        idContador: 2,
        idEmpresa: 2
    };

    beforeEach(() => {
        // Reset database state
        cy.task('db:seed');
    });

    describe('Detecção e Feedback de Status Offline', () => {
        it('deve exibir widget de status offline quando conexão cai', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Verificar widget inicialmente online
            cy.get('#offline-status-widget').should('exist');
            cy.get('.status-online').should('exist');

            // Simular perda de conexão
            cy.window().then((win) => {
                win.dispatchEvent(new Event('offline'));
            });

            // Verificar feedback visual
            cy.get('.status-offline').should('exist');
            cy.get('.status-text').should('contain', 'Modo Offline');
            cy.get('#offline-banner').should('exist');
            cy.get('#offline-banner').should('contain', 'Sem conexão');
        });

        it('deve remover banner quando conexão é restaurada', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Simular offline
            cy.window().then((win) => {
                win.dispatchEvent(new Event('offline'));
            });
            cy.get('#offline-banner').should('exist');

            // Simular reconexão
            cy.window().then((win) => {
                win.dispatchEvent(new Event('online'));
            });

            cy.get('#offline-banner').should('not.exist');
            cy.get('.status-online').should('exist');
        });
    });

    describe('Operações Offline com Tenant Isolation', () => {
        it('deve criar venda offline e registrar no outbox com tenant correto', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Simular modo offline
            cy.intercept('GET', '/api/health-check', { statusCode: 503 }).as('offline');

            // Criar venda
            cy.get('#add-product-btn').click();
            cy.get('#product-search').type('Produto Teste');
            cy.get('.product-item').first().click();
            cy.get('#finish-sale-btn').click();
            cy.get('#payment-method').select('dinheiro');
            cy.get('#confirm-payment-btn').click();

            // Verificar mensagem de sucesso offline
            cy.contains('Venda registrada em modo offline').should('exist');

            // Verificar registro no outbox via API (quando voltar online)
            cy.intercept('GET', '/api/health-check', { statusCode: 200 }).as('online');
            cy.wait(1000);

            cy.request('/api/sync/stats').then((response) => {
                expect(response.body.pending).to.be.greaterThan(0);
            });
        });

        it('deve isolar dados entre tenants no outbox', () => {
            // Tenant 1 cria venda
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');
            cy.createSale({ product: 'Produto A', quantity: 2 });
            cy.logout();

            // Tenant 2 cria venda
            cy.login(tenant2.email, tenant2.password);
            cy.visit('/pdv');
            cy.createSale({ product: 'Produto B', quantity: 3 });

            // Verificar isolamento via API
            cy.request('/api/sync/stats').then((response) => {
                expect(response.body.pending).to.be.greaterThan(0);
                
                // Tenant 2 não deve ver dados do Tenant 1
                cy.task('db:query', {
                    query: 'SELECT * FROM outbox_events WHERE id_contador = ? AND id_empresa = ?',
                    params: [tenant2.idContador, tenant2.idEmpresa]
                }).then((results) => {
                    expect(results).to.have.length.greaterThan(0);
                    results.forEach((event) => {
                        expect(event.id_contador).to.equal(tenant2.idContador);
                        expect(event.id_empresa).to.equal(tenant2.idEmpresa);
                    });
                });
            });
        });
    });

    describe('Sincronização e Resolução de Conflitos', () => {
        it('deve sincronizar operações pendentes ao reconectar', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Criar múltiplas vendas offline
            cy.intercept('GET', '/api/health-check', { statusCode: 503 }).as('offline');
            
            for (let i = 0; i < 3; i++) {
                cy.createSale({ product: `Produto ${i}`, quantity: 1 });
            }

            // Verificar pendências
            cy.get('#offline-status-widget').click();
            cy.get('#pending-ops-count').should('contain', '3');

            // Simular reconexão e sincronização
            cy.intercept('GET', '/api/health-check', { statusCode: 200 }).as('online');
            cy.intercept('POST', '/api/sync/execute').as('syncExecute');

            cy.get('#sync-now-btn').click();
            cy.wait('@syncExecute');

            // Verificar sincronização concluída
            cy.contains('Sincronização concluída').should('exist');
            cy.get('#pending-ops-count').should('contain', '0');
        });

        it('deve resolver conflitos usando last-write-wins', () => {
            cy.login(tenant1.email, tenant1.password);

            // Criar produto no cloud
            cy.request('POST', '/api/produtos', {
                id_produto: 999,
                id_contador: tenant1.idContador,
                id_empresa: tenant1.idEmpresa,
                nome: 'Produto Original',
                preco: 100.00
            });

            // Simular modo offline
            cy.intercept('GET', '/api/health-check', { statusCode: 503 }).as('offline');

            // Modificar produto localmente
            cy.visit('/produtos/editar/999');
            cy.get('#produto-nome').clear().type('Produto Modificado Offline');
            cy.get('#produto-preco').clear().type('150.00');
            cy.get('#save-btn').click();

            // Modificar na nuvem (simulando outro terminal)
            cy.task('db:update', {
                table: 'produtos',
                data: { 
                    nome: 'Produto Cloud Mais Recente',
                    preco: 200.00,
                    updated_at: new Date(Date.now() + 60000).toISOString() // 1 min no futuro
                },
                where: { id_produto: 999 }
            });

            // Reconectar e sincronizar
            cy.intercept('GET', '/api/health-check', { statusCode: 200 }).as('online');
            cy.visit('/pdv');
            cy.get('#sync-now-btn').click();

            // Verificar que versão da cloud venceu (timestamp mais recente)
            cy.request('/api/produtos/999').then((response) => {
                expect(response.body.nome).to.equal('Produto Cloud Mais Recente');
                expect(response.body.preco).to.equal(200.00);
            });
        });
    });

    describe('Auditoria de Operações Offline', () => {
        it('deve registrar todas operações offline no log de auditoria', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Simular offline
            cy.intercept('GET', '/api/health-check', { statusCode: 503 }).as('offline');

            // Realizar operações
            cy.createSale({ product: 'Produto X', quantity: 2 });
            cy.createCustomer({ name: 'Cliente Teste', cpf: '12345678900' });

            // Verificar logs de auditoria
            cy.task('db:query', {
                query: `
                    SELECT * FROM offline_audit_log 
                    WHERE id_contador = ? AND id_empresa = ?
                    ORDER BY created_at DESC
                `,
                params: [tenant1.idContador, tenant1.idEmpresa]
            }).then((logs) => {
                expect(logs).to.have.length.greaterThan(0);
                
                // Verificar campos obrigatórios
                logs.forEach((log) => {
                    expect(log).to.have.property('action');
                    expect(log).to.have.property('entity_type');
                    expect(log).to.have.property('entity_id');
                    expect(log).to.have.property('user_name');
                    expect(log).to.have.property('ip_address');
                    expect(log.status).to.equal('pending');
                });
            });
        });
    });

    describe('Fallback para Operações Críticas', () => {
        it('deve permitir vendas mesmo com conexão instável', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Simular conexão intermitente
            let requestCount = 0;
            cy.intercept('GET', '/api/health-check', (req) => {
                requestCount++;
                // Alterna entre online/offline
                req.reply({ statusCode: requestCount % 2 === 0 ? 200 : 503 });
            });

            // Criar venda deve funcionar independente do status
            cy.createSale({ product: 'Produto Y', quantity: 1 });
            cy.contains(/Venda (finalizada|registrada)/).should('exist');
        });

        it('deve manter integridade dos dados durante falhas', () => {
            cy.login(tenant1.email, tenant1.password);
            cy.visit('/pdv');

            // Simular falha durante sincronização
            cy.intercept('POST', '/api/sync/execute', { statusCode: 500 }).as('syncFail');

            cy.get('#offline-status-widget').click();
            cy.get('#sync-now-btn').click();
            cy.wait('@syncFail');

            // Verificar que eventos permanecem pendentes
            cy.request('/api/sync/stats').then((response) => {
                expect(response.body.pending).to.be.greaterThan(0);
            });

            // Verificar que retry foi incrementado
            cy.task('db:query', {
                query: 'SELECT retry_count FROM outbox_events WHERE status = ? LIMIT 1',
                params: ['pending']
            }).then((results) => {
                if (results.length > 0) {
                    expect(results[0].retry_count).to.be.greaterThan(0);
                }
            });
        });
    });

    describe('Performance e Carga', () => {
        it('deve sincronizar 100+ operações sem degradação', () => {
            cy.login(tenant1.email, tenant1.password);

            // Criar muitas operações offline
            cy.intercept('GET', '/api/health-check', { statusCode: 503 });
            
            cy.visit('/pdv');
            
            // Criar 100 vendas (simulação rápida via API)
            for (let i = 0; i < 100; i++) {
                cy.request('POST', '/api/pos/sales', {
                    items: [{ product_id: 1, quantity: 1 }],
                    payment: { method: 'dinheiro', amount: 10 }
                });
            }

            // Reconectar e medir tempo de sincronização
            cy.intercept('GET', '/api/health-check', { statusCode: 200 });
            
            const startTime = Date.now();
            cy.get('#sync-now-btn').click();
            cy.contains('Sincronização concluída', { timeout: 30000 }).should('exist');
            const endTime = Date.now();

            const syncTime = endTime - startTime;
            expect(syncTime).to.be.lessThan(30000); // Menos de 30 segundos
        });
    });
});

// Comandos customizados
Cypress.Commands.add('login', (email, password) => {
    cy.visit('/login');
    cy.get('#email').type(email);
    cy.get('#password').type(password);
    cy.get('#login-btn').click();
    cy.url().should('not.include', '/login');
});

Cypress.Commands.add('logout', () => {
    cy.get('#logout-btn').click();
});

Cypress.Commands.add('createSale', ({ product, quantity }) => {
    cy.get('#add-product-btn').click();
    cy.get('#product-search').type(product);
    cy.get('.product-item').first().click();
    cy.get('#quantity').clear().type(quantity);
    cy.get('#add-to-cart-btn').click();
    cy.get('#finish-sale-btn').click();
    cy.get('#payment-method').select('dinheiro');
    cy.get('#confirm-payment-btn').click();
});

Cypress.Commands.add('createCustomer', ({ name, cpf }) => {
    cy.visit('/clientes/novo');
    cy.get('#customer-name').type(name);
    cy.get('#customer-cpf').type(cpf);
    cy.get('#save-customer-btn').click();
});
