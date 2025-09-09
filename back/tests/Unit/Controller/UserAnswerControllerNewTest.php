<?php

namespace App\Tests\Unit\Controller;

use App\Controller\UserAnswerController;
use PHPUnit\Framework\TestCase;

class UserAnswerControllerNewTest extends TestCase
{
    public function testUserAnswerControllerCanBeInstantiated(): void
    {
        $userAnswerService = $this->createMock(\App\Service\UserAnswerService::class);
        
        $controller = new UserAnswerController($userAnswerService);
        $this->assertInstanceOf(UserAnswerController::class, $controller);
    }

    public function testUserAnswerControllerHasMethods(): void
    {
        $userAnswerService = $this->createMock(\App\Service\UserAnswerService::class);
        
        $controller = new UserAnswerController($userAnswerService);
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'saveGameResult'));
        $this->assertTrue(method_exists($controller, 'rateQuiz'));
        $this->assertTrue(method_exists($controller, 'getQuizRating'));
    }
}
