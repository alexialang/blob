<?php

namespace App\Tests\Integration;

use App\Entity\CategoryQuiz;
use App\Entity\Quiz;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityRepositoryTest extends KernelTestCase
{
    public function testUserRepositoryExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $entityManager = $container->get('doctrine')->getManager();
        $repository = $entityManager->getRepository(User::class);

        $this->assertNotNull($repository);
        $this->assertInstanceOf(\App\Repository\UserRepository::class, $repository);
    }

    public function testQuizRepositoryExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $entityManager = $container->get('doctrine')->getManager();
        $repository = $entityManager->getRepository(Quiz::class);

        $this->assertNotNull($repository);
        $this->assertInstanceOf(\App\Repository\QuizRepository::class, $repository);
    }

    public function testCategoryQuizRepositoryExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $entityManager = $container->get('doctrine')->getManager();
        $repository = $entityManager->getRepository(CategoryQuiz::class);

        $this->assertNotNull($repository);
        $this->assertInstanceOf(\App\Repository\CategoryQuizRepository::class, $repository);
    }
}





