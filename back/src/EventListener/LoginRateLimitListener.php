<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoginRateLimitListener implements EventSubscriberInterface
{
    private string $cacheDir;
    private static array $processedRequests = [];

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->cacheDir = $projectDir.'/var/cache/rate_limit/';
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($concurrentDirectory = $this->cacheDir, 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ('/api/login_check' === $path && $request->isMethod('POST')) {
            $clientIp = $request->getClientIp();
            $requestId = uniqid('req_', true);
            $request->attributes->set('_rate_limit_id', $requestId);

            $attempts = $this->getAttempts($clientIp);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->logger?->warning('Rate limit exceeded for IP: '.$clientIp.' ('.$attempts.' attempts)');

                $response = new JsonResponse([
                    'code' => 429,
                    'message' => 'Trop de tentatives de connexion. Réessayez dans 15 minutes.',
                ], 429);
                $event->setResponse($response);
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = $request->attributes->get('_rate_limit_id');

        if (!$requestId || '/api/login_check' !== $request->getPathInfo()) {
            return;
        }

        if (isset(self::$processedRequests[$requestId])) {
            return;
        }
        self::$processedRequests[$requestId] = true;

        $clientIp = $request->getClientIp();
        $status = $response->getStatusCode();

        if (200 === $status) {
            $this->resetAttempts($clientIp);
            $this->logger?->info('Successful login for IP: '.$clientIp.' - attempts reset');
        } elseif (401 === $status) {
            $newAttempts = $this->incrementAttempts($clientIp);
            $this->logger?->warning('Failed login attempt for IP: '.$clientIp.' - total attempts: '.$newAttempts);
        }
    }

    private function getAttempts(string $clientIp): int
    {
        $filename = $this->cacheDir.md5($clientIp).'.json';

        if (!file_exists($filename)) {
            return 0;
        }

        $data = file_get_contents($filename);
        if (false === $data) {
            return 0;
        }

        $decoded = json_decode($data, true);
        if (!$decoded || !isset($decoded['attempts'], $decoded['expires'])) {
            return 0;
        }

        if (time() > $decoded['expires']) {
            unlink($filename);

            return 0;
        }

        return (int) $decoded['attempts'];
    }

    private function incrementAttempts(string $clientIp): int
    {
        $attempts = $this->getAttempts($clientIp);
        $newAttempts = $attempts + 1;
        $filename = $this->cacheDir.md5($clientIp).'.json';

        $data = [
            'attempts' => $newAttempts,
            'expires' => time() + self::LOCKOUT_DURATION,
            'ip' => $clientIp,
            'last_attempt' => date('Y-m-d H:i:s'),
        ];

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

        return $newAttempts;
    }

    private function resetAttempts(string $clientIp): void
    {
        $filename = $this->cacheDir.md5($clientIp).'.json';

        if (file_exists($filename)) {
            unlink($filename);
            $this->logger?->info('Reset login attempts for IP: '.$clientIp);
        }
    }
}
