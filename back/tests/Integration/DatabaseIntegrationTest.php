<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseIntegrationTest extends KernelTestCase
{
    public function testDoctrineServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        // Vérifier que le service Doctrine existe
        $this->assertTrue($container->has('doctrine'));
    }
    
    public function testEntityManagerCanBeRetrieved(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $doctrine = $container->get('doctrine');
        $entityManager = $doctrine->getManager();
        
        $this->assertNotNull($entityManager);
        $this->assertInstanceOf(\Doctrine\ORM\EntityManagerInterface::class, $entityManager);
    }
}