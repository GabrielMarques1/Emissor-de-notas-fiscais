const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost',
    supportFile: 'cypress/support/e2e.js',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    videosFolder: 'cypress/videos',
    screenshotsFolder: 'cypress/screenshots',
    
    // Timeouts
    defaultCommandTimeout: 10000,
    pageLoadTimeout: 30000,
    requestTimeout: 10000,
    
    // Viewport
    viewportWidth: 1280,
    viewportHeight: 720,
    
    // Comportamento
    watchForFileChanges: true,
    video: true,
    screenshotOnRunFailure: true,
    
    // Retry
    retries: {
      runMode: 2,
      openMode: 0
    },
    
    // Variáveis de ambiente
    env: {
      // Tenant A (para testes)
      TENANT_A_CONTADOR: 1,
      TENANT_A_EMPRESA: 100,
      TENANT_A_USER: 'pdv1@teste.com',
      TENANT_A_PASS: 'senha123',
      
      // Tenant B (para testes de isolamento)
      TENANT_B_CONTADOR: 2,
      TENANT_B_EMPRESA: 200,
      TENANT_B_USER: 'pdv2@teste.com',
      TENANT_B_PASS: 'senha123',
      
      // Configurações
      API_URL: 'http://localhost/api',
    },
    
    setupNodeEvents(on, config) {
      // Implementar listeners de eventos
      on('task', {
        log(message) {
          console.log(message)
          return null
        }
      })
      
      return config
    },
  },
})

