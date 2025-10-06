// ***********************************************************
// Support file do Cypress - PDV Multi-Tenant
// Carrega automaticamente antes de cada teste
// ***********************************************************

// Import commands.js
import './commands'

// Desabilitar exceções não capturadas (para não quebrar testes)
Cypress.on('uncaught:exception', (err, runnable) => {
  // Retornar false previne que o Cypress falhe o teste
  // Útil para exceções de bibliotecas de terceiros
  return false
})

// Hook global antes de cada teste
beforeEach(() => {
  // Limpar localStorage e sessionStorage
  cy.clearLocalStorage()
  cy.clearCookies()
  
  // Log do teste atual
  cy.log(`Executando: ${Cypress.currentTest.title}`)
})

// Hook global após cada teste
afterEach(() => {
  // Screenshot se falhou
  if (Cypress.currentTest.state === 'failed') {
    cy.screenshot(`FAILED-${Cypress.currentTest.title}`)
  }
})

// Configurações globais
Cypress.config('defaultCommandTimeout', 10000)

