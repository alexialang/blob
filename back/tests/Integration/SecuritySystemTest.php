<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecuritySystemTest extends KernelTestCase
{
    public function testKernelEnvironmentIsTest(): void
    {
        $kernel = self::bootKernel();
        
        $this->assertEquals('test', $kernel->getEnvironment());
    }
    
    public function testKernelIsDebugMode(): void
    {
        $kernel = self::bootKernel();
        
        $this->assertTrue($kernel->isDebug());
    }
    
    public function testKernelHasContainer(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertNotNull($container);
    }
    
    public function testContainerHasRequiredParameters(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->hasParameter('kernel.environment'));
        $this->assertTrue($container->hasParameter('kernel.debug'));
    }
}