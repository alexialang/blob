<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BasicContainerTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        $kernel = self::bootKernel();
        $this->assertNotNull($kernel);
    }

    public function testContainerExists(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->assertNotNull($container);
    }

    public function testEnvironmentIsTest(): void
    {
        self::bootKernel();
        $this->assertEquals('test', self::$kernel->getEnvironment());
    }

    public function testDebugIsEnabled(): void
    {
        self::bootKernel();
        $this->assertTrue(self::$kernel->isDebug());
    }

    public function testProjectDirExists(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $projectDir = $container->getParameter('kernel.project_dir');
        $this->assertDirectoryExists($projectDir);
    }

    public function testCacheDirExists(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $cacheDir = $container->getParameter('kernel.cache_dir');
        $this->assertDirectoryExists($cacheDir);
    }

    public function testLogDirExists(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $logDir = $container->getParameter('kernel.logs_dir');
        $this->assertDirectoryExists($logDir);
    }

    public function testContainerHasBasicServices(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('kernel'));
        $this->assertTrue($container->has('logger'));
    }

    public function testBundleConfiguration(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertNotEmpty($bundles);
    }

    public function testParameterBagExists(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->assertTrue($container->hasParameter('kernel.charset'));
    }
}
