<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\QuizRepository;
use App\Service\QuizSearchService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class QuizSearchServiceTest extends TestCase
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

    // ===== Tests pour list() =====
    
    public function testListForManagement(): void
    {
        $quizzes = [
            $this->createMock(Quiz::class),
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findPublishedOrAll')
            ->with(true)
            ->willReturn($quizzes);

        $result = $this->service->list(true);

        $this->assertSame($quizzes, $result);
        $this->assertCount(2, $result);
    }

    public function testListPublishedOnly(): void
    {
        $quizzes = [
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findPublishedOrAll')
            ->with(false)
            ->willReturn($quizzes);

        $result = $this->service->list(false);

        $this->assertSame($quizzes, $result);
        $this->assertCount(1, $result);
    }

    public function testListDefault(): void
    {
        $quizzes = [];

        $this->quizRepository->expects($this->once())
            ->method('findPublishedOrAll')
            ->with(false) // Default value
            ->willReturn($quizzes);

        $result = $this->service->list();

        $this->assertSame($quizzes, $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests complets pour getQuizzesForCompanyManagement() =====
    
    public function testGetQuizzesForCompanyManagementSuccess(): void
    {
        $user = $this->createMock(User::class);
        $quizzes = [
            $this->createMock(Quiz::class),
            $this->createMock(Quiz::class)
        ];

        $repositoryResult = [
            'data' => $quizzes,
            'pagination' => [
                'total' => 2,
                'page' => 1,
                'limit' => 20,
                'totalPages' => 1
            ]
        ];

        $this->quizRepository->expects($this->once())
            ->method('findWithPagination')
            ->with(1, 20, null, 'id', $user)
            ->willReturn($repositoryResult);

        // Mock serializer
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($quizzes, 'json', ['groups' => ['quiz:organized']])
            ->willReturn('[{"id":1},{"id":2}]');

        $result = $this->service->getQuizzesForCompanyManagement($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testGetQuizzesForCompanyManagementWithParameters(): void
    {
        $user = $this->createMock(User::class);
        $quizzes = [
            $this->createMock(Quiz::class)
        ];

        $repositoryResult = [
            'data' => $quizzes,
            'pagination' => [
                'total' => 1,
                'page' => 2,
                'limit' => 10,
                'totalPages' => 1
            ]
        ];

        $this->quizRepository->expects($this->once())
            ->method('findWithPagination')
            ->with(2, 10, 'test search', 'title', $user)
            ->willReturn($repositoryResult);

        // Mock serializer
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($quizzes, 'json', ['groups' => ['quiz:organized']])
            ->willReturn('[{"id":1}]');

        $result = $this->service->getQuizzesForCompanyManagement($user, 2, 10, 'test search', 'title');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['data']);
    }

    public function testGetQuizzesForCompanyManagementException(): void
    {
        $user = $this->createMock(User::class);

        $this->quizRepository->expects($this->once())
            ->method('findWithPagination')
            ->with(1, 20, null, 'id', $user)
            ->willThrowException(new \Exception('Database error'));

        // Mock logger
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur dans getQuizzesForCompanyManagement'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->getQuizzesForCompanyManagement($user);
    }

    // ===== Tests pour getPrivateQuizzesForUser() =====
    
    public function testGetPrivateQuizzesForUser(): void
    {
        $user = $this->createMock(User::class);
        $quizzes = [
            $this->createMock(Quiz::class),
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findPrivateQuizzesForUser')
            ->with($user)
            ->willReturn($quizzes);

        // Mock serializer
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($quizzes, 'json', ['groups' => ['quiz:organized']])
            ->willReturn('[]');

        $result = $this->service->getPrivateQuizzesForUser($user);

        $this->assertIsArray($result);
    }

    public function testGetPrivateQuizzesForUserEmpty(): void
    {
        $user = $this->createMock(User::class);

        $this->quizRepository->expects($this->once())
            ->method('findPrivateQuizzesForUser')
            ->with($user)
            ->willReturn([]);

        // Mock serializer
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with([], 'json', ['groups' => ['quiz:organized']])
            ->willReturn('[]');

        $result = $this->service->getPrivateQuizzesForUser($user);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    // Tests getMyQuizzes() complets
    
    public function testGetMyQuizzes(): void
    {
        $user = $this->createMock(User::class);
        $quizzes = [
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($quizzes);

        // Mock serializer
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($quizzes, 'json', ['groups' => ['quiz:organized']])
            ->willReturn('[{"id":1}]');

        $result = $this->service->getMyQuizzes($user);

        $this->assertIsArray($result);
    }

    public function testGetMyQuizzesEmpty(): void
    {
        $user = $this->createMock(User::class);

        $this->quizRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn([]);

        // Mock serializer
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with([], 'json', ['groups' => ['quiz:organized']])
            ->willReturn('[]');

        $result = $this->service->getMyQuizzes($user);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour getMostPopularQuizzes() =====
    
    public function testGetMostPopularQuizzes(): void
    {
        $quizzes = [
            $this->createMock(Quiz::class),
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findMostPopular')
            ->with(8) // Default limit
            ->willReturn($quizzes);

        $result = $this->service->getMostPopularQuizzes();

        $this->assertSame($quizzes, $result);
        $this->assertCount(2, $result);
    }

    public function testGetMostPopularQuizzesCustomLimit(): void
    {
        $quizzes = [
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findMostPopular')
            ->with(5)
            ->willReturn($quizzes);

        $result = $this->service->getMostPopularQuizzes(5);

        $this->assertSame($quizzes, $result);
        $this->assertCount(1, $result);
    }

    // ===== Tests pour getMostRecentQuizzes() =====
    
    public function testGetMostRecentQuizzes(): void
    {
        $quizzes = [
            $this->createMock(Quiz::class),
            $this->createMock(Quiz::class),
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findMostRecent')
            ->with(6) // Default limit
            ->willReturn($quizzes);

        $result = $this->service->getMostRecentQuizzes();

        $this->assertSame($quizzes, $result);
        $this->assertCount(3, $result);
    }

    public function testGetMostRecentQuizzesCustomLimit(): void
    {
        $quizzes = [
            $this->createMock(Quiz::class)
        ];

        $this->quizRepository->expects($this->once())
            ->method('findMostRecent')
            ->with(10)
            ->willReturn($quizzes);

        $result = $this->service->getMostRecentQuizzes(10);

        $this->assertSame($quizzes, $result);
        $this->assertCount(1, $result);
    }

    public function testGetMostRecentQuizzesEmpty(): void
    {
        $this->quizRepository->expects($this->once())
            ->method('findMostRecent')
            ->with(6)
            ->willReturn([]);

        $result = $this->service->getMostRecentQuizzes();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

}
