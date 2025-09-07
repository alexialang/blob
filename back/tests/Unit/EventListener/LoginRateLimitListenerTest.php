<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\LoginRateLimitListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LoginRateLimitListenerTest extends TestCase
{
    private LoginRateLimitListener $listener;
    private LoggerInterface $logger;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tempDir = sys_get_temp_dir() . '/test_rate_limit_' . uniqid();
        
        $this->listener = new LoginRateLimitListener($this->tempDir, $this->logger);
    }

    protected function tearDown(): void
    {
        // Nettoyer les fichiers de cache créés pendant les tests
        $cacheDir = $this->tempDir . '/var/cache/rate_limit/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($cacheDir);
        }
        if (is_dir($this->tempDir . '/var/cache/')) {
            rmdir($this->tempDir . '/var/cache/');
        }
        if (is_dir($this->tempDir . '/var/')) {
            rmdir($this->tempDir . '/var/');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    // ===== Tests pour getSubscribedEvents() =====
    
    public function testGetSubscribedEvents(): void
    {
        $events = LoginRateLimitListener::getSubscribedEvents();
        
        $this->assertIsArray($events);
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        
        $this->assertEquals(['onKernelRequest', 10], $events[KernelEvents::REQUEST]);
        $this->assertEquals(['onKernelResponse', -10], $events[KernelEvents::RESPONSE]);
    }

    // ===== Tests pour onKernelRequest() =====
    
    public function testOnKernelRequestNotMainRequest(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);
        
        // Ne devrait rien faire pour les sous-requêtes
        $this->listener->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestNotLoginPath(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/users', 'REQUEST_METHOD' => 'GET']);
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        // Ne devrait rien faire pour les autres chemins
        $this->listener->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestNotPostMethod(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/login_check', 'REQUEST_METHOD' => 'GET']);
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        // Ne devrait rien faire pour les méthodes autres que POST
        $this->listener->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestLoginPathFirstAttempt(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/login_check',
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '192.168.1.1'
        ]);
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        // Premier essai, ne devrait pas bloquer
        $this->listener->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestTooManyAttempts(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/login_check',
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '192.168.1.2',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.2'
        ]);
        
        // Simuler 6 tentatives (MAX_ATTEMPTS = 5)
        $cacheDir = $this->tempDir . '/var/cache/rate_limit/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        // Utiliser la même IP que celle retournée par getClientIp()
        $clientIp = $request->getClientIp();
        $clientHash = md5($clientIp);
        $cacheFile = $cacheDir . $clientHash . '.json';
        
        $data = [
            'attempts' => 6,
            'expires' => time() + 900
        ];
        file_put_contents($cacheFile, json_encode($data));
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->logger
            ->expects($this->once())
            ->method('warning');
        
        $this->listener->onKernelRequest($event);
        
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(429, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Trop de tentatives', $responseData['message']);
    }

    // ===== Tests pour onKernelResponse() =====
    
    public function testOnKernelResponseNotMainRequest(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response();
        
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
        
        // Ne devrait rien faire pour les sous-requêtes
        $this->listener->onKernelResponse($event);
        
        // Pas d'assertion spécifique, juste s'assurer qu'aucune exception n'est levée
        $this->assertTrue(true);
    }

    public function testOnKernelResponseNotLoginPath(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/users', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();
        
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        
        // Ne devrait rien faire pour les autres chemins
        $this->listener->onKernelResponse($event);
        
        $this->assertTrue(true);
    }

    public function testOnKernelResponseSuccessfulLogin(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/login_check',
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '192.168.1.3'
        ]);
        
        // Simuler qu'une requête a été traitée (ajout de l'attribut _rate_limit_id)
        $request->attributes->set('_rate_limit_id', 'test_request_id');
        
        $response = new JsonResponse(['token' => 'abc123'], 200);
        
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        
        // Le test vérifie simplement que la méthode ne lève pas d'exception
        $this->listener->onKernelResponse($event);
        
        $this->assertTrue(true);
    }

    public function testOnKernelResponseFailedLogin(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/login_check',
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '192.168.1.4'
        ]);
        
        // Simuler qu'une requête a été traitée (ajout de l'attribut _rate_limit_id)
        $request->attributes->set('_rate_limit_id', 'test_request_id_2');
        
        $response = new JsonResponse(['error' => 'Invalid credentials'], 401);
        
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        
        // Le test vérifie simplement que la méthode ne lève pas d'exception
        $this->listener->onKernelResponse($event);
        
        $this->assertTrue(true);
    }
}
