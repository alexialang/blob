<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\UserAnswer;
use App\Repository\UserAnswerRepository;
use App\Service\QuizCrudService;
use App\Service\QuizRatingService;
use App\Service\UserAnswerService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserAnswerServiceTest extends TestCase
{
    private UserAnswerService $service;
    private EntityManagerInterface $em;
    private UserAnswerRepository $userAnswerRepository;
    private QuizRatingService $quizRatingService;
    private UserService $userService;
    private QuizCrudService $quizCrudService;
    private EventDispatcherInterface $eventDispatcher;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userAnswerRepository = $this->createMock(UserAnswerRepository::class);
        $this->quizRatingService = $this->createMock(QuizRatingService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->quizCrudService = $this->createMock(QuizCrudService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new UserAnswerService(
            $this->em,
            $this->userAnswerRepository,
            $this->quizRatingService,
            $this->userService,
            $this->quizCrudService,
            $this->eventDispatcher,
            $this->validator
        );
    }

    // ===== Tests pour list() =====

    public function testList(): void
    {
        $userAnswers = [
            $this->createMock(UserAnswer::class),
            $this->createMock(UserAnswer::class),
        ];

        $this->userAnswerRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($userAnswers);

        $result = $this->service->list();

        $this->assertSame($userAnswers, $result);
        $this->assertCount(2, $result);
    }

    public function testListEmpty(): void
    {
        $this->userAnswerRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour show() =====

    public function testShow(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);

        $result = $this->service->show($userAnswer);

        $this->assertSame($userAnswer, $result);
    }

    // ===== Tests pour create() =====

    public function testCreateSuccess(): void
    {
        $data = [
            'total_score' => 85,
            'user_id' => 123,
            'quiz_id' => 456,
        ];

        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock user and quiz lookup
        $this->userService->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($user);

        $this->quizCrudService->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($quiz);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->create($data);

        $this->assertInstanceOf(UserAnswer::class, $result);
    }

    public function testCreateValidationError(): void
    {
        $data = [
            'total_score' => -10, // Invalid score
            'user_id' => 123,
            'quiz_id' => 456,
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

    // ===== Tests pour update() =====

    public function testUpdateSuccess(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        $data = [
            'total_score' => 90,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $userAnswer->expects($this->once())->method('setTotalScore')->with(90);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->update($userAnswer, $data);

        $this->assertSame($userAnswer, $result);
    }

    public function testUpdateValidationError(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        $data = [
            'total_score' => 'invalid', // Invalid type
        ];

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $this->service->update($userAnswer, $data);
    }

    // ===== Tests pour delete() =====

    public function testDelete(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);

        $this->em->expects($this->once())->method('remove')->with($userAnswer);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete($userAnswer);
    }

    // ===== Tests pour saveGameResult() =====

    public function testSaveGameResultSuccess(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);

        $data = [
            'user' => $user, // Pass user object directly
            'quiz_id' => 456,
            'total_score' => 75,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock quiz lookup
        $this->quizCrudService->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($quiz);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        $result = $this->service->saveGameResult($data);

        $this->assertInstanceOf(UserAnswer::class, $result);
    }

    // ===== Tests pour rateQuiz() =====

    public function testRateQuizSuccess(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);

        $data = [
            'user' => $user, // Pass user object directly
            'quizId' => 456, // Note: quizId not quiz_id
            'rating' => 4,
        ];

        // Mock quiz lookup
        $this->quizCrudService->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($quiz);

        $this->quizRatingService->expects($this->once())
            ->method('rateQuiz')
            ->with($user, $quiz, 4)
            ->willReturn(['averageRating' => 4.2, 'totalRatings' => 10]);

        $result = $this->service->rateQuiz($data);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(4.2, $result['averageRating']);
        $this->assertEquals(10, $result['totalRatings']);
    }

    // ===== Tests pour getQuizRating() =====

    public function testGetQuizRating(): void
    {
        $quizId = 456;
        $currentUser = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);

        // Mock quiz lookup
        $this->quizCrudService->expects($this->once())
            ->method('find')
            ->with($quizId)
            ->willReturn($quiz);

        $this->quizRatingService->expects($this->once())
            ->method('getRatingStatistics')
            ->with($quiz, $currentUser)
            ->willReturn(['average' => 4.2, 'count' => 10]);

        $result = $this->service->getQuizRating($quizId, $currentUser);

        $this->assertIsArray($result);
        $this->assertEquals(4.2, $result['average']);
        $this->assertEquals(10, $result['count']);
    }

    // ===== Tests pour validateUserAnswerData() via réflection =====

    public function testValidateUserAnswerDataSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserAnswerData');
        $method->setAccessible(true);

        $data = [
            'total_score' => 85,
            'user_id' => 123,
            'quiz_id' => 456,
            'question_id' => 789,
            'answer' => 'Test answer',
            'score' => 10,
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

    public function testValidateUserAnswerDataError(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUserAnswerData');
        $method->setAccessible(true);

        $data = [
            'total_score' => 'invalid', // Invalid type
            'user_id' => null, // Missing required field
            'quiz_id' => 'invalid', // Invalid type
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

    // ===== Tests pour validateGameResultData() via réflection =====

    public function testValidateGameResultDataSuccess(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateGameResultData');
        $method->setAccessible(true);

        $data = [
            'total_score' => 75,
            'extra_field' => 'allowed', // Extra fields are allowed
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

    public function testValidateGameResultDataError(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateGameResultData');
        $method->setAccessible(true);

        $data = [
            'total_score' => null, // Missing required field
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

    // ===== Tests pour cas d'erreur dans saveGameResult() =====

    public function testSaveGameResultMissingData(): void
    {
        $data = [
            'quiz_id' => 456,
            'total_score' => 75,
            // Missing 'user' field
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required data: user, quiz_id, total_score');

        $this->service->saveGameResult($data);
    }

    public function testSaveGameResultQuizNotFound(): void
    {
        $user = $this->createMock(User::class);

        $data = [
            'user' => $user,
            'quiz_id' => 999, // Non-existent quiz
            'total_score' => 75,
        ];

        // Mock validation
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Mock quiz lookup - returns null
        $this->quizCrudService->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quiz not found');

        $this->service->saveGameResult($data);
    }

    // ===== Tests pour cas d'erreur dans rateQuiz() =====

    public function testRateQuizMissingData(): void
    {
        $data = [
            'quizId' => 456,
            'rating' => 4,
            // Missing 'user' field
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required data: user, quizId, rating');

        $this->service->rateQuiz($data);
    }

    public function testRateQuizQuizNotFound(): void
    {
        $user = $this->createMock(User::class);

        $data = [
            'user' => $user,
            'quizId' => 999, // Non-existent quiz
            'rating' => 4,
        ];

        // Mock quiz lookup - returns null
        $this->quizCrudService->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quiz not found');

        $this->service->rateQuiz($data);
    }
}
