<?php

namespace App\Tests\Unit\Service;

use App\Entity\Company;
use App\Entity\Group;
use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Enum\Permission;
use App\Repository\CompanyRepository;
use App\Service\CompanyService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyServiceTest extends TestCase
{
    private CompanyService $service;
    private EntityManagerInterface $em;
    private CompanyRepository $companyRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->companyRepository = $this->createMock(CompanyRepository::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new CompanyService(
            $this->em,
            $this->companyRepository,
            $this->serializer,
            $this->validator
        );
    }

    // ===== Tests pour list() =====

    public function testList(): void
    {
        $companies = [
            $this->createMock(Company::class),
            $this->createMock(Company::class),
        ];

        $this->companyRepository->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn($companies);

        $result = $this->service->list();

        $this->assertSame($companies, $result);
        $this->assertCount(2, $result);
    }

    public function testListEmpty(): void
    {
        $this->companyRepository->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour findByUser() =====

    public function testFindByUserWithCompany(): void
    {
        $user = $this->createMock(User::class);
        $company = $this->createMock(Company::class);

        $user->expects($this->exactly(2))
            ->method('getCompany')
            ->willReturn($company);

        $result = $this->service->findByUser($user);

        $this->assertCount(1, $result);
        $this->assertSame($company, $result[0]);
    }

    public function testFindByUserWithoutCompany(): void
    {
        $user = $this->createMock(User::class);

        $user->expects($this->once())
            ->method('getCompany')
            ->willReturn(null);

        $result = $this->service->findByUser($user);

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour find() =====

    public function testFind(): void
    {
        $company = $this->createMock(Company::class);

        $this->companyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($company);

        $result = $this->service->find(123);

        $this->assertSame($company, $result);
    }

    public function testFindNotFound(): void
    {
        $this->companyRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    // ===== Tests pour create() =====

    public function testCreateSuccess(): void
    {
        $data = ['name' => 'Test Company'];

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Company::class));
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->create($data);

        $this->assertInstanceOf(Company::class, $result);
    }

    public function testCreateException(): void
    {
        $data = ['name' => 'Test Company'];

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Company::class));
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->create($data);
    }

    // ===== Tests pour update() =====

    public function testUpdateSuccess(): void
    {
        $company = $this->createMock(Company::class);
        $data = ['name' => 'Updated Company'];

        $company->expects($this->once())
            ->method('setName')
            ->with('Updated Company');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->update($company, $data);

        $this->assertSame($company, $result);
    }

    public function testUpdateWithoutName(): void
    {
        $company = $this->createMock(Company::class);
        $data = [];

        $company->expects($this->never())->method('setName');

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->update($company, $data);

        $this->assertSame($company, $result);
    }

    public function testUpdateException(): void
    {
        $company = $this->createMock(Company::class);
        $data = ['name' => 'Updated Company'];

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Update error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Update error');

        $this->service->update($company, $data);
    }

    // ===== Tests pour delete() =====

    public function testDeleteSuccess(): void
    {
        $company = $this->createMock(Company::class);
        $user = $this->createMock(User::class);
        $group = $this->createMock(Group::class);
        $quiz = $this->createMock(Quiz::class);

        $users = new ArrayCollection([$user]);
        $groups = new ArrayCollection([$group]);
        $quizs = new ArrayCollection([$quiz]);

        $company->method('getUsers')->willReturn($users);
        $company->method('getGroups')->willReturn($groups);
        $company->method('getQuizs')->willReturn($quizs);

        $user->expects($this->once())->method('setCompany')->with(null);
        $group->expects($this->once())->method('setCompany')->with(null);
        $quiz->expects($this->once())->method('setCompany')->with(null);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('remove')->with($company);
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $this->service->delete($company);
    }

    public function testDeleteException(): void
    {
        $company = $this->createMock(Company::class);

        $company->method('getUsers')->willReturn(new ArrayCollection([]));
        $company->method('getGroups')->willReturn(new ArrayCollection([]));
        $company->method('getQuizs')->willReturn(new ArrayCollection([]));

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('remove')->with($company);
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Delete error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delete error');

        $this->service->delete($company);
    }

    // ===== Tests pour exportCompaniesToCsv() =====

    public function testExportCompaniesToCsv(): void
    {
        $companies = [
            $this->createMock(Company::class),
            $this->createMock(Company::class),
        ];

        $companies[0]->method('getId')->willReturn(1);
        $companies[0]->method('getName')->willReturn('Company 1');
        $companies[0]->method('getDateCreation')->willReturn(new \DateTime('2023-01-01'));
        $companies[0]->method('getUsers')->willReturn(new ArrayCollection([]));
        $companies[0]->method('getGroups')->willReturn(new ArrayCollection([]));
        $companies[0]->method('getQuizs')->willReturn(new ArrayCollection([]));

        $companies[1]->method('getId')->willReturn(2);
        $companies[1]->method('getName')->willReturn('Company 2');
        $companies[1]->method('getDateCreation')->willReturn(new \DateTime('2023-01-02'));
        $companies[1]->method('getUsers')->willReturn(new ArrayCollection([]));
        $companies[1]->method('getGroups')->willReturn(new ArrayCollection([]));
        $companies[1]->method('getQuizs')->willReturn(new ArrayCollection([]));

        $this->companyRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($companies);

        $result = $this->service->exportCompaniesToCsv();

        $this->assertIsString($result);
        $this->assertStringContainsString('ID,Nom,Nombre d\'utilisateurs,Nombre de groupes,Nombre de quiz,Date de création', $result);
        $this->assertStringContainsString('Company 1', $result);
        $this->assertStringContainsString('Company 2', $result);
    }

    public function testExportCompaniesToCsvEmpty(): void
    {
        $this->companyRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->exportCompaniesToCsv();

        $this->assertIsString($result);
        $this->assertStringContainsString('ID,Nom,Nombre d\'utilisateurs,Nombre de groupes,Nombre de quiz,Date de création', $result);
    }

    // ===== Tests pour exportCompaniesToJson() =====

    public function testExportCompaniesToJson(): void
    {
        $companies = [
            $this->createMock(Company::class),
            $this->createMock(Company::class),
        ];

        $this->companyRepository->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn($companies);

        $jsonResult = '[
    {
        "id": 1,
        "name": "Company 1"
    }
]';

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($companies, 'json')
            ->willReturn($jsonResult);

        $result = $this->service->exportCompaniesToJson();

        $this->assertSame($jsonResult, $result);
    }

    // ===== Tests pour getCompanyGroups() (version complexe) =====

    public function testGetCompanyGroupsComplex(): void
    {
        $company = $this->createMock(Company::class);
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);

        $company->method('getId')->willReturn(123);

        // Mock group data
        $group->method('getId')->willReturn(456);
        $group->method('getName')->willReturn('Test Group');
        $group->method('getAccesCode')->willReturn('ABC123');
        $group->method('getUsers')->willReturn(new ArrayCollection([$user]));

        // Mock user data
        $user->method('getId')->willReturn(789);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');
        $user->method('getPseudo')->willReturn('johndoe');
        $user->method('getAvatar')->willReturn('avatar.jpg');

        $this->companyRepository->expects($this->once())
            ->method('findGroupsWithUsersByCompany')
            ->with(123)
            ->willReturn([$group]);

        $result = $this->service->getCompanyGroups($company);

        $this->assertCount(1, $result);
        $this->assertSame(456, $result[0]['id']);
        $this->assertSame('Test Group', $result[0]['name']);
        $this->assertSame('ABC123', $result[0]['accesCode']);
        $this->assertSame(1, $result[0]['userCount']);
        $this->assertCount(1, $result[0]['users']);
        $this->assertSame(789, $result[0]['users'][0]['id']);
        $this->assertSame('user@example.com', $result[0]['users'][0]['email']);
        $this->assertSame('John', $result[0]['users'][0]['firstName']);
        $this->assertSame('Doe', $result[0]['users'][0]['lastName']);
        $this->assertSame('johndoe', $result[0]['users'][0]['pseudo']);
        $this->assertSame('avatar.jpg', $result[0]['users'][0]['avatar']);
    }

    public function testGetCompanyGroupsComplexEmpty(): void
    {
        $company = $this->createMock(Company::class);

        $company->method('getId')->willReturn(123);

        $this->companyRepository->expects($this->once())
            ->method('findGroupsWithUsersByCompany')
            ->with(123)
            ->willReturn([]);

        $result = $this->service->getCompanyGroups($company);

        $this->assertCount(0, $result);
        $this->assertSame([], $result);
    }

    // ===== Tests pour importCompaniesFromCsv() =====

    public function testImportCompaniesFromCsvSuccess(): void
    {
        // Créer un fichier temporaire réel pour le test
        $tempFile = tempnam(sys_get_temp_dir(), 'test_companies');
        $csvContent = "Nom\nCompany Test 1\nCompany Test 2\n";
        file_put_contents($tempFile, $csvContent);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn($tempFile);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->importCompaniesFromCsv($file);

        $this->assertSame(2, $result['success']);
        $this->assertEmpty($result['errors']);

        // Nettoyer le fichier temporaire
        unlink($tempFile);
    }

    public function testImportCompaniesFromCsvWithEmptyLines(): void
    {
        // Créer un fichier temporaire avec des lignes vides
        $tempFile = tempnam(sys_get_temp_dir(), 'test_companies');
        $csvContent = "Nom\n\nCompany Valid\n\n";
        file_put_contents($tempFile, $csvContent);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn($tempFile);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->importCompaniesFromCsv($file);

        $this->assertSame(1, $result['success']);
        $this->assertEmpty($result['errors']);

        // Nettoyer le fichier temporaire
        unlink($tempFile);
    }

    public function testImportCompaniesFromCsvWithInvalidData(): void
    {
        // Créer un fichier temporaire avec des données invalides
        $tempFile = tempnam(sys_get_temp_dir(), 'test_companies');
        $csvContent = "Nom\n,\nCompany Valid\n";
        file_put_contents($tempFile, $csvContent);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn($tempFile);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->importCompaniesFromCsv($file);

        $this->assertSame(1, $result['success']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Format invalide', $result['errors'][0]);

        // Nettoyer le fichier temporaire
        unlink($tempFile);
    }

    public function testImportCompaniesFromCsvWithValidationErrors(): void
    {
        // Créer un fichier temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'test_companies');
        $csvContent = "Nom\nInvalid Company\nValid Company\n";
        file_put_contents($tempFile, $csvContent);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn($tempFile);

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violation->method('getMessage')->willReturn('Name is invalid');

        $violationsWithError = $this->createMock(ConstraintViolationListInterface::class);
        $violationsWithError->method('count')->willReturn(1);
        $violationsWithError->method('offsetGet')->with(0)->willReturn($violation);

        $violationsValid = $this->createMock(ConstraintViolationListInterface::class);
        $violationsValid->method('count')->willReturn(0);

        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturnOnConsecutiveCalls($violationsWithError, $violationsValid);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('persist'); // Seulement pour la company valide
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->importCompaniesFromCsv($file);

        $this->assertSame(1, $result['success']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Name is invalid', $result['errors'][0]);

        // Nettoyer le fichier temporaire
        unlink($tempFile);
    }

    public function testImportCompaniesFromCsvException(): void
    {
        // Créer un fichier temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'test_companies');
        $csvContent = "Nom\nCompany 1\n";
        file_put_contents($tempFile, $csvContent);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn($tempFile);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        try {
            $this->service->importCompaniesFromCsv($file);
        } finally {
            // Nettoyer le fichier temporaire même en cas d'exception
            unlink($tempFile);
        }
    }

    public function testImportCompaniesFromCsvNoSuccess(): void
    {
        // Créer un fichier temporaire avec seulement des erreurs
        $tempFile = tempnam(sys_get_temp_dir(), 'test_companies');
        $csvContent = "Nom\n,\n\n";
        file_put_contents($tempFile, $csvContent);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn($tempFile);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->never())->method('persist'); // Aucune company valide
        $this->em->expects($this->never())->method('flush'); // Pas de flush car success = 0
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->importCompaniesFromCsv($file);

        $this->assertSame(0, $result['success']);
        $this->assertCount(1, $result['errors']); // Une erreur pour la ligne invalide

        // Nettoyer le fichier temporaire
        unlink($tempFile);
    }

    // ===== Tests pour getCompanyStats() =====

    public function testGetCompanyStats(): void
    {
        $company = $this->createMock(Company::class);
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $group = $this->createMock(Group::class);
        $quiz = $this->createMock(Quiz::class);

        $recentDate = new \DateTime('-10 days');
        $oldDate = new \DateTime('-40 days');
        $creationDate = new \DateTime('2023-01-01');

        // Mock company data
        $company->method('getId')->willReturn(123);
        $company->method('getName')->willReturn('Test Company');
        $company->method('getDateCreation')->willReturn($creationDate);

        $users = new ArrayCollection([$user1, $user2]);
        $groups = new ArrayCollection([$group]);
        $quizs = new ArrayCollection([$quiz]);

        $company->method('getUsers')->willReturn($users);
        $company->method('getGroups')->willReturn($groups);
        $company->method('getQuizs')->willReturn($quizs);

        // Mock users
        $user1->method('getLastAccess')->willReturn($recentDate);
        $user1->method('isActive')->willReturn(true);

        $user2->method('getLastAccess')->willReturn($oldDate);
        $user2->method('isActive')->willReturn(false);

        $result = $this->service->getCompanyStats($company);

        $this->assertSame(123, $result['id']);
        $this->assertSame('Test Company', $result['name']);
        $this->assertSame(2, $result['userCount']);
        $this->assertSame(1, $result['activeUsers']);
        $this->assertSame(1, $result['groupCount']);
        $this->assertSame(1, $result['quizCount']);
        $this->assertSame('2023-01-01 00:00:00', $result['createdAt']);
        $this->assertSame($recentDate->format('Y-m-d H:i:s'), $result['lastActivity']);
    }

    public function testGetCompanyStatsNoUsers(): void
    {
        $company = $this->createMock(Company::class);

        $company->method('getId')->willReturn(123);
        $company->method('getName')->willReturn('Empty Company');
        $company->method('getDateCreation')->willReturn(null);
        $company->method('getUsers')->willReturn(new ArrayCollection([]));
        $company->method('getGroups')->willReturn(new ArrayCollection([]));
        $company->method('getQuizs')->willReturn(new ArrayCollection([]));

        $result = $this->service->getCompanyStats($company);

        $this->assertSame(123, $result['id']);
        $this->assertSame('Empty Company', $result['name']);
        $this->assertSame(0, $result['userCount']);
        $this->assertSame(0, $result['activeUsers']);
        $this->assertSame(0, $result['groupCount']);
        $this->assertSame(0, $result['quizCount']);
        $this->assertNull($result['createdAt']);
        $this->assertNull($result['lastActivity']);
    }

    // ===== Tests pour assignUserToCompany() =====

    public function testAssignUserToCompanySuccess(): void
    {
        $company = $this->createMock(Company::class);
        $user = $this->createMock(User::class);
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);
        $permissionRepository = $this->createMock(\App\Repository\UserPermissionRepository::class);

        $company->method('getId')->willReturn(123);
        $company->method('getName')->willReturn('Test Company');

        $user->method('getId')->willReturn(456);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');
        $user->method('getCompany')->willReturn(null);

        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [UserPermission::class, $permissionRepository],
            ]);

        $userRepository->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($user);

        $permissionRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([]);

        $user->expects($this->once())->method('setCompany')->with($company);
        $user->expects($this->once())->method('setRoles')->with(['ROLE_USER']);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->assignUserToCompany($company, 456, ['ROLE_USER'], ['CREATE_QUIZ']);

        $this->assertSame(456, $result['id']);
        $this->assertSame('test@example.com', $result['email']);
        $this->assertSame('John', $result['firstName']);
        $this->assertSame('Doe', $result['lastName']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
        $this->assertSame(123, $result['companyId']);
        $this->assertSame('Test Company', $result['companyName']);
    }

    public function testAssignUserToCompanyUserNotFound(): void
    {
        $company = $this->createMock(Company::class);
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $userRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Utilisateur non trouvé');

        $this->service->assignUserToCompany($company, 999, ['ROLE_USER'], []);
    }

    public function testAssignUserToCompanyAlreadyInCompany(): void
    {
        $company = $this->createMock(Company::class);
        $user = $this->createMock(User::class);
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);

        $company->method('getId')->willReturn(123);
        $user->method('getCompany')->willReturn($company);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $userRepository->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur est déjà dans cette entreprise');

        $this->service->assignUserToCompany($company, 456, ['ROLE_USER'], []);
    }

    public function testAssignUserToCompanyWithInvalidPermission(): void
    {
        $company = $this->createMock(Company::class);
        $user = $this->createMock(User::class);
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);
        $permissionRepository = $this->createMock(\App\Repository\UserPermissionRepository::class);

        $company->method('getId')->willReturn(123);
        $company->method('getName')->willReturn('Test Company');

        $user->method('getId')->willReturn(456);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');
        $user->method('getCompany')->willReturn(null);

        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [UserPermission::class, $permissionRepository],
            ]);

        $userRepository->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($user);

        $permissionRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([]);

        $user->expects($this->once())->method('setCompany')->with($company);
        $user->expects($this->once())->method('setRoles')->with(['ROLE_USER']);

        // Aucun persist ne devrait être appelé pour les permissions invalides
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Test avec une permission invalide qui déclenchera ValueError
        $result = $this->service->assignUserToCompany($company, 456, ['ROLE_USER'], ['INVALID_PERMISSION']);

        $this->assertSame(456, $result['id']);
        $this->assertSame('test@example.com', $result['email']);
        $this->assertSame('John', $result['firstName']);
        $this->assertSame('Doe', $result['lastName']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
        $this->assertSame(123, $result['companyId']);
        $this->assertSame('Test Company', $result['companyName']);
    }
}
