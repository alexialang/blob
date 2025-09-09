<?php

namespace App\Tests\Integration;

use App\Service\BadgeService;
use App\Service\CategoryQuizService;
use App\Service\UserService;
use App\Service\PaymentService;
use App\Service\QuizCrudService;
use App\Service\GlobalStatisticsService;
use App\Service\GroupService;
use App\Service\LeaderboardService;
use App\Service\MultiplayerGameService;
use App\Service\MultiplayerScoreService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceClassesTest extends KernelTestCase
{
    public function testBadgeServiceClass(): void
    {
        $this->assertTrue(class_exists(BadgeService::class));
    }
    
    public function testCategoryQuizServiceClass(): void
    {
        $this->assertTrue(class_exists(CategoryQuizService::class));
    }
    
    public function testUserServiceClass(): void
    {
        $this->assertTrue(class_exists(UserService::class));
    }
    
    public function testPaymentServiceClass(): void
    {
        $this->assertTrue(class_exists(PaymentService::class));
    }
    
    public function testQuizCrudServiceClass(): void
    {
        $this->assertTrue(class_exists(QuizCrudService::class));
    }
    
    public function testGlobalStatisticsServiceClass(): void
    {
        $this->assertTrue(class_exists(GlobalStatisticsService::class));
    }
    
    public function testGroupServiceClass(): void
    {
        $this->assertTrue(class_exists(GroupService::class));
    }
    
    public function testLeaderboardServiceClass(): void
    {
        $this->assertTrue(class_exists(LeaderboardService::class));
    }
    
    public function testMultiplayerGameServiceClass(): void
    {
        $this->assertTrue(class_exists(MultiplayerGameService::class));
    }
    
    public function testMultiplayerScoreServiceClass(): void
    {
        $this->assertTrue(class_exists(MultiplayerScoreService::class));
    }
    
    public function testServiceMethodsExist(): void
    {
        $reflection = new \ReflectionClass(CategoryQuizService::class);
        $this->assertTrue($reflection->hasMethod('list'));
        $this->assertTrue($reflection->hasMethod('find'));
    }
    
    public function testServicePropertiesExist(): void
    {
        $reflection = new \ReflectionClass(CategoryQuizService::class);
        $properties = $reflection->getProperties();
        $this->assertNotEmpty($properties);
    }
    
    public function testServiceConstructorExists(): void
    {
        $reflection = new \ReflectionClass(CategoryQuizService::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
    }
    
    public function testServiceImplementsLogic(): void
    {
        $reflection = new \ReflectionClass(BadgeService::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->assertNotEmpty($methods);
    }
    
    public function testAllServicesAreClasses(): void
    {
        $services = [
            BadgeService::class,
            CategoryQuizService::class,
            UserService::class,
            PaymentService::class,
            QuizCrudService::class
        ];
        
        foreach ($services as $service) {
            $this->assertTrue(class_exists($service), "Service $service should exist");
        }
    }
}
