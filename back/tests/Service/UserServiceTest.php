<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|UserRepository $userRepository;
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private MockObject|MessageBusInterface $bus;
    private MockObject|MailerInterface $mailer;
    private MockObject|HttpClientInterface $httpClient;
    private MockObject|ValidatorInterface $validator;
    private MockObject|ParameterBagInterface $params;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->userService = new UserService(
            'test@example.com',
            'fake-recaptcha-key',
            $this->entityManager,
            $this->userRepository,
            $this->passwordHasher,
            $this->bus,
            $this->mailer,
            $this->httpClient,
            $this->params,
            $this->validator
        );
    }

    public function testCreateUserWithValidData(): void
    {
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123'
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn(null);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->bus
            ->expects($this->never())
            ->method('dispatch');

        $result = $this->userService->create($userData);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals('Doe', $result->getLastName());
        $this->assertEquals('john.doe@example.com', $result->getEmail());
    }

    public function testCreateUserWithExistingEmail(): void
    {
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123'
        ];

        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cet email est déjà utilisé');

        $this->userService->create($userData);
    }

    public function testVerifyCaptchaSuccess(): void
    {
        $token = 'valid-token';
        
        $responseData = ['success' => true];
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify')
            ->willReturn($response);

        $result = $this->userService->verifyCaptcha($token);

        $this->assertTrue($result);
    }

    public function testVerifyCaptchaFailure(): void
    {
        $token = 'invalid-token';
        
        $responseData = ['success' => false];
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify')
            ->willReturn($response);

        $result = $this->userService->verifyCaptcha($token);

        $this->assertFalse($result);
    }
}
