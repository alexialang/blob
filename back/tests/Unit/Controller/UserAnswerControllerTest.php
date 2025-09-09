<?php

namespace App\Tests\Unit\Controller;

use App\Controller\UserAnswerController;
use App\Service\UserAnswerService;
use PHPUnit\Framework\TestCase;

class UserAnswerControllerTest extends TestCase
{
    private UserAnswerService $userAnswerService;

    protected function setUp(): void
    {
        $this->userAnswerService = $this->createMock(UserAnswerService::class);
    }

    public function testUserAnswerControllerCanBeInstantiated(): void
    {
        $controller = new UserAnswerController($this->userAnswerService);
        $this->assertInstanceOf(UserAnswerController::class, $controller);
    }

    public function testUserAnswerControllerHasMethods(): void
    {
        $controller = new UserAnswerController($this->userAnswerService);
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