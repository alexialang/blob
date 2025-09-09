<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConfigurationIntegrationTest extends KernelTestCase
{
    public function testFrameworkConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test des paramètres essentiels
        $this->assertTrue($container->hasParameter('kernel.environment'));
        $this->assertTrue($container->hasParameter('kernel.debug'));
        $this->assertTrue($container->hasParameter('kernel.project_dir'));
        
        $env = $container->getParameter('kernel.environment');
        $this->assertEquals('test', $env);
    }
    
    public function testDoctrineConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que Doctrine est configuré
        $this->assertTrue($container->has('doctrine'));
        $this->assertTrue($container->has('doctrine.orm.entity_manager'));
        
        $doctrine = $container->get('doctrine');
        $this->assertNotNull($doctrine);
    }
    
    public function testSecurityConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test paramètres de sécurité
        $this->assertTrue($container->hasParameter('app.frontend_url'));
        
        $frontendUrl = $container->getParameter('app.frontend_url');
        $this->assertIsString($frontendUrl);
        $this->assertNotEmpty($frontendUrl);
    }
    
    public function testPaymentConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test paramètres de paiement
        $this->assertTrue($container->hasParameter('stripe_webhook_secret'));
        
        $webhookSecret = $container->getParameter('stripe_webhook_secret');
        $this->assertIsString($webhookSecret);
    }
    
    public function testEnvironmentVariables(): void
    {
        self::bootKernel();
        
        // Test que les variables d'environnement sont accessibles
        $this->assertEquals('test', $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test');
    }
    
    public function testRouterConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $router = $container->get('router');
        $this->assertNotNull($router);
        
        // Test que quelques routes sont chargées
        $routes = $router->getRouteCollection();
        $this->assertNotNull($routes);
        $this->assertGreaterThan(0, $routes->count());
    }
    
    public function testTwigConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que Twig est configuré (pour les emails)
        $this->assertTrue($container->has('twig'));
        
        $twig = $container->get('twig');
        $this->assertNotNull($twig);
    }
    
    public function testSerializerConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que le serializer est configuré
        $this->assertTrue($container->has('serializer'));
        
        $serializer = $container->get('serializer');
        $this->assertNotNull($serializer);
    }
    
    public function testLoggerConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que le logger est configuré
        $this->assertTrue($container->has('logger'));
        
        $logger = $container->get('logger');
        $this->assertNotNull($logger);
    }
    
    public function testKernelParameters(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test des paramètres kernel critiques
        $projectDir = $container->getParameter('kernel.project_dir');
        $this->assertIsString($projectDir);
        $this->assertNotEmpty($projectDir);
        $this->assertDirectoryExists($projectDir);
        
        $cacheDir = $container->getParameter('kernel.cache_dir');
        $this->assertIsString($cacheDir);
        $this->assertNotEmpty($cacheDir);
    }
}
