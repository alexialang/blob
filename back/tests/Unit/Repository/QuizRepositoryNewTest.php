<?php

namespace App\Tests\Unit\Repository;

use App\Repository\QuizRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class QuizRepositoryNewTest extends TestCase
{
    private ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
    }

    public function testQuizRepositoryCanBeInstantiated(): void
    {
        $repository = new QuizRepository($this->managerRegistry);
        $this->assertInstanceOf(QuizRepository::class, $repository);
    }

    public function testQuizRepositoryHasMethods(): void
    {
        $repository = new QuizRepository($this->managerRegistry);
        $this->assertTrue(method_exists($repository, 'findByUser'));
        $this->assertTrue(method_exists($repository, 'findPublishedOrAll'));
        $this->assertTrue(method_exists($repository, 'findWithPagination'));
        $this->assertTrue(method_exists($repository, 'findPrivateQuizzesForUserGroups'));
        $this->assertTrue(method_exists($repository, 'findMostPopular'));
        $this->assertTrue(method_exists($repository, 'findMostRecent'));
        $this->assertTrue(method_exists($repository, 'canUserModifyQuiz'));
        $this->assertTrue(method_exists($repository, 'findWithUserAccess'));
        $this->assertTrue(method_exists($repository, 'findWithAllRelations'));
        $this->assertTrue(method_exists($repository, 'findPrivateQuizzesForUser'));
    }
}