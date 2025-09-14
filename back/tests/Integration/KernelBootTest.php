<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KernelBootTest extends KernelTestCase
{
    public function testKernelBootsInTestEnvironment(): void
    {
        $kernel = self::bootKernel(['environment' => 'test']);

        $this->assertSame('test', $kernel->getEnvironment());
        $this->assertNotNull($kernel->getContainer());
    }

    public function testParametersAreLoaded(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Vérifier que le paramètre kernel.project_dir existe
        $this->assertTrue($container->hasParameter('kernel.project_dir'));
        $this->assertNotEmpty($container->getParameter('kernel.project_dir'));
    }

    public function testBundlesAreLoaded(): void
    {
        $kernel = self::bootKernel();
        $bundles = $kernel->getBundles();

        $this->assertNotEmpty($bundles);
        $this->assertArrayHasKey('FrameworkBundle', $bundles);
        $this->assertArrayHasKey('DoctrineBundle', $bundles);
    }

    public function testConfigurationIsValid(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Si le container peut être compilé, la configuration est valide
        $this->assertNotNull($container);
        $this->assertTrue($container->hasParameter('kernel.environment'));
    }
}





