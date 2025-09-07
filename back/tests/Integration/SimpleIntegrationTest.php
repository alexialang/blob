<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SimpleIntegrationTest extends KernelTestCase
{
    public function testKernelBootstrap(): void
    {
        $kernel = self::bootKernel();
        
        $this->assertNotNull($kernel);
        $this->assertNotNull($kernel->getContainer());
        $this->assertEquals('test', $kernel->getEnvironment());
    }
    
    public function testDatabaseConnection(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->assertNotNull($entityManager);
    }
    
    public function testServiceContainer(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test some basic services
        $this->assertTrue($container->has('doctrine'));
        $this->assertTrue($container->has('event_dispatcher'));
        $this->assertTrue($container->has('validator'));
    }
    
    public function testEnvironmentConfiguration(): void
    {
        self::bootKernel();
        
        $this->assertEquals('test', self::$kernel->getEnvironment());
        $this->assertTrue(self::$kernel->isDebug());
    }
    
    public function testContainerParameters(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->assertTrue($container->hasParameter('kernel.project_dir'));
        $this->assertTrue($container->hasParameter('kernel.environment'));
        $this->assertTrue($container->hasParameter('kernel.debug'));
    }
}
