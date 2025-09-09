<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\EventListener\JWTCreatedListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use PHPUnit\Framework\TestCase;

class JWTCreatedListenerTest extends TestCase
{
    private JWTCreatedListener $listener;

    protected function setUp(): void
    {
        $this->listener = new JWTCreatedListener();
    }

    public function testOnJWTCreatedWithUserInstance(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        $user->method('getPseudo')->willReturn('testuser');
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');

        $event = $this->createMock(JWTCreatedEvent::class);
        $event->method('getUser')->willReturn($user);
        $event->method('getData')->willReturn(['exp' => 1234567890]);

        $expectedData = [
            'exp' => 1234567890,
            'userId' => 123,
            'pseudo' => 'testuser',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->listener->onJWTCreated($event);
    }

    public function testOnJWTCreatedWithNonUserInstance(): void
    {
        $event = $this->createMock(JWTCreatedEvent::class);
        $user = $this->createMock(\App\Entity\User::class);
        $event->method('getUser')->willReturn($user);

        $event->expects($this->never())
            ->method('setData');

        $this->listener->onJWTCreated($event);
    }
}
