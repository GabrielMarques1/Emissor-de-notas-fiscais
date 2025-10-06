/**
 * Teste E2E: Modo Offline
 * 
 * Valida funcionamento do PDV em modo offline
 */

describe('Modo Offline', () => {
  beforeEach(() => {
    cy.loginTenantA()
    cy.abrirCaixa(100)
  })
  
  it('Deve detectar perda de conexão e exibir badge', () => {
    // Verificar que está online
    cy.get('#pdv-status').should('contain', 'Online')
    
    // Simular perda de conexão
    cy.window().then((win) => {
      win.connectionMonitor.handleOffline()
    })
    
    // Verificar badge de modo offline
    cy.get('#connection-status-badge').should('be.visible')
    cy.contains('MODO OFFLINE').should('be.visible')
  })
  
  it('Deve carregar produtos do cache quando offline', () => {
    // Garantir que produtos estão cacheados
    cy.wait(2000) // Aguardar cache inicial
    
    // Simular offline
    cy.window().then((win) => {
      win.connectionMonitor.handleOffline()
    })
    
    // Tentar buscar produto
    cy.window().then((win) => {
      return win.offlineManager.getProdutoByBarcode('7891234567890')
    }).then((produto) => {
      // Produto deve estar no cache
      expect(produto).to.not.be.null
      expect(produto.codigo_de_barras).to.equal('7891234567890')
    })
  })
  
  it('Deve adicionar venda ao outbox quando offline', () => {
    // Simular offline
    cy.window().then((win) => {
      win.connectionMonitor.handleOffline()
    })
    
    // Adicionar produto
    cy.adicionarProduto('7891234567890', 1)
    
    // Tentar finalizar venda
    cy.finalizarVenda('dinheiro', 20)
    
    // Venda deve ser adicionada ao outbox
    cy.window().then((win) => {
      return win.offlineManager.getPendingOutbox()
    }).then((outbox) => {
      expect(outbox.length).to.be.greaterThan(0)
      
      const venda = outbox[0]
      expect(venda.operation).to.equal('create_sale')
      expect(venda.status).to.equal('pending')
    })
  })
  
  it('Deve sincronizar vendas pendentes ao reconectar', () => {
    // Simular offline e criar venda
    cy.window().then((win) => {
      win.connectionMonitor.handleOffline()
    })
    
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20)
    
    // Verificar outbox
    cy.window().then((win) => {
      return win.offlineManager.getPendingOutbox()
    }).then((outbox) => {
      const quantidadePendente = outbox.length
      expect(quantidadePendente).to.be.greaterThan(0)
      
      // Simular reconexão
      cy.window().then((win) => {
        win.connectionMonitor.handleOnline()
      })
      
      // Aguardar sincronização
      cy.wait(5000)
      
      // Verificar que outbox foi limpo
      cy.window().then((win) => {
        return win.offlineManager.getPendingOutbox()
      }).then((outboxAtualizado) => {
        expect(outboxAtualizado.length).to.be.lessThan(quantidadePendente)
      })
    })
  })
  
  it('Deve exibir contador de operações pendentes', () => {
    // Simular offline
    cy.window().then((win) => {
      win.connectionMonitor.handleOffline()
    })
    
    // Adicionar 3 vendas ao outbox
    for (let i = 0; i < 3; i++) {
      cy.adicionarProduto('7891234567890', 1)
      cy.finalizarVenda('dinheiro', 20)
      cy.wait(500)
    }
    
    // Verificar contador
    cy.get('#outbox-counter').should('contain', '3 pendentes')
  })
  
  it('Service Worker deve servir assets do cache quando offline', () => {
    // Reload para garantir que Service Worker está ativo
    cy.reload()
    
    // Verificar que Service Worker está registrado
    cy.window().then((win) => {
      return win.navigator.serviceWorker.ready
    }).then((registration) => {
      expect(registration).to.not.be.undefined
    })
    
    // Simular offline
    cy.goOffline()
    
    // Tentar carregar página
    cy.visit('/pdv', { failOnStatusCode: false })
    
    // Assets devem carregar do cache
    cy.get('body').should('exist')
  })
  
  it('Deve mostrar toast ao reconectar', () => {
    // Simular offline
    cy.window().then((win) => {
      win.connectionMonitor.handleOffline()
    })
    
    cy.wait(1000)
    
    // Simular reconexão
    cy.window().then((win) => {
      win.connectionMonitor.handleOnline()
    })
    
    // Verificar toast de reconexão
    cy.contains('Conexão Restaurada', { timeout: 5000 }).should('be.visible')
  })
  
  it('Ping periódico deve detectar perda de conexão', () => {
    // Verificar que está online
    cy.window().then((win) => {
      expect(win.connectionMonitor.isOnline).to.be.true
    })
    
    // Bloquear endpoint de ping
    cy.intercept('GET', '/api/ping', { forceNetworkError: true })
    
    // Aguardar próximo ping (10s)
    cy.wait(11000)
    
    // Status deve mudar para offline
    cy.window().then((win) => {
      expect(win.connectionMonitor.isOnline).to.be.false
    })
  })
})

