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
        $this->assertTrue(method_exists($controller, 'submitAnswer'));
        $this->assertTrue(method_exists($controller, 'getUserAnswers'));
        $this->assertTrue(method_exists($controller, 'getAnswer'));
        $this->assertTrue(method_exists($controller, 'updateAnswer'));
        $this->assertTrue(method_exists($controller, 'deleteAnswer'));
        $this->assertTrue(method_exists($controller, 'getAnswersByQuiz'));
        $this->assertTrue(method_exists($controller, 'getAnswersByUser'));
        $this->assertTrue(method_exists($controller, 'getAnswerStatistics'));
        $this->assertTrue(method_exists($controller, 'validateAnswer'));
    }
}
