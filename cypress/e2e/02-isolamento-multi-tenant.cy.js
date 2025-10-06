/**
 * Teste E2E: Isolamento Multi-Tenant
 * 
 * Valida que dados de diferentes tenants não vazam entre si
 */

describe('Isolamento Multi-Tenant', () => {
  it('Tenant A não deve ver produtos do Tenant B', () => {
    // Login Tenant A
    cy.loginTenantA()
    cy.abrirCaixa(100)
    
    // Interceptar requisições de produtos
    cy.intercept('GET', '/api/products*').as('getProdutos')
    
    // Buscar produtos
    cy.visit('/pdv')
    cy.waitForAPI('@getProdutos')
    
    // Verificar que response contém apenas produtos do Tenant A
    cy.get('@getProdutos').then((interception) => {
      const produtos = interception.response.body
      
      produtos.forEach(produto => {
        expect(produto.id_contador).to.equal(Cypress.env('TENANT_A_CONTADOR'))
        expect(produto.id_empresa).to.equal(Cypress.env('TENANT_A_EMPRESA'))
      })
    })
    
    // Logout
    cy.logoutPDV()
    
    // Login Tenant B
    cy.loginTenantB()
    cy.abrirCaixa(100)
    
    // Buscar produtos novamente
    cy.visit('/pdv')
    cy.waitForAPI('@getProdutos')
    
    // Verificar que response contém apenas produtos do Tenant B
    cy.get('@getProdutos').then((interception) => {
      const produtos = interception.response.body
      
      produtos.forEach(produto => {
        expect(produto.id_contador).to.equal(Cypress.env('TENANT_B_CONTADOR'))
        expect(produto.id_empresa).to.equal(Cypress.env('TENANT_B_EMPRESA'))
      })
    })
  })
  
  it('Tenant A não deve ver vendas do Tenant B', () => {
    // Login Tenant A
    cy.loginTenantA()
    cy.abrirCaixa(100)
    
    // Realizar venda Tenant A
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20)
    
    // Interceptar requisições de vendas
    cy.intercept('GET', '/api/pos*').as('getVendas')
    
    // Acessar relatório de vendas
    cy.contains('Relatórios').click()
    cy.waitForAPI('@getVendas')
    
    // Verificar que vendas pertencem ao Tenant A
    cy.get('@getVendas').then((interception) => {
      const vendas = interception.response.body.data || []
      
      vendas.forEach(venda => {
        expect(venda.id_contador).to.equal(Cypress.env('TENANT_A_CONTADOR'))
        expect(venda.id_empresa).to.equal(Cypress.env('TENANT_A_EMPRESA'))
      })
    })
    
    // Logout e login Tenant B
    cy.logoutPDV()
    cy.loginTenantB()
    cy.abrirCaixa(100)
    
    // Realizar venda Tenant B
    cy.adicionarProduto('7891234567892', 1)
    cy.finalizarVenda('dinheiro', 20)
    
    // Acessar relatório
    cy.contains('Relatórios').click()
    cy.waitForAPI('@getVendas')
    
    // Verificar que vendas pertencem ao Tenant B
    cy.get('@getVendas').then((interception) => {
      const vendas = interception.response.body.data || []
      
      vendas.forEach(venda => {
        expect(venda.id_contador).to.equal(Cypress.env('TENANT_B_CONTADOR'))
        expect(venda.id_empresa).to.equal(Cypress.env('TENANT_B_EMPRESA'))
      })
      
      // Garantir que não há vendas do Tenant A
      const vendasTenantA = vendas.filter(v => 
        v.id_contador === Cypress.env('TENANT_A_CONTADOR')
      )
      expect(vendasTenantA).to.have.length(0)
    })
  })
  
  it('Busca por barcode não deve retornar produtos de outro tenant', () => {
    // Login Tenant A
    cy.loginTenantA()
    cy.abrirCaixa(100)
    
    // Interceptar busca por barcode
    cy.intercept('GET', '/api/products/barcode/*').as('buscaBarcode')
    
    // Buscar produto que existe apenas no Tenant B
    cy.get('#codigo_de_barras').type('7891234567899{enter}')
    cy.waitForAPI('@buscaBarcode')
    
    // Deve retornar 404 (não encontrado)
    cy.get('@buscaBarcode').then((interception) => {
      expect(interception.response.statusCode).to.equal(404)
    })
    
    // Verificar mensagem de erro
    cy.verificarErro('Produto não encontrado')
  })
  
  it('Cache de produtos deve ser isolado por tenant', () => {
    // Login Tenant A
    cy.loginTenantA()
    
    // Verificar que IndexedDB está vazio
    cy.window().then((win) => {
      return win.offlineManager.getStats()
    }).then((stats) => {
      expect(stats.tenant).to.include(Cypress.env('TENANT_A_CONTADOR').toString())
      expect(stats.tenant).to.include(Cypress.env('TENANT_A_EMPRESA').toString())
    })
    
    // Logout e login Tenant B
    cy.logoutPDV()
    cy.loginTenantB()
    
    // Verificar que IndexedDB foi trocado para Tenant B
    cy.window().then((win) => {
      return win.offlineManager.getStats()
    }).then((stats) => {
      expect(stats.tenant).to.include(Cypress.env('TENANT_B_CONTADOR').toString())
      expect(stats.tenant).to.include(Cypress.env('TENANT_B_EMPRESA').toString())
    })
  })
  
  it('Clientes devem ser isolados por tenant', () => {
    // Login Tenant A
    cy.loginTenantA()
    cy.abrirCaixa(100)
    
    // Interceptar busca de clientes
    cy.intercept('GET', '/api/customers*').as('getClientes')
    
    // Buscar clientes
    cy.contains('Buscar Cliente').click()
    cy.waitForAPI('@getClientes')
    
    // Verificar isolamento
    cy.get('@getClientes').then((interception) => {
      const clientes = interception.response.body.data || []
      
      clientes.forEach(cliente => {
        expect(cliente.id_contador).to.equal(Cypress.env('TENANT_A_CONTADOR'))
        expect(cliente.id_empresa).to.equal(Cypress.env('TENANT_A_EMPRESA'))
      })
    })
  })
  
  it('Turnos (shifts) devem ser isolados por tenant', () => {
    // Login Tenant A
    cy.loginTenantA()
    
    // Interceptar requisição de turnos
    cy.intercept('GET', '/api/shifts*').as('getShifts')
    
    cy.visit('/pdv')
    cy.waitForAPI('@getShifts')
    
    // Verificar isolamento
    cy.get('@getShifts').then((interception) => {
      const shifts = interception.response.body.data || interception.response.body || []
      
      shifts.forEach(shift => {
        expect(shift.id_contador).to.equal(Cypress.env('TENANT_A_CONTADOR'))
        expect(shift.id_empresa).to.equal(Cypress.env('TENANT_A_EMPRESA'))
      })
    })
  })
})

