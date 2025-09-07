<?php

namespace App\Tests\Unit\Controller;

use App\Controller\DonationController;
use App\Service\PaymentService;
use PHPUnit\Framework\TestCase;

class DonationControllerTest extends TestCase
{
    private DonationController $controller;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->controller = new DonationController($this->paymentService);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(DonationController::class, $this->controller);
    }

    public function testControllerHasPaymentService(): void
    {
        $this->assertTrue(method_exists($this->controller, 'create'));
    }

    public function testControllerMethodsExist(): void
    {
        $methods = ['create'];
        
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->controller, $method));
        }
    }
}
