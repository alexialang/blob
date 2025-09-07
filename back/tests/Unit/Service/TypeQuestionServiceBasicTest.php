<?php

namespace App\Tests\Unit\Service;

use App\Entity\TypeQuestion;
use App\Repository\TypeQuestionRepository;
use App\Service\TypeQuestionService;
use PHPUnit\Framework\TestCase;

class TypeQuestionServiceBasicTest extends TestCase
{
    private TypeQuestionService $service;
    private TypeQuestionRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TypeQuestionRepository::class);
        $this->service = new TypeQuestionService($this->repository);
    }

    public function testList(): void
    {
        $types = [
            $this->createMock(TypeQuestion::class),
            $this->createMock(TypeQuestion::class),
            $this->createMock(TypeQuestion::class)
        ];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($types);

        $result = $this->service->list();

        $this->assertSame($types, $result);
        $this->assertCount(3, $result);
    }

    public function testFindWithValidId(): void
    {
        $typeQuestion = $this->createMock(TypeQuestion::class);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(456)
            ->willReturn($typeQuestion);

        $result = $this->service->find(456);

        $this->assertSame($typeQuestion, $result);
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

    public function testShowIsAliasForFind(): void
    {
        $typeQuestion = $this->createMock(TypeQuestion::class);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($typeQuestion);

        $result = $this->service->show(123);

        $this->assertSame($typeQuestion, $result);
    }

    public function testShowWithNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(888)
            ->willReturn(null);

        $result = $this->service->show(888);

        $this->assertNull($result);
    }
}
