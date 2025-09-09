<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\SecurityHeadersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityHeadersListenerTest extends TestCase
{
    public function testSecurityHeadersListenerConstructor(): void
    {
        $listener = new SecurityHeadersListener();
        $this->assertInstanceOf(SecurityHeadersListener::class, $listener);
    }

    public function testSecurityHeadersListenerHasOnKernelResponseMethod(): void
    {
        $listener = new SecurityHeadersListener();
        $this->assertTrue(method_exists($listener, 'onKernelResponse'));
    }

    public function testSecurityHeadersListenerOnKernelResponse(): void
    {
        $listener = new SecurityHeadersListener();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response();
        
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        
        $listener->onKernelResponse($event);
        
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertTrue($response->headers->has('X-XSS-Protection'));
    }
}

