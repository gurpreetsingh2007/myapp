// cypress/e2e/app.cy.ts
describe('Vue Application Tests', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  describe('Login Test', () => {
    it('should log in with valid credentials', () => {
      // Visit the login page

      // Type in the username and password
      cy.get('#username').type('your-username') // Replace 'your-username' with the actual username
      cy.get('#password').type('your-password') // Replace 'your-password' with the actual password

      // Click the login button
      cy.get('#login-btn')
        .should('be.visible') // Check if the button is visible
        .and('not.be.disabled') // Ensure it's not disabled
        .click()

      // Verify that login was successful, for example by checking if the user is redirected to the dashboard
      cy.url().should('include', '/dashboard') // Replace '/dashboard' with the actual post-login URL
      cy.get('#danger').should('contain', 'Danger') // Replace this with an actual element that confirms successful login
    })
  })
})
