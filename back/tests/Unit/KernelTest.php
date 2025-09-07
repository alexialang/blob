<?php

namespace App\Tests\Unit;

use App\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testKernelInstantiation(): void
    {
        $kernel = new Kernel('test', true);
        
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertEquals('test', $kernel->getEnvironment());
        $this->assertTrue($kernel->isDebug());
    }
    
    public function testKernelInheritance(): void
    {
        $kernel = new Kernel('prod', false);
        
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Kernel', $kernel);
        $this->assertEquals('prod', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }
    
    public function testMicroKernelTrait(): void
    {
        $kernel = new Kernel('dev', true);
        
        $reflection = new \ReflectionClass($kernel);
        $traits = $reflection->getTraitNames();
        
        $this->assertContains('Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait', $traits);
    }
}

