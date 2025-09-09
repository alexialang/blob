<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\SecurityHeadersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EventListenerTest extends TestCase
{
    public function testSecurityHeadersListenerConstructor(): void
    {
        $listener = new SecurityHeadersListener();
        $this->assertInstanceOf(SecurityHeadersListener::class, $listener);
    }

    public function testSecurityHeadersListenerHasMethods(): void
    {
        $listener = new SecurityHeadersListener();
        $this->assertTrue(method_exists($listener, 'onKernelResponse'));
    }
}
