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
        $request = $event->getRequest();

        // Vérifier si on est en mode développement
        $isDev = 'dev' === $request->server->get('APP_ENV');

        // Strict Transport Security (HSTS) - seulement en production
        if (!$isDev) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Empêche l'affichage en iframe (clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêche la détection automatique du type MIME
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protection XSS basique
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Contrôle du referrer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP) - différente selon l'environnement
        // Ne pas appliquer de CSP stricte au frontend Angular
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api')) {
            // Frontend Angular - pas de CSP stricte
            return;
        }
        
        if ($isDev) {
            // CSP permissive pour le développement (Web Debug Toolbar + Swagger UI)
            $csp = "default-src 'self'; ".
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; ".
                   "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ".
                   "img-src 'self' data: https://cdn.jsdelivr.net; ".
                   "font-src 'self' data: https://cdn.jsdelivr.net; ".
                   "connect-src 'self' ws: wss: http: https:; ".
                   "frame-src 'self'; ".
                   "frame-ancestors 'none'; ".
                   "base-uri 'self'";
        } else {
            // CSP stricte pour la production API uniquement
            $csp = "default-src 'none'; ".
                   "connect-src 'self'; ".
                   "frame-ancestors 'none'; ".
                   "base-uri 'none'";
        }
        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy (Feature Policy)
        $response->headers->set('Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), '.
            'magnetometer=(), microphone=(), payment=(), usb=()'
        );
    }
}
