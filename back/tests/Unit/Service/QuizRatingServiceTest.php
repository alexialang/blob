<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Entity\QuizRating;
use App\Entity\User;
use App\Repository\QuizRatingRepository;
use App\Service\QuizRatingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class QuizRatingServiceTest extends TestCase
{
    private QuizRatingService $service;
    private QuizRatingRepository $quizRatingRepository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->quizRatingRepository = $this->createMock(QuizRatingRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new QuizRatingService(
            $this->quizRatingRepository,
            $this->em
        );
    }

    public function testGetAverageRating(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(123);

        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(123)
            ->willReturn(4.5);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(123)
            ->willReturn(10);

        $result = $this->service->getAverageRating($quiz);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('averageRating', $result);
        $this->assertArrayHasKey('ratingCount', $result);
        $this->assertEquals(4.5, $result['averageRating']);
        $this->assertEquals(10, $result['ratingCount']);
    }

    public function testGetAverageRatingWithNullAverage(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(456);

        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(456)
            ->willReturn(null);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(456)
            ->willReturn(0);

        $result = $this->service->getAverageRating($quiz);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['averageRating']); // null devient 0
        $this->assertEquals(0, $result['ratingCount']);
    }

    public function testGetRatingStatistics(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $user = $this->createMock(User::class);
        $userRating = $this->createMock(QuizRating::class);

        $quiz->method('getId')->willReturn(789);
        $user->method('getId')->willReturn(101);

        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(789)
            ->willReturn(3.8);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(789)
            ->willReturn(15);

        $this->quizRatingRepository->expects($this->once())
            ->method('findUserRatingForQuiz')
            ->with(101, 789)
            ->willReturn($userRating);

        $userRating->method('getRating')->willReturn(4);

        $result = $this->service->getRatingStatistics($quiz, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('averageRating', $result);
        $this->assertArrayHasKey('totalRatings', $result);
        $this->assertArrayHasKey('userRating', $result);
        $this->assertEquals(3.8, $result['averageRating']);
        $this->assertEquals(15, $result['totalRatings']);
        $this->assertEquals(4, $result['userRating']);
    }

    public function testGetRatingStatisticsWithoutUser(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(789);

        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(789)
            ->willReturn(3.8);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(789)
            ->willReturn(15);

        $this->quizRatingRepository->expects($this->never())
            ->method('findUserRatingForQuiz');

        $result = $this->service->getRatingStatistics($quiz);

        $this->assertIsArray($result);
        $this->assertEquals(3.8, $result['averageRating']);
        $this->assertEquals(15, $result['totalRatings']);
        $this->assertNull($result['userRating']);
    }

    public function testGetRatingStatisticsWithNoUserRating(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $user = $this->createMock(User::class);

        $quiz->method('getId')->willReturn(789);
        $user->method('getId')->willReturn(101);

        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(789)
            ->willReturn(3.8);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(789)
            ->willReturn(15);

        $this->quizRatingRepository->expects($this->once())
            ->method('findUserRatingForQuiz')
            ->with(101, 789)
            ->willReturn(null);

        $result = $this->service->getRatingStatistics($quiz, $user);

        $this->assertIsArray($result);
        $this->assertEquals(3.8, $result['averageRating']);
        $this->assertEquals(15, $result['totalRatings']);
        $this->assertNull($result['userRating']);
    }

    public function testRateQuizNewRating(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);
        $rating = 5;

        $user->method('getId')->willReturn(202);
        $quiz->method('getId')->willReturn(303);

        $newRating = $this->createMock(QuizRating::class);
        $newRating->method('getRating')->willReturn(5);

        $this->quizRatingRepository->expects($this->exactly(2))
            ->method('findUserRatingForQuiz')
            ->with(202, 303)
            ->willReturnOnConsecutiveCalls(null, $newRating); // Première fois null, deuxième fois le nouveau rating

        $this->em->expects($this->once())
            ->method('beginTransaction');

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(QuizRating::class));

        $this->em->expects($this->once())
            ->method('flush');

        $this->em->expects($this->once())
            ->method('commit');

        // Mock pour getRatingStatistics
        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(303)
            ->willReturn(5.0);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(303)
            ->willReturn(1);

        $result = $this->service->rateQuiz($user, $quiz, $rating);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('averageRating', $result);
        $this->assertArrayHasKey('totalRatings', $result);
        $this->assertArrayHasKey('userRating', $result);
    }

    public function testRateQuizUpdateExisting(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);
        $existingRating = $this->createMock(QuizRating::class);
        $rating = 3;

        $user->method('getId')->willReturn(202);
        $quiz->method('getId')->willReturn(303);

        $this->quizRatingRepository->expects($this->exactly(2))
            ->method('findUserRatingForQuiz')
            ->with(202, 303)
            ->willReturn($existingRating); // Rating existant les deux fois

        $existingRating->expects($this->once())
            ->method('setRating')
            ->with($rating);

        $existingRating->expects($this->once())
            ->method('setRatedAt')
            ->with($this->isInstanceOf(\DateTime::class));

        $this->em->expects($this->once())
            ->method('beginTransaction');

        $this->em->expects($this->never())
            ->method('persist'); // Pas de persist pour un update

        $this->em->expects($this->once())
            ->method('flush');

        $this->em->expects($this->once())
            ->method('commit');

        // Mock pour getRatingStatistics
        $this->quizRatingRepository->expects($this->once())
            ->method('findAverageRatingForQuiz')
            ->with(303)
            ->willReturn(3.5);

        $this->quizRatingRepository->expects($this->once())
            ->method('countRatingsForQuiz')
            ->with(303)
            ->willReturn(2);

        $existingRating->method('getRating')->willReturn(3);

        $result = $this->service->rateQuiz($user, $quiz, $rating);

        $this->assertIsArray($result);
    }

    public function testRateQuizInvalidRatingTooLow(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La note doit être comprise entre 1 et 5');

        $this->service->rateQuiz($user, $quiz, 0);
    }

    public function testRateQuizInvalidRatingTooHigh(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La note doit être comprise entre 1 et 5');

        $this->service->rateQuiz($user, $quiz, 6);
    }

    public function testRateQuizWithException(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);
        $rating = 4;

        $user->method('getId')->willReturn(202);
        $quiz->method('getId')->willReturn(303);

        $this->quizRatingRepository->expects($this->once())
            ->method('findUserRatingForQuiz')
            ->with(202, 303)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('beginTransaction');

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Database error'));

        $this->em->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->rateQuiz($user, $quiz, $rating);
    }
}
