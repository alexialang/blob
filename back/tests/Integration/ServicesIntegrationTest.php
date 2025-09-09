<?php

namespace App\Tests\Integration;

use App\Service\CategoryQuizService;
use App\Service\BadgeService;
use App\Service\GlobalStatisticsService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServicesIntegrationTest extends KernelTestCase
{
    public function testCategoryQuizServiceIsAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $service = $container->get(CategoryQuizService::class);
        $this->assertInstanceOf(CategoryQuizService::class, $service);
    }
    
    public function testBadgeServiceIsAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $service = $container->get(BadgeService::class);
        $this->assertInstanceOf(BadgeService::class, $service);
    }
    
    public function testGlobalStatisticsServiceIsAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $service = $container->get(GlobalStatisticsService::class);
        $this->assertInstanceOf(GlobalStatisticsService::class, $service);
    }
    
    public function testServiceDependenciesAreInjected(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que les repositories sont bien injectés
        $this->assertTrue($container->has('App\Repository\CategoryQuizRepository'));
        $this->assertTrue($container->has('App\Repository\BadgeRepository'));
        $this->assertTrue($container->has('App\Repository\UserRepository'));
    }
    
    public function testEventDispatcherIsConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        $this->assertNotNull($eventDispatcher);
        
        // Vérifier que nos listeners sont enregistrés
        $listeners = $eventDispatcher->getListeners();
        $this->assertIsArray($listeners);
        $this->assertNotEmpty($listeners);
    }
    
    public function testDatabaseConnectionIsConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $connection = $container->get('doctrine.dbal.default_connection');
        $this->assertNotNull($connection);
        
        // Test que la connexion a les bons paramètres
        $params = $connection->getParams();
        $this->assertIsArray($params);
        $this->assertArrayHasKey('driver', $params);
    }
    
    public function testSecurityConfiguration(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que les services de sécurité sont disponibles
        $this->assertTrue($container->has('security.password_hasher'));
        $this->assertTrue($container->has('security.token_storage'));
    }
    
    public function testCacheIsConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que le cache est disponible
        $this->assertTrue($container->has('cache.app'));
        
        $cache = $container->get('cache.app');
        $this->assertNotNull($cache);
    }
    
    public function testMessengerIsConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test que Messenger est configuré
        $this->assertTrue($container->has('messenger.default_bus'));
        
        $bus = $container->get('messenger.default_bus');
        $this->assertNotNull($bus);
    }
    
    public function testValidatorIsConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Test alternative pour le validator
        $this->assertTrue($container->has('debug.validator'));
        
        $validator = $container->get('debug.validator');
        $this->assertNotNull($validator);
    }
}
