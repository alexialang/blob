<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiCategoryQuizTest extends KernelTestCase
{
    public function testRouterServiceExists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Vérifier que le service router existe
        $this->assertTrue($container->has('router'));
    }

    public function testApiRouteDefinedInRouting(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $router = $container->get('router');
        $routes = $router->getRouteCollection();

        // Vérifier qu'au moins une route est définie
        $this->assertGreaterThan(0, count($routes));

        // Vérifier qu'il y a des routes API
        $apiRoutes = [];
        foreach ($routes as $route) {
            if (str_contains($route->getPath(), '/api/')) {
                $apiRoutes[] = $route;
            }
        }

        $this->assertGreaterThan(0, count($apiRoutes));
    }
}
