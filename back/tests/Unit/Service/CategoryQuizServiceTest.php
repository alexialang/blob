<?php

namespace App\Tests\Unit\Service;

use App\Entity\CategoryQuiz;
use App\Repository\CategoryQuizRepository;
use App\Service\CategoryQuizService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryQuizServiceTest extends TestCase
{
    private CategoryQuizService $service;
    private EntityManagerInterface $em;
    private CategoryQuizRepository $categoryQuizRepository;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->categoryQuizRepository = $this->createMock(CategoryQuizRepository::class);

        $this->service = new CategoryQuizService(
            $this->categoryQuizRepository
        );
    }

    public function testList(): void
    {
        $categories = [
            $this->createMock(CategoryQuiz::class),
            $this->createMock(CategoryQuiz::class),
            $this->createMock(CategoryQuiz::class)
        ];

        $this->categoryQuizRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($categories);

        $result = $this->service->list();

        $this->assertSame($categories, $result);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testListEmpty(): void
    {
        $this->categoryQuizRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFind(): void
    {
        $id = 123;
        $category = $this->createMock(CategoryQuiz::class);

        $this->categoryQuizRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($category);

        $result = $this->service->find($id);

        $this->assertSame($category, $result);
    }

    public function testFindNotFound(): void
    {
        $id = 999;

        $this->categoryQuizRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->find($id);

        $this->assertNull($result);
    }

    public function testFindWithValidPositiveId(): void
    {
        $id = 1;
        $category = $this->createMock(CategoryQuiz::class);

        $this->categoryQuizRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($category);

        $result = $this->service->find($id);

        $this->assertSame($category, $result);
    }

    public function testFindWithZeroIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'ID de la catégorie doit être positif');

        $this->categoryQuizRepository->expects($this->never())
            ->method('find');

        $this->service->find(0);
    }

    public function testFindWithNegativeIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'ID de la catégorie doit être positif');

        $this->categoryQuizRepository->expects($this->never())
            ->method('find');

        $this->service->find(-1);
    }

    public function testFindWithNegativeIdLargeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'ID de la catégorie doit être positif');

        $this->categoryQuizRepository->expects($this->never())
            ->method('find');

        $this->service->find(-999);
    }
}
