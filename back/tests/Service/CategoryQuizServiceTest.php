<?php

namespace App\Tests\Service;

use App\Entity\CategoryQuiz;
use App\Repository\CategoryQuizRepository;
use App\Service\CategoryQuizService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryQuizServiceTest extends TestCase
{
    private CategoryQuizService $categoryQuizService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|CategoryQuizRepository $categoryQuizRepository;
    private MockObject|ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->categoryQuizRepository = $this->createMock(CategoryQuizRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        
        $this->categoryQuizService = new CategoryQuizService(
            $this->entityManager,
            $this->categoryQuizRepository,
            $this->validator
        );
    }

    public function testList(): void
    {
        $categories = [new CategoryQuiz(), new CategoryQuiz()];
        
        $this->categoryQuizRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($categories);

        $result = $this->categoryQuizService->list();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(CategoryQuiz::class, $result);
    }

    public function testFind(): void
    {
        $category = new CategoryQuiz();
        $categoryId = 1;
        
        $this->categoryQuizRepository
            ->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $result = $this->categoryQuizService->find($categoryId);

        $this->assertSame($category, $result);
    }

    public function testFindNotFound(): void
    {
        $categoryId = 999;
        
        $this->categoryQuizRepository
            ->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn(null);

        $result = $this->categoryQuizService->find($categoryId);

        $this->assertNull($result);
    }
}

