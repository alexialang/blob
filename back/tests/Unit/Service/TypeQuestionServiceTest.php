<?php

namespace App\Tests\Unit\Service;

use App\Entity\TypeQuestion;
use App\Repository\TypeQuestionRepository;
use App\Service\TypeQuestionService;
use PHPUnit\Framework\TestCase;

class TypeQuestionServiceTest extends TestCase
{
    private TypeQuestionService $service;
    private TypeQuestionRepository $typeQuestionRepository;

    protected function setUp(): void
    {
        $this->typeQuestionRepository = $this->createMock(TypeQuestionRepository::class);

        $this->service = new TypeQuestionService(
            $this->typeQuestionRepository
        );
    }

    public function testList(): void
    {
        $typeQuestions = [
            $this->createMock(TypeQuestion::class),
            $this->createMock(TypeQuestion::class),
            $this->createMock(TypeQuestion::class),
        ];

        $this->typeQuestionRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($typeQuestions);

        $result = $this->service->list();

        $this->assertSame($typeQuestions, $result);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testListEmpty(): void
    {
        $this->typeQuestionRepository->expects($this->once())
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
        $typeQuestion = $this->createMock(TypeQuestion::class);

        $this->typeQuestionRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($typeQuestion);

        $result = $this->service->find($id);

        $this->assertSame($typeQuestion, $result);
    }

    public function testFindNotFound(): void
    {
        $id = 999;

        $this->typeQuestionRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->find($id);

        $this->assertNull($result);
    }

    public function testFindWithZero(): void
    {
        $id = 0;

        $this->typeQuestionRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->find($id);

        $this->assertNull($result);
    }

    public function testFindWithNegativeId(): void
    {
        $id = -1;

        $this->typeQuestionRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->find($id);

        $this->assertNull($result);
    }
}
