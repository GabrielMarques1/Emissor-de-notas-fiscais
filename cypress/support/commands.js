// ***********************************************
// Custom Commands para PDV Multi-Tenant
// ***********************************************

/**
 * Login no PDV
 * @param {string} user - Email ou usuário
 * @param {string} password - Senha
 */
Cypress.Commands.add('loginPDV', (user, password) => {
  cy.visit('/login-pdv')
  
  cy.get('input[name="usuario"]').type(user)
  cy.get('input[name="senha"]').type(password)
  cy.get('button[type="submit"]').click()
  
  // Aguardar redirecionamento
  cy.url().should('include', '/pdv')
  
  // Verificar se está logado
  cy.contains('PDV', { timeout: 10000 }).should('be.visible')
})

/**
 * Login no PDV para Tenant A
 */
Cypress.Commands.add('loginTenantA', () => {
  const user = Cypress.env('TENANT_A_USER')
  const pass = Cypress.env('TENANT_A_PASS')
  cy.loginPDV(user, pass)
})

/**
 * Login no PDV para Tenant B
 */
Cypress.Commands.add('loginTenantB', () => {
  const user = Cypress.env('TENANT_B_USER')
  const pass = Cypress.env('TENANT_B_PASS')
  cy.loginPDV(user, pass)
})

/**
 * Logout do PDV
 */
Cypress.Commands.add('logoutPDV', () => {
  cy.visit('/login-pdv/logout')
  cy.url().should('include', '/login-pdv')
})

/**
 * Abrir caixa
 * @param {number} valorInicial - Valor inicial do caixa
 */
Cypress.Commands.add('abrirCaixa', (valorInicial = 100.00) => {
  cy.visit('/pdv')
  
  // Clicar no botão de abrir caixa (pode estar em modal ou header)
  cy.contains('Abrir Caixa', { timeout: 5000 }).click()
  
  // Preencher valor inicial
  cy.get('input[name="valor_inicial"]').clear().type(valorInicial.toString())
  
  // Confirmar
  cy.contains('button', 'Confirmar').click()
  
  // Aguardar confirmação
  cy.contains('Caixa aberto', { timeout: 5000 }).should('be.visible')
})

/**
 * Fechar caixa
 */
Cypress.Commands.add('fecharCaixa', () => {
  // Clicar no botão de fechar caixa
  cy.contains('Fechar Caixa', { timeout: 5000 }).click()
  
  // Confirmar fechamento
  cy.contains('button', 'Confirmar Fechamento').click()
  
  // Aguardar confirmação
  cy.contains('Caixa fechado', { timeout: 5000 }).should('be.visible')
})

/**
 * Buscar produto por código de barras
 * @param {string} barcode - Código de barras
 */
Cypress.Commands.add('buscarProduto', (barcode) => {
  cy.get('#codigo_de_barras').clear().type(barcode + '{enter}')
  
  // Aguardar produto aparecer na lista
  cy.wait(1000)
})

/**
 * Adicionar produto ao carrinho
 * @param {string} barcode - Código de barras
 * @param {number} quantidade - Quantidade
 */
Cypress.Commands.add('adicionarProduto', (barcode, quantidade = 1) => {
  for (let i = 0; i < quantidade; i++) {
    cy.buscarProduto(barcode)
  }
})

/**
 * Finalizar venda
 * @param {string} formaPagamento - 'dinheiro', 'credito', 'debito', 'pix'
 * @param {number} valorPago - Valor pago (opcional, para dinheiro)
 */
Cypress.Commands.add('finalizarVenda', (formaPagamento = 'dinheiro', valorPago = null) => {
  // Clicar em Finalizar
  cy.contains('button', 'Finalizar Venda').click()
  
  // Aguardar modal de pagamento
  cy.get('.modal').should('be.visible')
  
  // Selecionar forma de pagamento
  cy.get(`button[data-payment="${formaPagamento}"]`).click()
  
  // Se dinheiro, informar valor pago
  if (formaPagamento === 'dinheiro' && valorPago) {
    cy.get('input[name="valor_pago"]').clear().type(valorPago.toString())
  }
  
  // Confirmar pagamento
  cy.contains('button', 'Confirmar Pagamento').click()
  
  // Aguardar confirmação
  cy.contains('Venda finalizada', { timeout: 10000 }).should('be.visible')
})

/**
 * Limpar carrinho
 */
Cypress.Commands.add('limparCarrinho', () => {
  cy.contains('button', 'Limpar Carrinho').click()
  
  // Confirmar
  cy.contains('button', 'Sim').click()
  
  cy.wait(500)
})

/**
 * Verificar se está offline
 */
Cypress.Commands.add('goOffline', () => {
  cy.log('Simulando modo offline')
  
  // Cypress não suporta navigator.onLine nativamente
  // Usar cy.intercept para bloquear requests
  cy.intercept('**', { forceNetworkError: true })
})

/**
 * Verificar se está online
 */
Cypress.Commands.add('goOnline', () => {
  cy.log('Voltando online')
  
  // Remover intercepts
  cy.reload()
})

/**
 * Aguardar API response
 * @param {string} alias - Alias do intercept
 * @param {number} timeout - Timeout em ms
 */
Cypress.Commands.add('waitForAPI', (alias, timeout = 10000) => {
  cy.wait(alias, { timeout })
})

/**
 * Verificar total do carrinho
 * @param {number} valorEsperado - Valor esperado
 */
Cypress.Commands.add('verificarTotal', (valorEsperado) => {
  cy.get('.pdv-total-value')
    .should('contain', valorEsperado.toFixed(2).replace('.', ','))
})

/**
 * Intercept de API para verificar tenant
 * @param {number} idContador - ID do contador esperado
 * @param {number} idEmpresa - ID da empresa esperado
 */
Cypress.Commands.add('interceptTenant', (idContador, idEmpresa) => {
  cy.intercept('POST', '/api/**', (req) => {
    // Verificar se request contém tenant correto
    if (req.body && req.body.id_contador) {
      expect(req.body.id_contador).to.equal(idContador)
    }
    if (req.body && req.body.id_empresa) {
      expect(req.body.id_empresa).to.equal(idEmpresa)
    }
  }).as('tenantRequest')
})

/**
 * Verificar mensagem de erro
 * @param {string} mensagem - Mensagem esperada
 */
Cypress.Commands.add('verificarErro', (mensagem) => {
  cy.contains(mensagem, { timeout: 5000 }).should('be.visible')
})

/**
 * Verificar mensagem de sucesso
 * @param {string} mensagem - Mensagem esperada
 */
Cypress.Commands.add('verificarSucesso', (mensagem) => {
  cy.contains(mensagem, { timeout: 5000 }).should('be.visible')
})

/**
 * Simular leitura de código de barras
 * @param {string} barcode - Código de barras
 */
Cypress.Commands.add('scanBarcode', (barcode) => {
  cy.get('#codigo_de_barras').type(barcode + '{enter}')
  cy.wait(500)
})

/**
 * Verificar quantidade de itens no carrinho
 * @param {number} quantidade - Quantidade esperada
 */
Cypress.Commands.add('verificarQuantidadeItens', (quantidade) => {
  cy.get('#pdv-tbody tr').should('have.length', quantidade)
})

/**
 * Remover item do carrinho por índice
 * @param {number} index - Índice do item (0-based)
 */
Cypress.Commands.add('removerItem', (index) => {
  cy.get('#pdv-tbody tr').eq(index).find('button[title="Remover"]').click()
  cy.wait(500)
})

