<?php

namespace App\Tests\Unit\Service;

use App\Entity\Company;
use App\Entity\Group;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Service\CompanyService;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GroupServiceTest extends TestCase
{
    private GroupService $service;
    private EntityManagerInterface $em;
    private GroupRepository $groupRepository;
    private CompanyService $companyService;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->groupRepository = $this->createMock(GroupRepository::class);
        $this->companyService = $this->createMock(CompanyService::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new GroupService(
            $this->em,
            $this->groupRepository,
            $this->companyService,
            $this->validator
        );
    }

    // ===== Tests pour list() =====

    public function testList(): void
    {
        $groups = [
            $this->createMock(Group::class),
            $this->createMock(Group::class),
        ];

        $this->groupRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($groups);

        $result = $this->service->list();

        $this->assertSame($groups, $result);
        $this->assertCount(2, $result);
    }

    public function testListEmpty(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour getGroupsByUser() =====

    public function testGetGroupsByUserWithCompany(): void
    {
        $user = $this->createMock(User::class);
        $company = $this->createMock(Company::class);
        $companyRepository = $this->createMock(\App\Repository\CompanyRepository::class);
        $groups = [
            $this->createMock(Group::class),
            $this->createMock(Group::class),
        ];

        $user->method('getCompanyId')->willReturn(123);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Company::class)
            ->willReturn($companyRepository);

        $companyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($company);

        $this->groupRepository->expects($this->once())
            ->method('findBy')
            ->with(['company' => $company])
            ->willReturn($groups);

        $result = $this->service->getGroupsByUser($user);

        $this->assertSame($groups, $result);
        $this->assertCount(2, $result);
    }

    public function testGetGroupsByUserWithoutCompanyId(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getCompanyId')->willReturn(null);

        $result = $this->service->getGroupsByUser($user);

        $this->assertSame([], $result);
    }

    public function testGetGroupsByUserCompanyNotFound(): void
    {
        $user = $this->createMock(User::class);
        $companyRepository = $this->createMock(\App\Repository\CompanyRepository::class);

        $user->method('getCompanyId')->willReturn(123);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Company::class)
            ->willReturn($companyRepository);

        $companyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $result = $this->service->getGroupsByUser($user);

        $this->assertSame([], $result);
    }

    // ===== Tests pour getGroupsByCompany() =====

    public function testGetGroupsByCompany(): void
    {
        $company = $this->createMock(Company::class);
        $groups = [
            $this->createMock(Group::class),
            $this->createMock(Group::class),
        ];

        $company->method('getId')->willReturn(123);

        $this->groupRepository->expects($this->once())
            ->method('findByCompany')
            ->with(123)
            ->willReturn($groups);

        $result = $this->service->getGroupsByCompany($company);

        $this->assertSame($groups, $result);
        $this->assertCount(2, $result);
    }

    // ===== Tests pour find() =====

    public function testFind(): void
    {
        $group = $this->createMock(Group::class);

        $this->groupRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($group);

        $result = $this->service->find(123);

        $this->assertSame($group, $result);
    }

    public function testFindNotFound(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    // ===== Tests pour create() =====

    public function testCreateSuccess(): void
    {
        $data = [
            'name' => 'Test Group',
            'acces_code' => 'ABC123',
            'company_id' => 456,
        ];

        $company = $this->createMock(Company::class);
        $companyRepository = $this->createMock(\App\Repository\CompanyRepository::class);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Company::class)
            ->willReturn($companyRepository);

        $companyRepository->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($company);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->create($data);

        $this->assertInstanceOf(Group::class, $result);
    }

    public function testCreateValidationError(): void
    {
        $data = [
            'name' => '',
            'acces_code' => 'ABC123',
        ];

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $this->service->create($data);
    }

    public function testCreateWithoutCompany(): void
    {
        $data = [
            'name' => 'Test Group',
            'acces_code' => 'ABC123',
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->create($data);

        $this->assertInstanceOf(Group::class, $result);
    }

    // Test pour create() avec member_ids - trop complexe pour les tests unitaires
    // Cette fonctionnalité serait mieux testée avec des tests d'intégration

    // ===== Tests pour createForCompany() =====

    public function testCreateForCompanySuccess(): void
    {
        $data = [
            'name' => 'Test Group',
            'acces_code' => 'ABC123',
        ];

        $company = $this->createMock(Company::class);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createForCompany($data, $company);

        $this->assertInstanceOf(Group::class, $result);
    }

    public function testCreateForCompanyValidationError(): void
    {
        $data = [
            'name' => '',
            'acces_code' => 'ABC123',
        ];

        $company = $this->createMock(Company::class);

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $this->service->createForCompany($data, $company);
    }

    // Test pour createForCompany() avec member_ids - trop complexe pour les tests unitaires
    // Cette fonctionnalité serait mieux testée avec des tests d'intégration

    // ===== Tests pour update() =====

    public function testUpdateSuccess(): void
    {
        $group = $this->createMock(Group::class);
        $data = [
            'name' => 'Updated Group',
            'acces_code' => 'XYZ789',
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $group->expects($this->once())->method('setName')->with('Updated Group');
        $group->expects($this->once())->method('setAccesCode')->with('XYZ789');

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->update($group, $data);

        $this->assertSame($group, $result);
    }

    public function testUpdateValidationError(): void
    {
        $group = $this->createMock(Group::class);
        $data = [
            'name' => '',
            'acces_code' => 'XYZ789',
        ];

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $this->service->update($group, $data);
    }

    public function testUpdateWithCompanyId(): void
    {
        $group = $this->createMock(Group::class);
        $company = $this->createMock(Company::class);
        $data = [
            'name' => 'Updated Group',
            'company_id' => 456,
        ];

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->companyService->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($company);

        $group->expects($this->once())->method('setName')->with('Updated Group');
        $group->expects($this->once())->method('setCompany')->with($company);

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->update($group, $data);

        $this->assertSame($group, $result);
    }

    // ===== Tests pour delete() =====

    public function testDelete(): void
    {
        $group = $this->createMock(Group::class);

        $this->em->expects($this->once())->method('remove')->with($group);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete($group);
    }

    // ===== Tests pour addUserToGroup() =====

    public function testAddUserToGroupSuccess(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);
        $userCompany = $this->createMock(Company::class);
        $groupCompany = $this->createMock(Company::class);

        // Mock des entreprises
        $userCompany->method('getId')->willReturn(123);
        $groupCompany->method('getId')->willReturn(123); // Same company

        $user->method('getCompany')->willReturn($userCompany);
        $group->method('getCompany')->willReturn($groupCompany);
        $group->method('getId')->willReturn(456);
        $user->method('getId')->willReturn(789);

        // Mock repository check
        $this->groupRepository->expects($this->once())
            ->method('isUserInGroup')
            ->with(456, 789)
            ->willReturn(false); // User not in group

        $group->expects($this->once())->method('addUser')->with($user);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->addUserToGroup($group, $user);

        $this->assertTrue($result);
    }

    public function testAddUserToGroupDifferentCompany(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);
        $userCompany = $this->createMock(Company::class);
        $groupCompany = $this->createMock(Company::class);

        // Mock des entreprises différentes
        $userCompany->method('getId')->willReturn(123);
        $groupCompany->method('getId')->willReturn(456); // Different company

        $user->method('getCompany')->willReturn($userCompany);
        $group->method('getCompany')->willReturn($groupCompany);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur doit appartenir à la même entreprise que le groupe');

        $this->service->addUserToGroup($group, $user);
    }

    public function testAddUserToGroupUserWithoutCompany(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);
        $groupCompany = $this->createMock(Company::class);

        $user->method('getCompany')->willReturn(null);
        $group->method('getCompany')->willReturn($groupCompany);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur doit appartenir à la même entreprise que le groupe');

        $this->service->addUserToGroup($group, $user);
    }

    public function testAddUserToGroupAlreadyInGroup(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);
        $userCompany = $this->createMock(Company::class);
        $groupCompany = $this->createMock(Company::class);

        // Mock des entreprises
        $userCompany->method('getId')->willReturn(123);
        $groupCompany->method('getId')->willReturn(123); // Same company

        $user->method('getCompany')->willReturn($userCompany);
        $group->method('getCompany')->willReturn($groupCompany);
        $group->method('getId')->willReturn(456);
        $user->method('getId')->willReturn(789);

        // Mock repository check - user already in group
        $this->groupRepository->expects($this->once())
            ->method('isUserInGroup')
            ->with(456, 789)
            ->willReturn(true); // User already in group

        $group->expects($this->never())->method('addUser');
        $this->em->expects($this->never())->method('flush');

        $result = $this->service->addUserToGroup($group, $user);

        $this->assertFalse($result);
    }

    // ===== Tests pour removeUserFromGroup() =====

    public function testRemoveUserFromGroupSuccess(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);

        $group->method('getId')->willReturn(456);
        $user->method('getId')->willReturn(789);

        // Mock repository check - user is in group
        $this->groupRepository->expects($this->once())
            ->method('isUserInGroup')
            ->with(456, 789)
            ->willReturn(true);

        $group->expects($this->once())->method('removeUser')->with($user);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->removeUserFromGroup($group, $user);

        $this->assertTrue($result);
    }

    public function testRemoveUserFromGroupNotInGroup(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);

        $group->method('getId')->willReturn(456);
        $user->method('getId')->willReturn(789);

        // Mock repository check - user not in group
        $this->groupRepository->expects($this->once())
            ->method('isUserInGroup')
            ->with(456, 789)
            ->willReturn(false);

        $group->expects($this->never())->method('removeUser');
        $this->em->expects($this->never())->method('flush');

        $result = $this->service->removeUserFromGroup($group, $user);

        $this->assertFalse($result);
    }

    // ===== Tests pour validateGroupData() via réflection =====

    public function testValidateGroupDataSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateGroupData');
        $method->setAccessible(true);

        $data = [
            'name' => 'Valid Group',
            'description' => 'A valid group description',
            'acces_code' => 'ABC123',
            'company_id' => 456,
            'member_ids' => [789, 101112],
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

    public function testValidateGroupDataError(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateGroupData');
        $method->setAccessible(true);

        $data = [
            'name' => '', // Invalid empty name
            'acces_code' => 'ABC123',
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
}
