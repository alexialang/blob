<?php

namespace App\Tests\Unit\Service;

use App\Service\MultiplayerValidationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MultiplayerValidationServiceTest extends TestCase
{
    private MultiplayerValidationService $service;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->service = new MultiplayerValidationService($this->validator);
    }

    // ===== Tests pour validateRoomData() =====
    
    public function testValidateRoomDataSuccess(): void
    {
        $data = [
            'quizId' => 1,
            'maxPlayers' => 5,
            'isTeamMode' => true,
            'roomName' => 'Test Room'
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateRoomData($data);
        
        // Si aucune exception n'est levée, le test passe
        $this->assertTrue(true);
    }

    public function testValidateRoomDataWithValidationErrors(): void
    {
        $data = [
            'quizId' => 'invalid',
            'maxPlayers' => 15,
        ];

        $violation = new ConstraintViolation(
            'L\'ID du quiz doit être un entier',
            null,
            [],
            $data,
            'quizId',
            'invalid'
        );
        
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);
        $this->service->validateRoomData($data);
    }

    public function testValidateRoomDataMinimal(): void
    {
        $data = [
            'quizId' => 1,
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateRoomData($data);
        
        $this->assertTrue(true);
    }

    // ===== Tests pour validateAnswerData() =====
    
    public function testValidateAnswerDataSuccess(): void
    {
        $data = [
            'questionId' => 1,
            'answerId' => 2,
            'timeSpent' => 15.5
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateAnswerData($data);
        
        $this->assertTrue(true);
    }

    public function testValidateAnswerDataWithValidationErrors(): void
    {
        $data = [
            'questionId' => 'invalid',
            'answerId' => null,
        ];

        $violation = new ConstraintViolation(
            'L\'ID de la question doit être un entier',
            null,
            [],
            $data,
            'questionId',
            'invalid'
        );
        
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);
        $this->service->validateAnswerData($data);
    }

    public function testValidateAnswerDataMinimal(): void
    {
        $data = [
            'questionId' => 1,
            'answerId' => 2,
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateAnswerData($data);
        
        $this->assertTrue(true);
    }

    // ===== Tests pour validateJoinRoomData() =====
    
    public function testValidateJoinRoomDataSuccess(): void
    {
        $data = [
            'roomCode' => 'ABC123',
            'playerName' => 'TestPlayer'
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateJoinRoomData($data);
        
        $this->assertTrue(true);
    }

    public function testValidateJoinRoomDataWithValidationErrors(): void
    {
        $data = [
            'roomCode' => '',
            'playerName' => str_repeat('a', 256), // Trop long
        ];

        $violation = new ConstraintViolation(
            'Le code de la salle est requis',
            null,
            [],
            $data,
            'roomCode',
            ''
        );
        
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);
        $this->service->validateJoinRoomData($data);
    }

    public function testValidateJoinRoomDataMinimal(): void
    {
        $data = [
            'roomCode' => 'ABC123',
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateJoinRoomData($data);
        
        $this->assertTrue(true);
    }

    // ===== Tests pour validateInvitationData() =====
    
    public function testValidateInvitationDataSuccess(): void
    {
        $data = [
            'roomId' => 1,
            'inviteeEmail' => 'test@example.com'
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateInvitationData($data);
        
        $this->assertTrue(true);
    }

    public function testValidateInvitationDataWithValidationErrors(): void
    {
        $data = [
            'roomId' => 'invalid',
            'inviteeEmail' => 'invalid-email',
        ];

        $violation = new ConstraintViolation(
            'L\'ID de la salle doit être un entier',
            null,
            [],
            $data,
            'roomId',
            'invalid'
        );
        
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidationFailedException::class);
        $this->service->validateInvitationData($data);
    }

    public function testValidateInvitationDataMinimal(): void
    {
        $data = [
            'roomId' => 1,
            'inviteeEmail' => 'test@example.com'
        ];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service->validateInvitationData($data);
        
        $this->assertTrue(true);
    }
}

