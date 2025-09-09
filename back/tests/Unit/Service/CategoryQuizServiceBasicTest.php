<?php

namespace App\Tests\Unit\Service;

use App\Entity\CategoryQuiz;
use App\Repository\CategoryQuizRepository;
use App\Service\CategoryQuizService;
use PHPUnit\Framework\TestCase;

class CategoryQuizServiceBasicTest extends TestCase
{
    private CategoryQuizService $service;
    private CategoryQuizRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryQuizRepository::class);
        $this->service = new CategoryQuizService($this->repository);
    }

    public function testList(): void
    {
        $categories = [
            $this->createMock(CategoryQuiz::class),
            $this->createMock(CategoryQuiz::class),
        ];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($categories);

        $result = $this->service->list();

        $this->assertSame($categories, $result);
        $this->assertCount(2, $result);
    }

    public function testFindWithValidId(): void
    {
        $category = $this->createMock(CategoryQuiz::class);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($category);

        $result = $this->service->find(123);

        $this->assertSame($category, $result);
    }

    public function testFindWithNotFoundId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    public function testFindWithInvalidIdZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'ID de la catégorie doit être positif');

        $this->service->find(0);
    }

    public function testFindWithInvalidIdNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'ID de la catégorie doit être positif');

        $this->service->find(-5);
    }
}
