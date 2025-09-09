<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Strict Transport Security (HSTS)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Empêche l'affichage en iframe (clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêche la détection automatique du type MIME
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protection XSS basique
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Contrôle du referrer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP) - API stricte
        $csp = "default-src 'none'; ".
               "connect-src 'self'; ".
               "frame-ancestors 'none'; ".
               "base-uri 'none'";
        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy (Feature Policy)
        $response->headers->set('Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), '.
            'magnetometer=(), microphone=(), payment=(), usb=()'
        );
    }
}
