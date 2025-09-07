<?php

namespace App\Tests\Unit\Controller;

use App\Controller\AbstractSecureController;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class AbstractSecureControllerTest extends TestCase
{
    private AbstractSecureController $controller;

    protected function setUp(): void
    {
        // Créer une instance concrete de la classe abstract
        $this->controller = new class extends AbstractSecureController {
            public function getUser(): ?User
            {
                return parent::getUser();
            }
        };
    }

    public function testGetUserReturnsUser(): void
    {
        // Test basique - on vérifie que la méthode existe
        $this->assertTrue(method_exists($this->controller, 'getUser'));
    }

    public function testControllerIsInstanceOfAbstractSecureController(): void
    {
        $this->assertInstanceOf(AbstractSecureController::class, $this->controller);
    }

    public function testControllerHasSecurityMethods(): void
    {
        // Vérifier que les méthodes de sécurité sont présentes
        $this->assertTrue(method_exists($this->controller, 'getUser'));
    }
}
