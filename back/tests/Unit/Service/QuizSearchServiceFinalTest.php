<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\QuizRepository;
use App\Service\QuizSearchService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class QuizSearchServiceFinalTest extends TestCase
{
    private QuizSearchService $service;
    private QuizRepository $quizRepository;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->quizRepository = $this->createMock(QuizRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        
        $this->service = new QuizSearchService(
            $this->quizRepository,
            $this->logger,
            $this->serializer
        );
    }

    public function testList(): void
    {
        $quizzes = ['quiz1', 'quiz2'];

        $this->quizRepository->expects($this->once())
            ->method('findPublishedOrAll')
            ->with(false)
            ->willReturn($quizzes);

        $result = $this->service->list();

        $this->assertSame($quizzes, $result);
    }

    public function testListForManagement(): void
    {
        $quizzes = ['quiz1', 'quiz2', 'quiz3'];

        $this->quizRepository->expects($this->once())
            ->method('findPublishedOrAll')
            ->with(true)
            ->willReturn($quizzes);

        $result = $this->service->list(true);

        $this->assertSame($quizzes, $result);
    }

    public function testGetMyQuizzes(): void
    {
        $user = $this->createMock(User::class);
        $quizzes = ['my_quiz1', 'my_quiz2'];

        $this->quizRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($quizzes);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($quizzes, 'json', ['groups' => ['quiz:organized']])
            ->willReturn('["serialized"]');

        $result = $this->service->getMyQuizzes($user);

        $this->assertEquals(['serialized'], $result);
    }

    public function testGetMostPopularQuizzes(): void
    {
        $quizzes = ['popular1', 'popular2'];

        $this->quizRepository->expects($this->once())
            ->method('findMostPopular')
            ->with(8)
            ->willReturn($quizzes);

        $result = $this->service->getMostPopularQuizzes();

        $this->assertEquals($quizzes, $result);
    }

    public function testGetMostPopularQuizzesWithCustomLimit(): void
    {
        $quizzes = ['popular1', 'popular2', 'popular3'];

        $this->quizRepository->expects($this->once())
            ->method('findMostPopular')
            ->with(5)
            ->willReturn($quizzes);

        $result = $this->service->getMostPopularQuizzes(5);

        $this->assertEquals($quizzes, $result);
    }
}
