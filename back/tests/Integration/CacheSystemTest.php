<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CacheSystemTest extends KernelTestCase
{
    public function testCacheDirectoryExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $cacheDir = $container->getParameter('kernel.cache_dir');
        $this->assertNotEmpty($cacheDir);
        $this->assertDirectoryExists($cacheDir);
    }

    public function testProjectDirectoryExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $projectDir = $container->getParameter('kernel.project_dir');
        $this->assertNotEmpty($projectDir);
        $this->assertDirectoryExists($projectDir);
    }

    public function testKernelBuildDirectoryExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $buildDir = $container->getParameter('kernel.build_dir');
        $this->assertNotEmpty($buildDir);
    }
}
