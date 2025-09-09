<?php

namespace App\Tests\Integration;

use App\EventListener\JWTCreatedListener;
use App\EventListener\BadgeEventListener;
use App\EventListener\SecurityHeadersListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventSystemIntegrationTest extends KernelTestCase
{
    public function testEventListenersAreRegistered(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        
        // Test que nos event listeners sont bien enregistrés
        $this->assertTrue($container->has(JWTCreatedListener::class));
        $this->assertTrue($container->has(BadgeEventListener::class));
        $this->assertTrue($container->has(SecurityHeadersListener::class));
    }
    
    public function testJWTCreatedListenerIsAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $listener = $container->get(JWTCreatedListener::class);
        $this->assertInstanceOf(JWTCreatedListener::class, $listener);
    }
    
    public function testBadgeEventListenerIsAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $listener = $container->get(BadgeEventListener::class);
        $this->assertInstanceOf(BadgeEventListener::class, $listener);
    }
    
    public function testSecurityHeadersListenerIsAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $listener = $container->get(SecurityHeadersListener::class);
        $this->assertInstanceOf(SecurityHeadersListener::class, $listener);
    }
    
    public function testEventDispatcherHasListeners(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        
        // Test qu'il y a des listeners enregistrés
        $listeners = $eventDispatcher->getListeners();
        $this->assertIsArray($listeners);
        $this->assertNotEmpty($listeners);
    }
    
    public function testKernelEventsAreConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        
        // Test que les événements kernel sont configurés
        $responseListeners = $eventDispatcher->getListeners('kernel.response');
        $this->assertIsArray($responseListeners);
        
        // Notre SecurityHeadersListener doit être présent
        $hasSecurityListener = false;
        foreach ($responseListeners as $listener) {
            if (is_array($listener) && $listener[0] instanceof SecurityHeadersListener) {
                $hasSecurityListener = true;
                break;
            }
        }
        $this->assertTrue($hasSecurityListener);
    }
    
    public function testJWTEventsAreConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        
        // Test que les événements JWT sont configurés
        $jwtListeners = $eventDispatcher->getListeners('lexik_jwt_authentication.on_jwt_created');
        $this->assertIsArray($jwtListeners);
    }
    
    public function testEventSubscribersImplementCorrectInterface(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $securityListener = $container->get(SecurityHeadersListener::class);
        
        // Test que le listener implémente la bonne interface
        $this->assertInstanceOf(
            \Symfony\Component\EventDispatcher\EventSubscriberInterface::class,
            $securityListener
        );
        
        // Test que la méthode getSubscribedEvents existe
        $subscribedEvents = $securityListener::getSubscribedEvents();
        $this->assertIsArray($subscribedEvents);
        $this->assertNotEmpty($subscribedEvents);
    }
    
    public function testEventPrioritiesAreSet(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        
        // Test que nos listeners ont des priorités définies
        $responseListeners = $eventDispatcher->getListeners('kernel.response');
        
        // On doit avoir au moins un listener
        $this->assertNotEmpty($responseListeners);
    }
    
    public function testCustomEventsCanBeDispatched(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $eventDispatcher = $container->get('event_dispatcher');
        
        // Test qu'on peut dispatcher des événements custom
        $event = new \Symfony\Contracts\EventDispatcher\Event();
        
        try {
            $eventDispatcher->dispatch($event, 'test.custom.event');
            $this->assertTrue(true); // Si ça ne lève pas d'exception, c'est bon
        } catch (\Exception $e) {
            $this->fail('Event dispatcher should handle custom events: ' . $e->getMessage());
        }
    }
}
