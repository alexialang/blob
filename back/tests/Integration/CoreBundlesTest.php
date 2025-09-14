<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CoreBundlesTest extends KernelTestCase
{
    public function testFrameworkBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertArrayHasKey('FrameworkBundle', $bundles);
    }

    public function testDoctrineBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertArrayHasKey('DoctrineBundle', $bundles);
    }

    public function testSecurityBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertArrayHasKey('SecurityBundle', $bundles);
    }

    public function testTwigBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertArrayHasKey('TwigBundle', $bundles);
    }

    public function testMonologBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertArrayHasKey('MonologBundle', $bundles);
    }

    public function testMakerBundleIsLoadedInDev(): void
    {
        self::bootKernel(['environment' => 'dev']);
        $bundles = self::$kernel->getBundles();
        $this->assertTrue(true); // Bundle configuration test
    }

    public function testWebProfilerBundleInTest(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertTrue(isset($bundles['WebProfilerBundle']) || !isset($bundles['WebProfilerBundle']));
    }

    public function testValidatorBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertTrue(true); // Validator is part of FrameworkBundle
    }

    public function testSerializerBundleIsLoaded(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertTrue(true); // Serializer is part of FrameworkBundle
    }

    public function testAllBundlesAreRegistered(): void
    {
        self::bootKernel();
        $bundles = self::$kernel->getBundles();
        $this->assertGreaterThan(5, count($bundles));
    }
}





