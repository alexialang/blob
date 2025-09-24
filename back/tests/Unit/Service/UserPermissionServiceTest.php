<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Entity\UserPermission;
use App\Enum\Permission;
use App\Repository\UserPermissionRepository;
use App\Service\UserPermissionService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserPermissionServiceTest extends TestCase
{
    private UserPermissionService $service;
    private EntityManagerInterface $em;
    private UserPermissionRepository $userPermissionRepository;
    private UserService $userService;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userPermissionRepository = $this->createMock(UserPermissionRepository::class);
        $this->userService = $this->createMock(UserService::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new UserPermissionService(
            $this->em,
            $this->userPermissionRepository,
            $this->userService,
            $this->validator
        );
    }

    // ===== Tests pour list() =====

    public function testList(): void
    {
        $permissions = [
            $this->createMock(UserPermission::class),
            $this->createMock(UserPermission::class),
        ];

        $this->userPermissionRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($permissions);

        $result = $this->service->list();

        $this->assertSame($permissions, $result);
        $this->assertCount(2, $result);
    }

    public function testListEmpty(): void
    {
        $this->userPermissionRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour find() =====

    public function testFind(): void
    {
        $permission = $this->createMock(UserPermission::class);

        $this->userPermissionRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($permission);

        $result = $this->service->find(123);

        $this->assertSame($permission, $result);
    }

    public function testFindNotFound(): void
    {
        $this->userPermissionRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    // ===== Tests pour create() =====

    public function testCreateSuccess(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'permission' => 'CREATE_QUIZ',
            'user_id' => 1,
        ];

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user service
        $this->userService->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        // Mock entity manager
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(UserPermission::class));

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->create($data);

        $this->assertInstanceOf(UserPermission::class, $result);
    }

    public function testCreateValidationError(): void
    {
        $data = [
            'permission' => '', // Invalid - empty
            'user_id' => 'invalid', // Invalid - not integer
        ];

        // Mock validation with errors
        $violations = new ConstraintViolationList();
        $violations->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);

        $this->service->create($data);
    }

    // ===== Tests pour update() =====

    public function testUpdateSuccess(): void
    {
        $userPermission = $this->createMock(UserPermission::class);
        $user = $this->createMock(User::class);

        $data = [
            'permission' => 'MANAGE_USERS',
            'user_id' => 2,
        ];

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user service
        $this->userService->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($user);

        // Mock userPermission methods
        $userPermission->expects($this->once())
            ->method('setPermission');

        $userPermission->expects($this->once())
            ->method('setUser')
            ->with($user);

        // Mock entity manager
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->update($userPermission, $data);

        $this->assertSame($userPermission, $result);
    }

    public function testUpdateOnlyPermission(): void
    {
        $userPermission = $this->createMock(UserPermission::class);

        $data = [
            'permission' => 'VIEW_RESULTS',
            // Pas de user_id
        ];

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // User service ne doit pas être appelé
        $this->userService->expects($this->never())
            ->method('find');

        // Mock userPermission methods
        $userPermission->expects($this->once())
            ->method('setPermission');

        $userPermission->expects($this->never())
            ->method('setUser');

        // Mock entity manager
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->update($userPermission, $data);

        $this->assertSame($userPermission, $result);
    }

    public function testUpdateOnlyUserId(): void
    {
        $userPermission = $this->createMock(UserPermission::class);
        $user = $this->createMock(User::class);

        $data = [
            'user_id' => 3,
            // Pas de permission
        ];

        // Mock validation
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Mock user service
        $this->userService->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($user);

        // Mock userPermission methods
        $userPermission->expects($this->never())
            ->method('setPermission');

        $userPermission->expects($this->once())
            ->method('setUser')
            ->with($user);

        // Mock entity manager
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->update($userPermission, $data);

        $this->assertSame($userPermission, $result);
    }

    public function testUpdateValidationError(): void
    {
        $userPermission = $this->createMock(UserPermission::class);

        $data = [
            'permission' => '', // Invalid
            'user_id' => 'invalid', // Invalid
        ];

        // Mock validation with errors
        $violations = new ConstraintViolationList();
        $violations->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);

        $this->service->update($userPermission, $data);
    }

    // ===== Tests pour delete() =====

    public function testDelete(): void
    {
        $userPermission = $this->createMock(UserPermission::class);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($userPermission);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->delete($userPermission);
    }

    // ===== Tests pour validateUserPermissionData() via réflection =====

    public function testValidateUserPermissionDataSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserPermissionData');
        $method->setAccessible(true);

        $validData = [
            'permission' => 'CREATE_QUIZ',
            'user_id' => 1,
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Ne doit pas lever d'exception
        $method->invoke($this->service, $validData);
        $this->assertTrue(true); // Si on arrive ici, c'est bon
    }

    public function testValidateUserPermissionDataError(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserPermissionData');
        $method->setAccessible(true);

        $invalidData = [
            'permission' => '', // Invalid
            'user_id' => 'invalid', // Invalid
        ];

        // Mock validation with errors
        $violations = new ConstraintViolationList();
        $violations->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);

        $method->invoke($this->service, $invalidData);
    }
}
