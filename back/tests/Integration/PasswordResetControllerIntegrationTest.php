<?php

namespace App\Tests\Integration;

use App\Controller\PasswordResetController;
use App\Service\UserPasswordResetService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class PasswordResetControllerIntegrationTest extends KernelTestCase
{
    private PasswordResetController $controller;
    private UserPasswordResetService $resetService;
    private UserService $userService;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        $this->resetService = $this->createMock(UserPasswordResetService::class);
        $this->userService = $this->createMock(UserService::class);
        
        $this->controller = new PasswordResetController(
            $this->resetService,
            $this->userService
        );
        
        // Injecter le container pour que les méthodes json() fonctionnent
        $this->controller->setContainer($container);
    }

    public function testForgotPasswordSuccess(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'recaptchaToken' => 'valid_token'
        ]));

        $this->userService->expects($this->once())
            ->method('verifyCaptcha')
            ->with('valid_token', 'password_reset')
            ->willReturn(true);

        $this->resetService->expects($this->once())
            ->method('requestPasswordReset')
            ->with('test@example.com');

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('email a été envoyé', $responseData['message']);
    }

    public function testForgotPasswordMissingCaptchaToken(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com'
        ]));

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Token CAPTCHA requis', $responseData['error']);
    }

    public function testForgotPasswordEmptyCaptchaToken(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'recaptchaToken' => ''
        ]));

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Token CAPTCHA requis', $responseData['error']);
    }

    public function testForgotPasswordInvalidCaptcha(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'recaptchaToken' => 'invalid_token'
        ]));

        $this->userService->expects($this->once())
            ->method('verifyCaptcha')
            ->with('invalid_token', 'password_reset')
            ->willReturn(false);

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Échec de la vérification CAPTCHA', $responseData['error']);
    }

    public function testForgotPasswordValidationException(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'email' => 'invalid-email',
            'recaptchaToken' => 'valid_token'
        ]));

        $this->userService->expects($this->once())
            ->method('verifyCaptcha')
            ->willReturn(true);

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')->willReturn('Email invalide');
        
        $violations = new ConstraintViolationList([$violation]);
        $validationException = new ValidationFailedException('email', $violations);

        $this->resetService->expects($this->once())
            ->method('requestPasswordReset')
            ->willThrowException($validationException);

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('details', $responseData);
        $this->assertEquals('Données invalides', $responseData['error']);
        $this->assertContains('Email invalide', $responseData['details']);
    }

    public function testForgotPasswordGeneralException(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'recaptchaToken' => 'valid_token'
        ]));

        $this->userService->expects($this->once())
            ->method('verifyCaptcha')
            ->willReturn(true);

        $this->resetService->expects($this->once())
            ->method('requestPasswordReset')
            ->willThrowException(new \Exception('Service indisponible'));

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Service indisponible', $responseData['error']);
    }

    public function testResetPasswordSuccess(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'password' => 'newPassword123',
            'confirmPassword' => 'newPassword123'
        ]));

        $this->resetService->expects($this->once())
            ->method('resetPassword')
            ->with('valid_token', 'newPassword123', 'newPassword123')
            ->willReturn(true);

        $response = $this->controller->resetPassword('valid_token', $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Mot de passe réinitialisé avec succès', $responseData['message']);
    }

    public function testResetPasswordInvalidToken(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'password' => 'newPassword123',
            'confirmPassword' => 'newPassword123'
        ]));

        $this->resetService->expects($this->once())
            ->method('resetPassword')
            ->with('invalid_token', 'newPassword123', 'newPassword123')
            ->willReturn(false);

        $response = $this->controller->resetPassword('invalid_token', $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Lien invalide ou expiré', $responseData['error']);
    }

    public function testResetPasswordValidationException(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'password' => 'weak',
            'confirmPassword' => 'weak'
        ]));

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')->willReturn('Le mot de passe est trop faible');
        
        $violations = new ConstraintViolationList([$violation]);
        $validationException = new ValidationFailedException('password', $violations);

        $this->resetService->expects($this->once())
            ->method('resetPassword')
            ->willThrowException($validationException);

        $response = $this->controller->resetPassword('valid_token', $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('details', $responseData);
        $this->assertEquals('Données invalides', $responseData['error']);
        $this->assertContains('Le mot de passe est trop faible', $responseData['details']);
    }

    public function testResetPasswordGeneralException(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode([
            'password' => 'newPassword123',
            'confirmPassword' => 'newPassword123'
        ]));

        $this->resetService->expects($this->once())
            ->method('resetPassword')
            ->willThrowException(new \Exception('Erreur de base de données'));

        $response = $this->controller->resetPassword('valid_token', $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Erreur de base de données', $responseData['error']);
    }

    public function testForgotPasswordInvalidJson(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Token CAPTCHA requis', $responseData['error']);
    }

    public function testResetPasswordInvalidJson(): void
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->resetPassword('valid_token', $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Trying to access array offset', $responseData['error']);
    }
}
