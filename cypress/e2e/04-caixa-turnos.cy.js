/**
 * Teste E2E: Abertura e Fechamento de Caixa
 * 
 * Valida operações de turno (shift) do caixa
 */

describe('Caixa e Turnos', () => {
  beforeEach(() => {
    cy.loginTenantA()
  })
  
  it('Deve abrir caixa com valor inicial', () => {
    // Interceptar abertura de caixa
    cy.intercept('POST', '/api/shifts/open').as('abrirCaixa')
    
    cy.abrirCaixa(150.00)
    
    // Verificar request
    cy.waitForAPI('@abrirCaixa')
    cy.get('@abrirCaixa').then((interception) => {
      expect(interception.request.body.valor_inicial).to.equal(150.00)
      expect(interception.response.statusCode).to.equal(201)
    })
    
    // Verificar indicador de caixa aberto
    cy.get('#pdv-shift-pill').should('contain', 'Aberto')
  })
  
  it('Não deve permitir abrir caixa já aberto', () => {
    // Abrir caixa
    cy.abrirCaixa(100)
    
    // Tentar abrir novamente
    cy.contains('Abrir Caixa').click()
    
    // Deve exibir erro
    cy.verificarErro('Já existe um caixa aberto')
  })
  
  it('Deve realizar sangria (retirada de dinheiro)', () => {
    cy.abrirCaixa(500)
    
    // Abrir menu de sangria
    cy.contains('button', 'Sangria').click()
    
    // Preencher valor
    cy.get('input[name="valor_sangria"]').type('100')
    cy.get('textarea[name="motivo"]').type('Retirada para banco')
    
    // Confirmar
    cy.contains('button', 'Confirmar Sangria').click()
    
    // Verificar sucesso
    cy.verificarSucesso('Sangria realizada')
    
    // Saldo do caixa deve diminuir
    cy.contains('Saldo: R$ 400,00').should('be.visible')
  })
  
  it('Deve realizar suprimento (adição de dinheiro)', () => {
    cy.abrirCaixa(100)
    
    // Abrir menu de suprimento
    cy.contains('button', 'Suprimento').click()
    
    // Preencher valor
    cy.get('input[name="valor_suprimento"]').type('200')
    cy.get('textarea[name="motivo"]').type('Troco adicional')
    
    // Confirmar
    cy.contains('button', 'Confirmar Suprimento').click()
    
    // Verificar sucesso
    cy.verificarSucesso('Suprimento realizado')
    
    // Saldo do caixa deve aumentar
    cy.contains('Saldo: R$ 300,00').should('be.visible')
  })
  
  it('Deve fechar caixa e gerar relatório', () => {
    cy.abrirCaixa(100)
    
    // Realizar algumas vendas
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20)
    
    cy.adicionarProduto('7891234567891', 1)
    cy.finalizarVenda('credito')
    
    // Interceptar fechamento
    cy.intercept('POST', '/api/shifts/close/*').as('fecharCaixa')
    
    // Fechar caixa
    cy.fecharCaixa()
    
    // Verificar request
    cy.waitForAPI('@fecharCaixa')
    
    // Verificar relatório de fechamento
    cy.contains('Relatório de Fechamento').should('be.visible')
    cy.contains('Valor Inicial: R$ 100,00').should('be.visible')
    cy.contains('Vendas: 2').should('be.visible')
    cy.contains('Dinheiro:').should('be.visible')
    cy.contains('Crédito:').should('be.visible')
  })
  
  it('Não deve permitir vendas com caixa fechado', () => {
    // Tentar acessar PDV sem caixa aberto
    cy.visit('/pdv')
    
    // Deve exibir mensagem
    cy.contains('Abra o caixa para iniciar').should('be.visible')
    
    // Botão de finalizar deve estar desabilitado
    cy.contains('button', 'Finalizar Venda').should('be.disabled')
  })
  
  it('Deve listar histórico de turnos', () => {
    // Abrir e fechar caixa
    cy.abrirCaixa(100)
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20)
    cy.fecharCaixa()
    
    // Acessar histórico de turnos
    cy.contains('Relatórios').click()
    cy.contains('Turnos').click()
    
    // Verificar que turno aparece na lista
    cy.get('.lista-turnos').should('be.visible')
    cy.get('.lista-turnos tr').should('have.length.greaterThan', 0)
  })
  
  it('Deve exibir estatísticas do turno atual', () => {
    cy.abrirCaixa(100)
    
    // Realizar vendas
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20)
    
    cy.adicionarProduto('7891234567891', 2)
    cy.finalizarVenda('credito')
    
    // Abrir estatísticas do turno
    cy.contains('button', 'Estatísticas').click()
    
    // Verificar dados
    cy.contains('Vendas Realizadas: 2').should('be.visible')
    cy.contains('Ticket Médio:').should('be.visible')
  })
  
  it('Sangria deve exigir autorização de gerente', () => {
    cy.abrirCaixa(500)
    
    // Tentar sangria acima do limite (ex: R$ 500)
    cy.contains('button', 'Sangria').click()
    cy.get('input[name="valor_sangria"]').type('500')
    cy.contains('button', 'Confirmar Sangria').click()
    
    // Deve solicitar autorização
    cy.contains('Autorização de Gerente Necessária').should('be.visible')
    
    // Preencher senha do gerente
    cy.get('input[name="senha_gerente"]').type('senha_gerente')
    cy.contains('button', 'Autorizar').click()
    
    // Verificar sucesso
    cy.verificarSucesso('Sangria autorizada')
  })
})

