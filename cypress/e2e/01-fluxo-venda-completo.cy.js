/**
 * Teste E2E: Fluxo Completo de Venda
 * 
 * Testa o fluxo end-to-end de uma venda no PDV:
 * 1. Login
 * 2. Abrir caixa
 * 3. Adicionar produtos
 * 4. Finalizar venda
 * 5. Fechar caixa
 */

describe('Fluxo Completo de Venda', () => {
  beforeEach(() => {
    cy.loginTenantA()
  })
  
  it('Deve realizar venda completa em dinheiro', () => {
    // Abrir caixa
    cy.abrirCaixa(100.00)
    
    // Adicionar produtos ao carrinho
    cy.adicionarProduto('7891234567890', 2) // 2x Produto A
    cy.adicionarProduto('7891234567891', 1) // 1x Produto B
    
    // Verificar quantidade de itens
    cy.verificarQuantidadeItens(3)
    
    // Verificar total (assumindo R$ 10,00 cada)
    cy.verificarTotal(30.00)
    
    // Finalizar venda em dinheiro
    cy.finalizarVenda('dinheiro', 50.00)
    
    // Verificar troco
    cy.contains('Troco: R$ 20,00').should('be.visible')
    
    // Fechar recibo
    cy.contains('button', 'Fechar').click()
    
    // Carrinho deve estar vazio
    cy.verificarQuantidadeItens(0)
  })
  
  it('Deve realizar venda com cartão de crédito', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar produto
    cy.adicionarProduto('7891234567890', 1)
    
    // Finalizar com crédito
    cy.finalizarVenda('credito')
    
    // Verificar mensagem de sucesso
    cy.verificarSucesso('Venda finalizada com sucesso')
    
    // Verificar que nova venda pode ser iniciada
    cy.get('#codigo_de_barras').should('be.visible')
  })
  
  it('Deve realizar venda com PIX', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar produto
    cy.adicionarProduto('7891234567890', 3)
    
    // Finalizar com PIX
    cy.finalizarVenda('pix')
    
    // Verificar QR Code
    cy.get('.qrcode-pix').should('be.visible')
    
    // Simular pagamento recebido
    cy.contains('button', 'Pagamento Recebido').click()
    
    // Verificar sucesso
    cy.verificarSucesso('Venda finalizada')
  })
  
  it('Deve remover item do carrinho', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar produtos
    cy.adicionarProduto('7891234567890', 1)
    cy.adicionarProduto('7891234567891', 1)
    
    // Verificar 2 itens
    cy.verificarQuantidadeItens(2)
    
    // Remover primeiro item
    cy.removerItem(0)
    
    // Verificar 1 item
    cy.verificarQuantidadeItens(1)
  })
  
  it('Deve limpar carrinho completo', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar vários produtos
    cy.adicionarProduto('7891234567890', 5)
    
    cy.verificarQuantidadeItens(5)
    
    // Limpar carrinho
    cy.limparCarrinho()
    
    // Verificar vazio
    cy.verificarQuantidadeItens(0)
    cy.verificarTotal(0.00)
  })
  
  it('Deve aplicar desconto na venda', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar produto
    cy.adicionarProduto('7891234567890', 1) // R$ 10,00
    
    // Aplicar desconto de 10%
    cy.contains('button', 'Desconto').click()
    cy.get('input[name="desconto_percentual"]').type('10')
    cy.contains('button', 'Aplicar').click()
    
    // Verificar total com desconto
    cy.verificarTotal(9.00)
    
    // Finalizar
    cy.finalizarVenda('dinheiro', 10.00)
  })
  
  it('Deve buscar cliente por CPF e vincular à venda', () => {
    cy.abrirCaixa(100.00)
    
    // Buscar cliente
    cy.contains('button', 'Buscar Cliente').click()
    cy.get('input[name="cpf"]').type('12345678900')
    cy.contains('button', 'Buscar').click()
    
    // Selecionar cliente
    cy.contains('João da Silva').click()
    
    // Verificar cliente selecionado
    cy.contains('Cliente: João da Silva').should('be.visible')
    
    // Adicionar produto e finalizar
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20.00)
  })
  
  it('Deve cancelar venda em andamento', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar produtos
    cy.adicionarProduto('7891234567890', 3)
    
    // Cancelar venda
    cy.contains('button', 'Cancelar Venda').click()
    cy.contains('button', 'Sim, Cancelar').click()
    
    // Carrinho vazio
    cy.verificarQuantidadeItens(0)
  })
  
  it('Deve suspender e recuperar venda', () => {
    cy.abrirCaixa(100.00)
    
    // Adicionar produtos
    cy.adicionarProduto('7891234567890', 2)
    cy.adicionarProduto('7891234567891', 1)
    
    // Suspender venda
    cy.contains('button', 'Suspender Venda').click()
    cy.get('input[name="motivo"]').type('Cliente voltará mais tarde')
    cy.contains('button', 'Confirmar').click()
    
    // Verificar venda suspensa
    cy.verificarSucesso('Venda suspensa')
    cy.verificarQuantidadeItens(0)
    
    // Recuperar venda
    cy.contains('button', 'Vendas Suspensas').click()
    cy.get('.lista-vendas-suspensas tr').first().find('button').contains('Recuperar').click()
    
    // Verificar itens recuperados
    cy.verificarQuantidadeItens(3)
  })
  
  it('Deve fechar caixa com vendas realizadas', () => {
    cy.abrirCaixa(100.00)
    
    // Realizar 2 vendas
    cy.adicionarProduto('7891234567890', 1)
    cy.finalizarVenda('dinheiro', 20.00)
    
    cy.adicionarProduto('7891234567891', 1)
    cy.finalizarVenda('credito')
    
    // Fechar caixa
    cy.fecharCaixa()
    
    // Verificar relatório de fechamento
    cy.contains('Relatório de Fechamento').should('be.visible')
    cy.contains('Vendas: 2').should('be.visible')
    cy.contains('Valor Inicial: R$ 100,00').should('be.visible')
  })
})

