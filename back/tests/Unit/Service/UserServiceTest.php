<?php

namespace App\Tests\Unit\Service;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\Permission;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserServiceTest extends TestCase
{
    private UserService $service;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->params->method('get')->willReturnMap([
            ['frontend_url', 'http://localhost:3000'],
        ]);

        $this->service = new UserService(
            'test@example.com', // mailerFrom
            'test-recaptcha-key', // recaptchaSecretKey
            $this->em,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailer,
            $this->httpClient,
            $this->params,
            $this->validator,
            $this->logger
        );
    }

    // ===== Tests pour list() =====

    public function testListWithoutDeleted(): void
    {
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['deletedAt' => null])
            ->willReturn($users);

        $result = $this->service->list(false);

        $this->assertSame($users, $result);
        $this->assertCount(2, $result);
    }

    public function testListWithDeleted(): void
    {
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
            $this->createMock(User::class), // Including deleted user
        ];

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($users);

        $result = $this->service->list(true);

        $this->assertSame($users, $result);
        $this->assertCount(3, $result);
    }

    // ===== Tests pour find() =====

    public function testFind(): void
    {
        $user = $this->createMock(User::class);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($user);

        $result = $this->service->find(123);

        $this->assertSame($user, $result);
    }

    public function testFindNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    // ===== Tests pour getUsersWithoutCompany() =====

    public function testGetUsersWithoutCompany(): void
    {
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with([
                'company' => null,
                'deletedAt' => null,
                'isActive' => true,
            ])
            ->willReturn($users);

        $result = $this->service->getUsersWithoutCompany();

        $this->assertSame($users, $result);
        $this->assertCount(2, $result);
    }

    public function testGetUsersWithoutCompanyEmpty(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with([
                'company' => null,
                'deletedAt' => null,
                'isActive' => true,
            ])
            ->willReturn([]);

        $result = $this->service->getUsersWithoutCompany();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour getUsersFromOtherCompanies() =====

    public function testGetUsersFromOtherCompanies(): void
    {
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        $this->userRepository->expects($this->once())
            ->method('findUsersFromOtherCompanies')
            ->with(123)
            ->willReturn($users);

        $result = $this->service->getUsersFromOtherCompanies(123);

        $this->assertSame($users, $result);
        $this->assertCount(2, $result);
    }

    // ===== Tests pour getUsersByCompany() =====
    // Note: Ce test est complexe à cause des conflits de mocks avec findBy()
    // Il serait mieux testé avec des tests d'intégration

    // ===== Tests pour getActiveUsersForMultiplayer() =====

    public function testGetActiveUsersForMultiplayer(): void
    {
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        $this->userRepository->expects($this->once())
            ->method('findActiveUsersForMultiplayer')
            ->willReturn($users);

        $result = $this->service->getActiveUsersForMultiplayer();

        $this->assertSame($users, $result);
        $this->assertCount(2, $result);
    }

    // ===== Tests pour confirmToken() =====

    public function testConfirmTokenSuccess(): void
    {
        $user = $this->createMock(User::class);
        $token = 'valid-token-123';

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['confirmationToken' => $token])
            ->willReturn($user);

        $user->expects($this->once())->method('setIsVerified')->with(true);
        $user->expects($this->once())->method('setConfirmationToken')->with(null);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->confirmToken($token);

        $this->assertSame($user, $result);
    }

    public function testConfirmTokenNotFound(): void
    {
        $token = 'invalid-token';

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['confirmationToken' => $token])
            ->willReturn(null);

        $result = $this->service->confirmToken($token);

        $this->assertNull($result);
    }

    public function testConfirmTokenAlreadyConfirmed(): void
    {
        $user = $this->createMock(User::class);
        $token = 'already-confirmed-token';

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['confirmationToken' => $token])
            ->willReturn($user);

        $user->method('isVerified')->willReturn(true);

        $result = $this->service->confirmToken($token);

        $this->assertNull($result);
    }

    public function testConfirmTokenEmptyToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le token de confirmation ne peut pas être vide');

        $this->service->confirmToken('');
    }

    public function testConfirmTokenTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le token de confirmation est invalide');

        $this->service->confirmToken('short');
    }

    // ===== Tests pour create() =====

    public function testCreateSuccess(): void
    {
        $data = [
            'email' => 'test@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'password123',
            'is_admin' => false,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn(null);

        // Mock password hashing
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->create($data);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateEmailAlreadyExists(): void
    {
        $data = [
            'email' => 'existing@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'password123',
        ];

        $existingUser = $this->createMock(User::class);

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cet email est déjà utilisé');

        $this->service->create($data);
    }

    public function testCreateWithAdminRole(): void
    {
        $data = [
            'email' => 'admin@example.com',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'password' => 'password123',
            'is_admin' => true,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'admin@example.com'])
            ->willReturn(null);

        // Mock password hashing
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->create($data);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateException(): void
    {
        $data = [
            'email' => 'test@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'password123',
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn(null);

        // Mock password hashing
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->create($data);
    }

    // ===== Tests pour sendEmail() =====

    public function testSendEmail(): void
    {
        $email = 'test@example.com';
        $firstName = 'John';
        $token = 'confirmation-token-123';

        $this->mailer->expects($this->once())
            ->method('send');

        // This method doesn't return anything, just verify it doesn't throw
        $this->service->sendEmail($email, $firstName, $token);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    // ===== Tests pour verifyCaptcha() =====

    public function testVerifyCaptchaSuccess(): void
    {
        $token = 'valid-captcha-token';
        $action = 'register';

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'success' => true,
            'score' => 0.9,
            'action' => 'register',
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify')
            ->willReturn($response);

        $result = $this->service->verifyCaptcha($token, $action);

        $this->assertTrue($result);
    }

    public function testVerifyCaptchaFailure(): void
    {
        $token = 'invalid-captcha-token';
        $action = 'register';

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify')
            ->willReturn($response);

        $result = $this->service->verifyCaptcha($token, $action);

        $this->assertFalse($result);
    }

    public function testVerifyCaptchaLowScore(): void
    {
        $token = 'low-score-token';
        $action = 'register';

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'success' => true,
            'score' => 0.3, // Low score
            'action' => 'register',
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify')
            ->willReturn($response);

        $result = $this->service->verifyCaptcha($token, $action);

        $this->assertFalse($result);
    }

    public function testVerifyCaptchaWrongAction(): void
    {
        $token = 'valid-token';
        $action = 'register';

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'success' => true,
            'score' => 0.9,
            'action' => 'login', // Different action
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify')
            ->willReturn($response);

        $result = $this->service->verifyCaptcha($token, $action);

        $this->assertFalse($result);
    }

    public function testVerifyCaptchaException(): void
    {
        $token = 'valid-token';
        $action = 'register';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())
            ->method('error');

        $result = $this->service->verifyCaptcha($token, $action);

        $this->assertFalse($result);
    }

    // ===== Tests pour update() =====

    public function testUpdateSuccess(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'email' => 'newemail@example.com',
            'firstName' => 'UpdatedFirst',
            'lastName' => 'UpdatedLast',
            'roles' => ['ROLE_USER'],
            'password' => 'newpassword123',
            'isActive' => true,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock current email check
        $user->method('getEmail')->willReturn('old@example.com');

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'newemail@example.com'])
            ->willReturn(null);

        // Mock password hashing
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_new_password');

        // Mock user setters
        $user->expects($this->once())->method('setEmail')->with('newemail@example.com');
        $user->expects($this->once())->method('setFirstName')->with('UpdatedFirst');
        $user->expects($this->once())->method('setLastName')->with('UpdatedLast');
        $user->expects($this->once())->method('setRoles')->with(['ROLE_USER']);
        $user->expects($this->once())->method('setPassword')->with('hashed_new_password');
        $user->expects($this->once())->method('setIsActive')->with(true);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->update($user, $data);

        $this->assertSame($user, $result);
    }

    public function testUpdateEmailAlreadyExists(): void
    {
        $user = $this->createMock(User::class);
        $existingUser = $this->createMock(User::class);
        $data = [
            'email' => 'existing@example.com',
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock current email check
        $user->method('getEmail')->willReturn('old@example.com');

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cet email est déjà utilisé');

        $this->service->update($user, $data);
    }

    public function testUpdateWithAdminRole(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'is_admin' => true,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock current roles
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->expects($this->once())->method('setRoles')->with(['ROLE_USER', 'ROLE_ADMIN']);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->update($user, $data);

        $this->assertSame($user, $result);
    }

    public function testUpdateRemoveAdminRole(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'is_admin' => false,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock current roles
        $user->method('getRoles')->willReturn(['ROLE_USER', 'ROLE_ADMIN']);
        $user->expects($this->once())->method('setRoles')->with(['ROLE_USER']);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->update($user, $data);

        $this->assertSame($user, $result);
    }

    public function testUpdateException(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'firstName' => 'Updated',
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $user->expects($this->once())->method('setFirstName')->with('Updated');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->update($user, $data);
    }

    // ===== Tests pour updateProfile() =====

    public function testUpdateProfileSuccess(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'pseudo' => 'NewPseudo',
            'firstName' => 'NewFirst',
            'lastName' => 'NewLast',
            'avatarShape' => 'circle',
            'avatarColor' => 'blue',
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock current avatar
        $user->method('getAvatar')->willReturn('{"shape":"square","color":"red"}');

        // Mock user setters
        $user->expects($this->once())->method('setPseudo')->with('NewPseudo');
        $user->expects($this->once())->method('setFirstName')->with('NewFirst');
        $user->expects($this->once())->method('setLastName')->with('NewLast');
        $user->expects($this->once())->method('setAvatar')->with('{"shape":"circle","color":"blue"}');

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateProfile($user, $data);

        $this->assertSame($user, $result);
    }

    public function testUpdateProfileWithNullAvatar(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'avatarShape' => 'triangle',
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock null avatar
        $user->method('getAvatar')->willReturn(null);
        $user->expects($this->once())->method('setAvatar')->with('{"shape":"triangle"}');

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateProfile($user, $data);

        $this->assertSame($user, $result);
    }

    public function testUpdateProfileEmailAlreadyExists(): void
    {
        $user = $this->createMock(User::class);
        $existingUser = $this->createMock(User::class);
        $data = [
            'email' => 'existing@example.com',
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock current email check
        $user->method('getEmail')->willReturn('old@example.com');

        // Mock existing user check
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cet email est déjà utilisé');

        $this->service->updateProfile($user, $data);
    }

    // ===== Tests pour anonymizeUser() =====

    public function testAnonymizeUserSuccess(): void
    {
        $user = $this->createMock(User::class);

        // Mock user ID
        $user->method('getId')->willReturn(123);

        // Mock user setters
        $user->expects($this->once())->method('setDeletedAt');
        $user->expects($this->once())->method('setIsActive')->with(false);
        $user->expects($this->once())->method('setEmail')->with('anon_123@example.com');
        $user->expects($this->once())->method('setFirstName')->with('Utilisateur');
        $user->expects($this->once())->method('setLastName')->with('Anonyme');
        $user->expects($this->once())->method('setPseudo');
        $user->expects($this->once())->method('setPassword')->with('');
        $user->expects($this->once())->method('setRoles')->with(['ROLE_ANONYMOUS']);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $this->service->anonymizeUser($user);
    }

    public function testAnonymizeUserException(): void
    {
        $user = $this->createMock(User::class);

        // Mock user ID
        $user->method('getId')->willReturn(123);

        // Mock user setters
        $user->expects($this->once())->method('setDeletedAt');
        $user->expects($this->once())->method('setIsActive')->with(false);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->anonymizeUser($user);
    }

    // ===== Tests pour updateUserRoles() =====

    public function testUpdateUserRolesSuccess(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ];

        $user->expects($this->once())->method('setRoles')->with(['ROLE_ADMIN', 'ROLE_USER']);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateUserRoles($user, $data);

        $this->assertSame($user, $result);
    }

    public function testUpdateUserRolesInvalidRole(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'roles' => ['ROLE_USER', 'ROLE_INVALID'],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rôles non autorisés détectés: ROLE_INVALID');

        $this->service->updateUserRoles($user, $data);
    }

    // Tests pour permissions - trop complexes pour les tests unitaires
    // car ils dépendent des valeurs exactes de l'enum Permission

    // ===== Tests pour getGameHistory() =====

    public function testGetGameHistorySuccess(): void
    {
        $user = $this->createMock(User::class);
        $userAnswer = $this->createMock(\App\Entity\UserAnswer::class);
        $quiz = $this->createMock(\App\Entity\Quiz::class);
        $dateAttempt = new \DateTime('2023-01-15 10:30:00');

        // Mock user answers
        $userAnswers = [$userAnswer];

        $this->userRepository->expects($this->once())
            ->method('findUserGameHistory')
            ->with($user, 50)
            ->willReturn($userAnswers);

        // Mock user answer properties
        $userAnswer->method('getId')->willReturn(123);
        $userAnswer->method('getQuiz')->willReturn($quiz);
        $userAnswer->method('getTotalScore')->willReturn(85);
        $userAnswer->method('getDateAttempt')->willReturn($dateAttempt);

        // Mock quiz properties
        $quiz->method('getId')->willReturn(456);
        $quiz->method('getTitle')->willReturn('Test Quiz');
        $quiz->method('getDescription')->willReturn('A test quiz');

        $result = $this->service->getGameHistory($user);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $historyItem = $result[0];
        $this->assertEquals(123, $historyItem['id']);
        $this->assertEquals(85, $historyItem['score']);
        $this->assertEquals('2023-01-15 10:30:00', $historyItem['date']);
        $this->assertEquals($dateAttempt->getTimestamp(), $historyItem['timestamp']);

        $this->assertArrayHasKey('quiz', $historyItem);
        $this->assertEquals(456, $historyItem['quiz']['id']);
        $this->assertEquals('Test Quiz', $historyItem['quiz']['title']);
        $this->assertEquals('A test quiz', $historyItem['quiz']['description']);
    }

    public function testGetGameHistoryEmpty(): void
    {
        $user = $this->createMock(User::class);

        $this->userRepository->expects($this->once())
            ->method('findUserGameHistory')
            ->with($user, 50)
            ->willReturn([]);

        $result = $this->service->getGameHistory($user);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ===== Tests pour getUsersByCompany() =====

    public function testGetUsersByCompanySuccess(): void
    {
        $company = $this->createMock(Company::class);
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        $company->method('getId')->willReturn(789);

        $this->userRepository->expects($this->once())
            ->method('findByCompanyWithStats')
            ->with(789, false)
            ->willReturn($users);

        $result = $this->service->getUsersByCompany($company);

        $this->assertSame($users, $result);
        $this->assertCount(2, $result);
    }

    // ===== Tests pour listWithStats() (version simplifiée) =====

    public function testListWithStatsAsAdmin(): void
    {
        $currentUser = $this->createMock(User::class);
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        // Mock admin user
        $currentUser->method('isAdmin')->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('findAllWithStats')
            ->with(false, 1, 20, null, 'id')
            ->willReturn($users);

        // Mock user properties for the first user
        $users[0]->method('getId')->willReturn(1);
        $users[0]->method('getEmail')->willReturn('user1@example.com');
        $users[0]->method('getFirstName')->willReturn('User');
        $users[0]->method('getLastName')->willReturn('One');
        $users[0]->method('isActive')->willReturn(true);
        $users[0]->method('getDateRegistration')->willReturn(new \DateTimeImmutable());
        $users[0]->method('getLastAccess')->willReturn(new \DateTime());
        $users[0]->method('getCompany')->willReturn(null);
        $users[0]->method('getGroups')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $users[0]->method('getUserPermissions')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        // Mock user properties for the second user
        $users[1]->method('getId')->willReturn(2);
        $users[1]->method('getEmail')->willReturn('user2@example.com');
        $users[1]->method('getFirstName')->willReturn('User');
        $users[1]->method('getLastName')->willReturn('Two');
        $users[1]->method('isActive')->willReturn(true);
        $users[1]->method('getDateRegistration')->willReturn(new \DateTimeImmutable());
        $users[1]->method('getLastAccess')->willReturn(new \DateTime());
        $users[1]->method('getCompany')->willReturn(null);
        $users[1]->method('getGroups')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $users[1]->method('getUserPermissions')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        $result = $this->service->listWithStats(false, $currentUser);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testListWithStatsAsNonAdminWithCompany(): void
    {
        $currentUser = $this->createMock(User::class);
        $company = $this->createMock(Company::class);
        $users = [$this->createMock(User::class)];

        // Mock non-admin user with company
        $currentUser->method('isAdmin')->willReturn(false);
        $currentUser->method('getCompany')->willReturn($company);
        $company->method('getId')->willReturn(123);

        $this->userRepository->expects($this->once())
            ->method('findByCompanyWithStats')
            ->with(123, false)
            ->willReturn($users);

        // Mock user properties
        $users[0]->method('getId')->willReturn(1);
        $users[0]->method('getEmail')->willReturn('user@example.com');
        $users[0]->method('getFirstName')->willReturn('User');
        $users[0]->method('getLastName')->willReturn('Test');
        $users[0]->method('isActive')->willReturn(true);
        $users[0]->method('getDateRegistration')->willReturn(new \DateTimeImmutable());
        $users[0]->method('getLastAccess')->willReturn(new \DateTime());
        $users[0]->method('getCompany')->willReturn($company);
        $users[0]->method('getGroups')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $users[0]->method('getUserPermissions')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        // Mock company properties
        $company->method('getName')->willReturn('Test Company');

        $result = $this->service->listWithStats(false, $currentUser);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testListWithStatsAsNonAdminWithoutCompany(): void
    {
        $currentUser = $this->createMock(User::class);
        $userWithStats = $this->createMock(User::class);

        // Mock non-admin user without company
        $currentUser->method('isAdmin')->willReturn(false);
        $currentUser->method('getCompany')->willReturn(null);
        $currentUser->method('getId')->willReturn(456);

        $this->userRepository->expects($this->once())
            ->method('findWithStats')
            ->with(456)
            ->willReturn($userWithStats);

        // Mock user properties
        $userWithStats->method('getId')->willReturn(456);
        $userWithStats->method('getEmail')->willReturn('user@example.com');
        $userWithStats->method('getFirstName')->willReturn('User');
        $userWithStats->method('getLastName')->willReturn('Test');
        $userWithStats->method('isActive')->willReturn(true);
        $userWithStats->method('getDateRegistration')->willReturn(new \DateTimeImmutable());
        $userWithStats->method('getLastAccess')->willReturn(new \DateTime());
        $userWithStats->method('getCompany')->willReturn(null);
        $userWithStats->method('getGroups')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $userWithStats->method('getUserPermissions')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        $result = $this->service->listWithStats(false, $currentUser);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    // ===== Tests pour listWithStatsAndPagination() =====

    public function testListWithStatsAndPaginationAsAdmin(): void
    {
        $currentUser = $this->createMock(User::class);
        $users = [
            $this->createMock(User::class),
            $this->createMock(User::class),
        ];

        // Mock admin user
        $currentUser->method('isAdmin')->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('findAllWithStats')
            ->with(false, 2, 10, 'test', 'email')
            ->willReturn($users);

        $this->userRepository->expects($this->once())
            ->method('countAllWithStats')
            ->with(false, 'test')
            ->willReturn(25);

        // Mock user properties for both users
        foreach ($users as $index => $user) {
            $user->method('getId')->willReturn($index + 1);
            $user->method('getEmail')->willReturn("user{$index}@example.com");
            $user->method('getFirstName')->willReturn('User');
            $user->method('getLastName')->willReturn((string) ($index + 1));
            $user->method('isActive')->willReturn(true);
            $user->method('getDateRegistration')->willReturn(new \DateTimeImmutable());
            $user->method('getLastAccess')->willReturn(new \DateTime());
            $user->method('getCompany')->willReturn(null);
            $user->method('getGroups')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
            $user->method('getUserPermissions')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        }

        $result = $this->service->listWithStatsAndPagination(false, $currentUser, 2, 10, 'test', 'email');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);

        $this->assertCount(2, $result['data']);

        $pagination = $result['pagination'];
        $this->assertEquals(2, $pagination['page']);
        $this->assertEquals(10, $pagination['limit']);
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(3, $pagination['totalPages']); // ceil(25/10) = 3
        $this->assertTrue($pagination['hasNext']); // page 2 < 3
        $this->assertTrue($pagination['hasPrev']); // page 2 > 1
    }

    public function testListWithStatsAndPaginationAsNonAdmin(): void
    {
        $currentUser = $this->createMock(User::class);
        $company = $this->createMock(Company::class);
        $users = [$this->createMock(User::class)];

        // Mock non-admin user with company
        $currentUser->method('isAdmin')->willReturn(false);
        $currentUser->method('getCompany')->willReturn($company);
        $company->method('getId')->willReturn(123);

        $this->userRepository->expects($this->once())
            ->method('findByCompanyWithStats')
            ->with(123, false)
            ->willReturn($users);

        // Mock user properties
        $users[0]->method('getId')->willReturn(1);
        $users[0]->method('getEmail')->willReturn('user@example.com');
        $users[0]->method('getFirstName')->willReturn('User');
        $users[0]->method('getLastName')->willReturn('Test');
        $users[0]->method('isActive')->willReturn(true);
        $users[0]->method('getDateRegistration')->willReturn(new \DateTimeImmutable());
        $users[0]->method('getLastAccess')->willReturn(new \DateTime());
        $users[0]->method('getCompany')->willReturn($company);
        $users[0]->method('getGroups')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $users[0]->method('getUserPermissions')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        // Mock company properties
        $company->method('getName')->willReturn('Test Company');

        $result = $this->service->listWithStatsAndPagination(false, $currentUser);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);

        $this->assertCount(1, $result['data']);

        $pagination = $result['pagination'];
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertEquals(1, $pagination['total']);
        $this->assertEquals(1, $pagination['totalPages']);
        $this->assertFalse($pagination['hasNext']);
        $this->assertFalse($pagination['hasPrev']);
    }

    // ===== Tests pour validateAndCleanRoles() via réflection =====

    public function testValidateAndCleanRolesSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateAndCleanRoles');
        $method->setAccessible(true);

        $roles = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_USER']; // With duplicates

        $result = $method->invoke($this->service, $roles);

        $this->assertIsArray($result);
        $this->assertContains('ROLE_USER', $result);
        $this->assertContains('ROLE_ADMIN', $result);
        $this->assertCount(2, $result); // Duplicates removed
    }

    public function testValidateAndCleanRolesWithoutRoleUser(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateAndCleanRoles');
        $method->setAccessible(true);

        $roles = ['ROLE_ADMIN']; // Missing ROLE_USER

        $result = $method->invoke($this->service, $roles);

        $this->assertIsArray($result);
        $this->assertContains('ROLE_USER', $result); // Should be added automatically
        $this->assertContains('ROLE_ADMIN', $result);
        $this->assertCount(2, $result);
    }

    public function testValidateAndCleanRolesInvalidRole(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateAndCleanRoles');
        $method->setAccessible(true);

        $roles = ['ROLE_USER', 'ROLE_INVALID'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rôles non autorisés détectés: ROLE_INVALID');

        $method->invoke($this->service, $roles);
    }

    // ===== Tests pour validateUserDataForCreation() via réflection =====

    public function testValidateUserDataForCreationSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserDataForCreation');
        $method->setAccessible(true);

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Should not throw any exception
        $method->invoke($this->service, $data);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function testValidateUserDataForCreationError(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserDataForCreation');
        $method->setAccessible(true);

        $data = [
            'firstName' => '', // Invalid empty firstName
            'lastName' => 'Doe',
            'email' => 'invalid-email',
            'password' => '123', // Too short
        ];

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $method->invoke($this->service, $data);
    }

    // ===== Tests pour validateUserData() via réflection =====

    public function testValidateUserDataSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserData');
        $method->setAccessible(true);

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Should not throw any exception
        $method->invoke($this->service, $data);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function testValidateUserDataError(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserData');
        $method->setAccessible(true);

        $data = [
            'firstName' => 'A', // Too short
            'email' => 'invalid-email',
        ];

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $method->invoke($this->service, $data);
    }

    // ===== Tests pour getAttemptNumber() via réflection =====

    public function testGetAttemptNumberSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAttemptNumber');
        $method->setAccessible(true);

        $user = $this->createMock(User::class);
        $quiz = $this->createMock(\App\Entity\Quiz::class);
        $userAnswer1 = $this->createMock(\App\Entity\UserAnswer::class);
        $userAnswer2 = $this->createMock(\App\Entity\UserAnswer::class);

        $attemptDate1 = new \DateTime('2023-01-01 10:00:00');
        $attemptDate2 = new \DateTime('2023-01-02 10:00:00');
        $targetDate = new \DateTime('2023-01-02 10:00:00');

        // Mock quiz ID
        $quiz->method('getId')->willReturn(123);

        // Mock user answers
        $userAnswer1->method('getQuiz')->willReturn($quiz);
        $userAnswer1->method('getDateAttempt')->willReturn($attemptDate1);

        $userAnswer2->method('getQuiz')->willReturn($quiz);
        $userAnswer2->method('getDateAttempt')->willReturn($attemptDate2);

        $userAnswers = new \Doctrine\Common\Collections\ArrayCollection([$userAnswer1, $userAnswer2]);
        $user->method('getUserAnswers')->willReturn($userAnswers);

        $result = $method->invoke($this->service, $user, 123, $targetDate);

        $this->assertEquals(2, $result); // Second attempt
    }

    public function testGetAttemptNumberNotFound(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAttemptNumber');
        $method->setAccessible(true);

        $user = $this->createMock(User::class);
        $targetDate = new \DateTime('2023-01-01 10:00:00');

        // Mock empty user answers
        $userAnswers = new \Doctrine\Common\Collections\ArrayCollection([]);
        $user->method('getUserAnswers')->willReturn($userAnswers);

        $result = $method->invoke($this->service, $user, 123, $targetDate);

        $this->assertEquals(1, $result); // Default to 1 if not found
    }

    // ===== Tests pour getUserStatistics() =====
    // Note: Cette méthode est extrêmement complexe avec de nombreuses dépendances
    // et des accès à des propriétés qui peuvent être nulles. Elle nécessite des tests d'intégration.

    // ===== Tests additionnels pour améliorer la couverture =====

    public function testListEmptyResult(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['deletedAt' => null])
            ->willReturn([]);

        $result = $this->service->list(false);

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    public function testGetUsersWithoutCompanyReallyEmpty(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $result = $this->service->getUsersWithoutCompany();
        $this->assertEmpty($result);
    }

    public function testGetUsersFromOtherCompaniesEmpty(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findUsersFromOtherCompanies')
            ->with(456)
            ->willReturn([]);

        $result = $this->service->getUsersFromOtherCompanies(456);
        $this->assertEmpty($result);
    }

    public function testGetActiveUsersForMultiplayerEmpty(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findActiveUsersForMultiplayer')
            ->willReturn([]);

        $result = $this->service->getActiveUsersForMultiplayer();
        $this->assertEmpty($result);
    }

    public function testCreateWithLongFirstName(): void
    {
        $data = [
            'email' => 'test@example.com',
            'firstName' => str_repeat('A', 50), // Long prénom
            'lastName' => 'Doe',
            'password' => 'password123',
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->create($data);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testUpdateWithSpecialCharactersInName(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'firstName' => 'Jean-François',
            'lastName' => "O'Connor",
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $user->expects($this->once())->method('setFirstName')->with('Jean-François');
        $user->expects($this->once())->method('setLastName')->with("O'Connor");

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->update($user, $data);
        $this->assertSame($user, $result);
    }

    public function testAnonymizeUserWithSpecificId(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(999);

        $user->expects($this->once())->method('setDeletedAt');
        $user->expects($this->once())->method('setIsActive')->with(false);
        $user->expects($this->once())->method('setEmail')->with('anon_999@example.com');
        $user->expects($this->once())->method('setFirstName')->with('Utilisateur');
        $user->expects($this->once())->method('setLastName')->with('Anonyme');
        $user->expects($this->once())->method('setPseudo');
        $user->expects($this->once())->method('setPassword')->with('');
        $user->expects($this->once())->method('setRoles')->with(['ROLE_ANONYMOUS']);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $this->service->anonymizeUser($user);
    }
}
