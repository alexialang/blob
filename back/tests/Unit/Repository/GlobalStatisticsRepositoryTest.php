<?php

namespace App\Tests\Unit\Repository;

use App\Repository\GlobalStatisticsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class GlobalStatisticsRepositoryTest extends TestCase
{
    private GlobalStatisticsRepository $repository;
    private ManagerRegistry $managerRegistry;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = new GlobalStatisticsRepository($this->managerRegistry);
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(GlobalStatisticsRepository::class, $this->repository);
    }

    public function testEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }

    public function testGetTeamScoresByQuizMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getTeamScoresByQuiz'));
    }

    public function testGetTeamScoresByQuizForCompanyMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getTeamScoresByQuizForCompany'));
    }

    public function testGetGroupScoresByQuizMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getGroupScoresByQuiz'));
    }

    public function testGetGroupScoresByQuizForCompanyMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getGroupScoresByQuizForCompany'));
    }

    public function testRepositoryHasCorrectConstructor(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('registry', $parameters[0]->getName());
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $parentClass = $reflection->getParentClass();
        
        $this->assertNotNull($parentClass);
        $this->assertEquals('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository', $parentClass->getName());
    }

    public function testGetTeamScoresByQuizMethodSignature(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getTeamScoresByQuiz');
        
        $this->assertNotNull($method);
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('limit', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(20, $parameters[0]->getDefaultValue());
    }

    public function testGetTeamScoresByQuizForCompanyMethodSignature(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getTeamScoresByQuizForCompany');
        
        $this->assertNotNull($method);
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('companyId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals(20, $parameters[1]->getDefaultValue());
    }

    public function testGetGroupScoresByQuizMethodSignature(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getGroupScoresByQuiz');
        
        $this->assertNotNull($method);
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('limit', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(100, $parameters[0]->getDefaultValue());
    }

    public function testGetGroupScoresByQuizForCompanyMethodSignature(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getGroupScoresByQuizForCompany');
        
        $this->assertNotNull($method);
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('companyId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals(100, $parameters[1]->getDefaultValue());
    }

    public function testRepositoryMethodsReturnArray(): void
    {
        // Test que les méthodes existent et ont la bonne signature de retour
        $reflection = new \ReflectionClass($this->repository);
        
        $methods = [
            'getTeamScoresByQuiz',
            'getTeamScoresByQuizForCompany', 
            'getGroupScoresByQuiz',
            'getGroupScoresByQuizForCompany'
        ];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertNotNull($method);
            $this->assertEquals('array', $method->getReturnType()->getName());
        }
    }

    public function testRepositoryHasCorrectNamespace(): void
    {
        $this->assertEquals('App\Repository\GlobalStatisticsRepository', $this->repository::class);
    }

    public function testRepositoryIsNotAbstract(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testRepositoryIsNotInterface(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertFalse($reflection->isInterface());
    }

    public function testRepositoryIsNotTrait(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertFalse($reflection->isTrait());
    }
}
