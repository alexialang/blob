<?php

namespace App\Tests\Integration;

use App\Repository\AnswerRepository;
use App\Repository\BadgeRepository;
use App\Repository\CategoryQuizRepository;
use App\Repository\CompanyRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use App\Repository\RoomPlayerRepository;
use App\Repository\RoomRepository;
use App\Repository\UserAnswerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RepositoryClassesTest extends KernelTestCase
{
    public function testUserRepositoryClass(): void
    {
        $this->assertTrue(class_exists(UserRepository::class));
    }

    public function testQuizRepositoryClass(): void
    {
        $this->assertTrue(class_exists(QuizRepository::class));
    }

    public function testBadgeRepositoryClass(): void
    {
        $this->assertTrue(class_exists(BadgeRepository::class));
    }

    public function testCategoryQuizRepositoryClass(): void
    {
        $this->assertTrue(class_exists(CategoryQuizRepository::class));
    }

    public function testCompanyRepositoryClass(): void
    {
        $this->assertTrue(class_exists(CompanyRepository::class));
    }

    public function testQuestionRepositoryClass(): void
    {
        $this->assertTrue(class_exists(QuestionRepository::class));
    }

    public function testAnswerRepositoryClass(): void
    {
        $this->assertTrue(class_exists(AnswerRepository::class));
    }

    public function testRoomRepositoryClass(): void
    {
        $this->assertTrue(class_exists(RoomRepository::class));
    }

    public function testRoomPlayerRepositoryClass(): void
    {
        $this->assertTrue(class_exists(RoomPlayerRepository::class));
    }

    public function testUserAnswerRepositoryClass(): void
    {
        $this->assertTrue(class_exists(UserAnswerRepository::class));
    }

    public function testRepositoryInheritance(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }

    public function testRepositoryMethods(): void
    {
        $reflection = new \ReflectionClass(CategoryQuizRepository::class);
        $this->assertTrue($reflection->hasMethod('find'));
        $this->assertTrue($reflection->hasMethod('findAll'));
    }

    public function testRepositoryConstructor(): void
    {
        $reflection = new \ReflectionClass(BadgeRepository::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
    }

    public function testRepositoryProperties(): void
    {
        $reflection = new \ReflectionClass(QuizRepository::class);
        $this->assertTrue($reflection->hasMethod('createQueryBuilder'));
    }

    public function testAllRepositoriesExist(): void
    {
        $repositories = [
            UserRepository::class,
            QuizRepository::class,
            BadgeRepository::class,
            CategoryQuizRepository::class,
            CompanyRepository::class,
        ];

        foreach ($repositories as $repository) {
            $this->assertTrue(class_exists($repository), "Repository $repository should exist");
        }
    }
}
