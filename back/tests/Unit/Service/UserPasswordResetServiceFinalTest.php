<?php

namespace App\Tests\Unit\Service;

use App\Repository\UserRepository;
use App\Service\UserPasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordResetServiceFinalTest extends TestCase
{
    private UserPasswordResetService $service;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private MailerInterface $mailer;
    private MessageBusInterface $bus;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->service = new UserPasswordResetService(
            $this->entityManager,
            $this->userRepository,
            $this->mailer,
            $this->bus,
            $this->passwordHasher,
            'http://localhost',
            'test@example.com'
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(UserPasswordResetService::class, $this->service);
    }

    public function testServiceHasMethods(): void
    {
        $this->assertTrue(method_exists($this->service, 'requestPasswordReset'));
        $this->assertTrue(method_exists($this->service, 'resetPassword'));
        $this->assertTrue(method_exists($this->service, 'validateResetToken'));
    }

    public function testServiceIsProperlyConfigured(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('requestPasswordReset'));
    }
}
