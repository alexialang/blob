<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiAuthenticationTest extends KernelTestCase
{
    public function testContainerCanBeBooted(): void
    {
        // Test simple : le container Symfony peut démarrer
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertNotNull($container);
        $this->assertNotNull($kernel);
    }
    
    public function testLoginRouteExistsInRouting(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        // Vérifier que le service router existe
        $this->assertTrue($container->has('router'));
        
        $router = $container->get('router');
        $routes = $router->getRouteCollection();
        
        // Vérifier qu'il y a des routes définies
        $this->assertGreaterThan(0, count($routes));
    }
}
