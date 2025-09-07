<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Message\Mailer\PasswordResetEmailMessage;
use App\Repository\UserRepository;
use App\Service\UserPasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserPasswordResetServiceTest extends TestCase
{
    // Classe anonyme pour implémenter MessageBusInterface sans problème d'Envelope
    private function createTestMessageBus(): MessageBusInterface
    {
        return new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message, $stamps);
            }
        };
    }
    private UserPasswordResetService $service;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private MailerInterface $mailer;
    private MessageBusInterface $bus;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private string $frontendUrl = 'https://example.com';
    private string $mailerFrom = 'test@example.com';

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->bus = $this->createTestMessageBus();
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new UserPasswordResetService(
            $this->em,
            $this->userRepository,
            $this->mailer,
            $this->bus,
            $this->passwordHasher,
            $this->frontendUrl,
            $this->mailerFrom,
            $this->validator
        );
    }

    public function testRequestPasswordResetSuccess(): void
    {
        $email = 'test@example.com';
        $user = $this->createMock(User::class);

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user found
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        // Mock user methods
        $user->expects($this->once())
            ->method('setPasswordResetToken')
            ->with($this->isType('string'));

        $user->expects($this->once())
            ->method('setPasswordResetRequestAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $user->method('getEmail')->willReturn($email);
        $user->method('getFirstName')->willReturn('John');

        // Mock flush
        $this->em->expects($this->once())
            ->method('flush');

        // Note: Nous utilisons un stub pour bus, donc pas d'expectations spécifiques

        $this->service->requestPasswordReset($email);
    }

    public function testRequestPasswordResetUserNotFound(): void
    {
        $email = 'notfound@example.com';

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user not found
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        // Should not flush or dispatch message
        $this->em->expects($this->never())
            ->method('flush');

        // Note: Nous utilisons un stub pour bus, donc pas d'expectations spécifiques

        $this->service->requestPasswordReset($email);
    }

    public function testRequestPasswordResetInvalidEmail(): void
    {
        $email = 'invalid-email';
        $violations = new ConstraintViolationList();
        $violations->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);

        $this->service->requestPasswordReset($email);
    }

    public function testResetPasswordSuccess(): void
    {
        $token = 'valid-token';
        $newPassword = 'NewPassword123!';
        $confirmPassword = 'NewPassword123!';
        $hashedPassword = 'hashed-password';

        $user = $this->createMock(User::class);
        $user->method('getPasswordResetRequestAt')
            ->willReturn(new \DateTimeImmutable('-30 minutes')); // Token not expired

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user found with token
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn($user);

        // Mock password hashing
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $newPassword)
            ->willReturn($hashedPassword);

        // Mock user methods
        $user->expects($this->once())
            ->method('setPasswordResetToken')
            ->with(null);

        $user->expects($this->once())
            ->method('setPasswordResetRequestAt')
            ->with(null);

        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        // Mock flush
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->resetPassword($token, $newPassword, $confirmPassword);

        $this->assertTrue($result);
    }

    public function testResetPasswordMismatch(): void
    {
        $token = 'valid-token';
        $newPassword = 'NewPassword123!';
        $confirmPassword = 'DifferentPassword123!';

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->service->resetPassword($token, $newPassword, $confirmPassword);

        $this->assertFalse($result);
    }

    public function testResetPasswordInvalidPassword(): void
    {
        $token = 'valid-token';
        $newPassword = 'weak'; // Invalid password
        $confirmPassword = 'weak';

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->service->resetPassword($token, $newPassword, $confirmPassword);

        $this->assertFalse($result);
    }

    public function testResetPasswordInvalidToken(): void
    {
        $token = 'invalid-token';
        $newPassword = 'NewPassword123!';
        $confirmPassword = 'NewPassword123!';

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user not found
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn(null);

        $result = $this->service->resetPassword($token, $newPassword, $confirmPassword);

        $this->assertFalse($result);
    }

    public function testResetPasswordExpiredToken(): void
    {
        $token = 'expired-token';
        $newPassword = 'NewPassword123!';
        $confirmPassword = 'NewPassword123!';

        $user = $this->createMock(User::class);
        $user->method('getPasswordResetRequestAt')
            ->willReturn(new \DateTimeImmutable('-2 hours')); // Token expired

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user found with expired token
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn($user);

        $result = $this->service->resetPassword($token, $newPassword, $confirmPassword);

        $this->assertFalse($result);
    }

    public function testResetPasswordValidationError(): void
    {
        $token = 'valid-token';
        $newPassword = ''; // Empty password
        $confirmPassword = '';

        $violations = new ConstraintViolationList();
        $violations->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);

        $this->service->resetPassword($token, $newPassword, $confirmPassword);
    }

    public function testSendPasswordResetEmail(): void
    {
        $email = 'test@example.com';
        $firstName = 'John';
        $token = 'test-token';

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($firstName, $token) {
                $context = $email->getContext();
                return $email->getTo()[0]->getAddress() === 'test@example.com'
                    && $email->getSubject() === 'Réinitialisation de votre mot de passe'
                    && $context['firstName'] === $firstName
                    && str_contains($context['resetUrl'], $token);
            }));

        $this->service->sendPasswordResetEmail($email, $firstName, $token);
    }

    /**
     * Test des validations de mot de passe
     */
    public function testPasswordValidationTooShort(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPasswordValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'short');
        $this->assertFalse($result);
    }

    public function testPasswordValidationNoUppercase(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPasswordValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'lowercase123!');
        $this->assertFalse($result);
    }

    public function testPasswordValidationNoLowercase(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPasswordValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'UPPERCASE123!');
        $this->assertFalse($result);
    }

    public function testPasswordValidationNoDigit(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPasswordValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Password!');
        $this->assertFalse($result);
    }

    public function testPasswordValidationNoSpecialChar(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPasswordValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Password123');
        $this->assertFalse($result);
    }

    public function testPasswordValidationValid(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPasswordValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'ValidPassword123!');
        $this->assertTrue($result);
    }

    public function testTokenNotExpired(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPasswordResetRequestAt')
            ->willReturn(new \DateTimeImmutable('-30 minutes'));

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('tokenExpired');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertFalse($result);
    }

    public function testTokenExpired(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPasswordResetRequestAt')
            ->willReturn(new \DateTimeImmutable('-2 hours'));

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('tokenExpired');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertTrue($result);
    }

    public function testTokenExpiredNoRequestDate(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPasswordResetRequestAt')
            ->willReturn(null);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('tokenExpired');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertTrue($result);
    }
}
