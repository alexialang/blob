<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceContainerTest extends KernelTestCase
{
    public function testRouterServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->has('router'));
    }
    
    public function testRequestStackServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->has('request_stack'));
    }
    
    public function testHttpKernelServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->has('http_kernel'));
    }
    
    public function testCacheServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->has('cache.app'));
    }
    
    public function testEventDispatcherServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->has('event_dispatcher'));
    }
}
