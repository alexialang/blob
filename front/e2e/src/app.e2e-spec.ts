import { browser, by, element, ExpectedConditions } from 'protractor';

describe('Blob Quiz App E2E', () => {
  
  beforeEach(async () => {
    await browser.get('/');
  });

  describe('Authentication Flow', () => {
    it('should display login page by default', async () => {
      const title = await browser.getTitle();
      expect(title).toContain('Blob Quiz');
      
      const loginForm = element(by.css('app-login'));
      expect(await loginForm.isPresent()).toBe(true);
    });

    it('should allow user to navigate to registration', async () => {
      const registerLink = element(by.css('a[href="/inscription"]'));
      await registerLink.click();
      
      await browser.wait(ExpectedConditions.urlContains('/inscription'), 5000);
      expect(await browser.getCurrentUrl()).toContain('/inscription');
      
      const registrationForm = element(by.css('app-registration'));
      expect(await registrationForm.isPresent()).toBe(true);
    });

    it('should show validation errors for invalid login', async () => {
      const emailInput = element(by.css('input[type="email"]'));
      const passwordInput = element(by.css('input[type="password"]'));
      const submitButton = element(by.css('button[type="submit"]'));
      
      await emailInput.sendKeys('invalid-email');
      await passwordInput.sendKeys('short');
      await submitButton.click();
      
      const errorMessages = element.all(by.css('.error-message'));
      expect(await errorMessages.count()).toBeGreaterThan(0);
    });
  });

  describe('Quiz Flow', () => {
    beforeEach(async () => {
      // Login with test user
      await loginAsTestUser();
    });

    it('should display quiz cards after login', async () => {
      await browser.wait(ExpectedConditions.urlContains('/quiz'), 10000);
      
      const quizCards = element.all(by.css('app-quiz-card'));
      expect(await quizCards.count()).toBeGreaterThan(0);
    });

    it('should start a quiz when clicking play button', async () => {
      const firstQuizCard = element.all(by.css('app-quiz-card')).first();
      const playButton = firstQuizCard.element(by.css('.play-button'));
      
      await playButton.click();
      
      await browser.wait(ExpectedConditions.urlContains('/play'), 10000);
      expect(await browser.getCurrentUrl()).toContain('/play');
      
      const gameComponent = element(by.css('app-quiz-game'));
      expect(await gameComponent.isPresent()).toBe(true);
    });

    it('should display question and answers in quiz game', async () => {
      await startQuiz();
      
      const questionText = element(by.css('.question-text'));
      expect(await questionText.isPresent()).toBe(true);
      expect(await questionText.getText()).not.toBe('');
      
      const answerOptions = element.all(by.css('.answer-option'));
      expect(await answerOptions.count()).toBeGreaterThan(1);
    });

    it('should allow answer selection and submission', async () => {
      await startQuiz();
      
      const firstAnswer = element.all(by.css('.answer-option')).first();
      await firstAnswer.click();
      
      expect(await firstAnswer.getAttribute('class')).toContain('selected');
      
      const submitButton = element(by.css('.submit-answer'));
      expect(await submitButton.isEnabled()).toBe(true);
      
      await submitButton.click();
      
      // Should show feedback
      const feedback = element(by.css('.feedback-overlay'));
      await browser.wait(ExpectedConditions.visibilityOf(feedback), 5000);
      expect(await feedback.isDisplayed()).toBe(true);
    });

    it('should progress through quiz questions', async () => {
      await startQuiz();
      
      const initialQuestionNumber = await element(by.css('.question-counter')).getText();
      
      // Answer first question
      await answerCurrentQuestion();
      
      // Wait for next question
      await browser.wait(ExpectedConditions.stalenessOf(element(by.css('.feedback-overlay'))), 10000);
      
      const newQuestionNumber = await element(by.css('.question-counter')).getText();
      expect(newQuestionNumber).not.toBe(initialQuestionNumber);
    });

    it('should show quiz results at the end', async () => {
      await completeQuiz();
      
      const resultsComponent = element(by.css('.quiz-results'));
      await browser.wait(ExpectedConditions.visibilityOf(resultsComponent), 10000);
      expect(await resultsComponent.isPresent()).toBe(true);
      
      const scoreDisplay = element(by.css('.final-score'));
      expect(await scoreDisplay.isPresent()).toBe(true);
      
      const leaderboard = element(by.css('.leaderboard'));
      expect(await leaderboard.isPresent()).toBe(true);
    });
  });

  describe('Multiplayer Flow', () => {
    beforeEach(async () => {
      await loginAsTestUser();
    });

    it('should allow creating multiplayer room', async () => {
      await navigateToQuiz();
      
      const multiplayerButton = element(by.css('.multiplayer-button'));
      await multiplayerButton.click();
      
      await browser.wait(ExpectedConditions.urlContains('/multiplayer/create'), 5000);
      
      const createRoomForm = element(by.css('.create-room-form'));
      expect(await createRoomForm.isPresent()).toBe(true);
      
      const maxPlayersInput = element(by.css('input[name="maxPlayers"]'));
      await maxPlayersInput.clear();
      await maxPlayersInput.sendKeys('4');
      
      const createButton = element(by.css('.create-room-button'));
      await createButton.click();
      
      await browser.wait(ExpectedConditions.urlContains('/multiplayer/room'), 10000);
      expect(await browser.getCurrentUrl()).toContain('/multiplayer/room');
    });

    it('should display waiting room for multiplayer', async () => {
      await createMultiplayerRoom();
      
      const waitingRoom = element(by.css('.waiting-room'));
      expect(await waitingRoom.isPresent()).toBe(true);
      
      const playersList = element(by.css('.players-list'));
      expect(await playersList.isPresent()).toBe(true);
      
      const roomCode = element(by.css('.room-code'));
      expect(await roomCode.isPresent()).toBe(true);
    });
  });

  describe('Admin Features', () => {
    beforeEach(async () => {
      await loginAsAdmin();
    });

    it('should allow access to user management', async () => {
      const userManagementLink = element(by.css('a[href="/gestion-utilisateur"]'));
      await userManagementLink.click();
      
      await browser.wait(ExpectedConditions.urlContains('/gestion-utilisateur'), 5000);
      
      const userTable = element(by.css('.user-management-table'));
      expect(await userTable.isPresent()).toBe(true);
    });

    it('should allow quiz creation', async () => {
      const quizManagementLink = element(by.css('a[href="/gestion-quiz"]'));
      await quizManagementLink.click();
      
      await browser.wait(ExpectedConditions.urlContains('/gestion-quiz'), 5000);
      
      const createQuizButton = element(by.css('.create-quiz-button'));
      await createQuizButton.click();
      
      await browser.wait(ExpectedConditions.urlContains('/creation-quiz'), 5000);
      
      const quizCreationForm = element(by.css('.quiz-creation-form'));
      expect(await quizCreationForm.isPresent()).toBe(true);
    });
  });

  describe('Responsive Design', () => {
    it('should work on mobile viewport', async () => {
      await browser.driver.manage().window().setSize(375, 667); // iPhone 6/7/8 size
      
      await browser.get('/');
      
      const mobileMenu = element(by.css('.mobile-menu-toggle'));
      expect(await mobileMenu.isPresent()).toBe(true);
      
      await mobileMenu.click();
      
      const navigationMenu = element(by.css('.mobile-navigation'));
      expect(await navigationMenu.isDisplayed()).toBe(true);
    });

    it('should work on tablet viewport', async () => {
      await browser.driver.manage().window().setSize(768, 1024); // iPad size
      
      await browser.get('/');
      await loginAsTestUser();
      
      const quizCards = element.all(by.css('app-quiz-card'));
      expect(await quizCards.count()).toBeGreaterThan(0);
      
      // Ensure cards are properly arranged in tablet layout
      const firstCard = quizCards.first();
      expect(await firstCard.isDisplayed()).toBe(true);
    });
  });

  // Helper functions
  async function loginAsTestUser() {
    const emailInput = element(by.css('input[type="email"]'));
    const passwordInput = element(by.css('input[type="password"]'));
    const submitButton = element(by.css('button[type="submit"]'));
    
    await emailInput.sendKeys('test@example.com');
    await passwordInput.sendKeys('password123');
    await submitButton.click();
    
    await browser.wait(ExpectedConditions.urlContains('/quiz'), 10000);
  }

  async function loginAsAdmin() {
    const emailInput = element(by.css('input[type="email"]'));
    const passwordInput = element(by.css('input[type="password"]'));
    const submitButton = element(by.css('button[type="submit"]'));
    
    await emailInput.sendKeys('admin@example.com');
    await passwordInput.sendKeys('admin123');
    await submitButton.click();
    
    await browser.wait(ExpectedConditions.urlContains('/quiz'), 10000);
  }

  async function navigateToQuiz() {
    await browser.get('/quiz');
    await browser.wait(ExpectedConditions.presenceOf(element(by.css('app-quiz-cards'))), 5000);
  }

  async function startQuiz() {
    await navigateToQuiz();
    const firstQuizCard = element.all(by.css('app-quiz-card')).first();
    const playButton = firstQuizCard.element(by.css('.play-button'));
    await playButton.click();
    await browser.wait(ExpectedConditions.urlContains('/play'), 10000);
  }

  async function answerCurrentQuestion() {
    const firstAnswer = element.all(by.css('.answer-option')).first();
    await firstAnswer.click();
    
    const submitButton = element(by.css('.submit-answer'));
    await submitButton.click();
    
    // Wait for feedback to appear and disappear
    const feedback = element(by.css('.feedback-overlay'));
    await browser.wait(ExpectedConditions.visibilityOf(feedback), 5000);
    await browser.wait(ExpectedConditions.invisibilityOf(feedback), 10000);
  }

  async function completeQuiz() {
    await startQuiz();
    
    // Answer all questions (assuming 5 questions for test quiz)
    for (let i = 0; i < 5; i++) {
      await answerCurrentQuestion();
      await browser.sleep(1000); // Small delay between questions
    }
  }

  async function createMultiplayerRoom() {
    await navigateToQuiz();
    
    const multiplayerButton = element(by.css('.multiplayer-button'));
    await multiplayerButton.click();
    
    const createButton = element(by.css('.create-room-button'));
    await createButton.click();
    
    await browser.wait(ExpectedConditions.urlContains('/multiplayer/room'), 10000);
  }
});

