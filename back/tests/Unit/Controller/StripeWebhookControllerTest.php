<?php

namespace App\Tests\Unit\Controller;

use App\Controller\StripeWebhookController;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StripeWebhookControllerTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testStripeWebhookControllerCanBeInstantiated(): void
    {
        $controller = new StripeWebhookController($this->logger, 'test_key', 'test_secret');
        $this->assertInstanceOf(StripeWebhookController::class, $controller);
    }

    public function testStripeWebhookControllerHasMethods(): void
    {
        $controller = new StripeWebhookController($this->logger, 'test_key', 'test_secret');
        $this->assertTrue(method_exists($controller, 'webhook'));
    }
}
