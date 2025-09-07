<?php

namespace App\Tests\Unit;

use App\Kernel;
use PHPUnit\Framework\TestCase;

class KernelBasicTest extends TestCase
{
    public function testKernelCreation(): void
    {
        $kernel = new Kernel('test', true);
        
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertEquals('test', $kernel->getEnvironment());
        $this->assertTrue($kernel->isDebug());
    }

    public function testKernelWithProdEnvironment(): void
    {
        $kernel = new Kernel('prod', false);
        
        $this->assertEquals('prod', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    public function testKernelProjectDir(): void
    {
        $kernel = new Kernel('test', true);
        $projectDir = $kernel->getProjectDir();
        
        $this->assertNotEmpty($projectDir);
        $this->assertIsString($projectDir);
    }

    public function testKernelLogDir(): void
    {
        $kernel = new Kernel('test', true);
        $logDir = $kernel->getLogDir();
        
        $this->assertNotEmpty($logDir);
        $this->assertIsString($logDir);
    }

    public function testKernelCacheDir(): void
    {
        $kernel = new Kernel('test', true);
        $cacheDir = $kernel->getCacheDir();
        
        $this->assertNotEmpty($cacheDir);
        $this->assertIsString($cacheDir);
        $this->assertStringContainsString('test', $cacheDir);
    }

    public function testKernelCharset(): void
    {
        $kernel = new Kernel('test', true);
        
        $this->assertEquals('UTF-8', $kernel->getCharset());
    }

    public function testKernelEnvironmentVariations(): void
    {
        $environments = ['dev', 'test', 'prod'];
        
        foreach ($environments as $env) {
            $kernel = new Kernel($env, true);
            $this->assertEquals($env, $kernel->getEnvironment());
        }
    }

    public function testKernelDebugVariations(): void
    {
        $kernel1 = new Kernel('dev', true);
        $kernel2 = new Kernel('prod', false);
        
        $this->assertTrue($kernel1->isDebug());
        $this->assertFalse($kernel2->isDebug());
    }
}
