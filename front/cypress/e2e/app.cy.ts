describe('Blob Application', () => {
  beforeEach(() => {
    cy.visit('/');
  });

  it('should display the application title', () => {
    cy.contains('Blob').should('be.visible');
  });

  it('should navigate to login page', () => {
    cy.visit('/connexion');
    cy.url().should('include', '/connexion');
    cy.contains('Connexion').should('be.visible');
  });

  it('should navigate to registration page', () => {
    cy.visit('/inscription');
    cy.url().should('include', '/inscription');
    cy.contains('Inscription').should('be.visible');
  });

  it('should display privacy consent banner', () => {
    cy.visit('/');
    cy.get('.privacy-consent-banner').should('be.visible');
  });
});








