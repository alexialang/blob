<?php

namespace App\Tests\Integration;

use App\Controller\BadgeController;
use App\Controller\CategoryQuizController;
use App\Controller\DonationController;
use App\Controller\QuizController;
use App\Controller\UserController;
use App\Controller\StatusController;
use App\Controller\CompanyController;
use App\Controller\GroupController;
use App\Controller\LeaderboardController;
use App\Controller\MultiplayerGameController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ControllerClassesTest extends KernelTestCase
{
    public function testBadgeControllerClass(): void
    {
        $this->assertTrue(class_exists(BadgeController::class));
    }
    
    public function testCategoryQuizControllerClass(): void
    {
        $this->assertTrue(class_exists(CategoryQuizController::class));
    }
    
    public function testDonationControllerClass(): void
    {
        $this->assertTrue(class_exists(DonationController::class));
    }
    
    public function testQuizControllerClass(): void
    {
        $this->assertTrue(class_exists(QuizController::class));
    }
    
    public function testUserControllerClass(): void
    {
        $this->assertTrue(class_exists(UserController::class));
    }
    
    public function testStatusControllerClass(): void
    {
        $this->assertTrue(class_exists(StatusController::class));
    }
    
    public function testCompanyControllerClass(): void
    {
        $this->assertTrue(class_exists(CompanyController::class));
    }
    
    public function testGroupControllerClass(): void
    {
        $this->assertTrue(class_exists(GroupController::class));
    }
    
    public function testLeaderboardControllerClass(): void
    {
        $this->assertTrue(class_exists(LeaderboardController::class));
    }
    
    public function testMultiplayerGameControllerClass(): void
    {
        $this->assertTrue(class_exists(MultiplayerGameController::class));
    }
    
    public function testControllerInheritance(): void
    {
        $reflection = new \ReflectionClass(BadgeController::class);
        $this->assertTrue($reflection->isSubclassOf('Symfony\Bundle\FrameworkBundle\Controller\AbstractController'));
    }
    
    public function testControllerMethods(): void
    {
        $reflection = new \ReflectionClass(CategoryQuizController::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->assertNotEmpty($methods);
    }
    
    public function testControllerConstructor(): void
    {
        $reflection = new \ReflectionClass(DonationController::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
    }
    
    public function testControllerImplementsLogic(): void
    {
        $reflection = new \ReflectionClass(StatusController::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->assertGreaterThan(0, count($methods));
    }
    
    public function testAllControllersExist(): void
    {
        $controllers = [
            BadgeController::class,
            CategoryQuizController::class,
            DonationController::class,
            QuizController::class,
            UserController::class
        ];
        
        foreach ($controllers as $controller) {
            $this->assertTrue(class_exists($controller), "Controller $controller should exist");
        }
    }
}
