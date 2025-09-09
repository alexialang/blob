<?php

namespace App\Tests\Unit\Service;

use App\Service\PaymentService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentServiceTest extends TestCase
{
    public function testServiceInstantiationWithValidKey(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $stripeKey = 'sk_test_valid_key';
        $frontendUrl = 'https://example.com';

        $service = new PaymentService($stripeKey, $validator, $frontendUrl);

        $this->assertInstanceOf(PaymentService::class, $service);
    }

    public function testServiceInstantiationWithEmptyKey(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $stripeKey = '';
        $frontendUrl = 'https://example.com';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Clé secrète Stripe non configurée. ');

        new PaymentService($stripeKey, $validator, $frontendUrl);
    }

    public function testValidateDonationDataMethod(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $stripeKey = 'sk_test_valid_key';
        $frontendUrl = 'https://example.com';

        $service = new PaymentService($stripeKey, $validator, $frontendUrl);

        // Test avec reflection pour la méthode privée
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('validateDonationData'));

        $method = $reflection->getMethod('validateDonationData');
        $method->setAccessible(true);

        // Test basique - ne devrait pas lever d'exception avec des données nulles
        $validator->method('validate')->willReturn(new \Symfony\Component\Validator\ConstraintViolationList());

        $method->invoke($service, ['amount' => 10.0, 'donor_email' => null, 'donor_name' => null]);
        $this->assertTrue(true); // Si on arrive ici, pas d'exception
    }

    public function testClassProperties(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $stripeKey = 'sk_test_valid_key';
        $frontendUrl = 'https://example.com';

        $service = new PaymentService($stripeKey, $validator, $frontendUrl);
        $reflection = new \ReflectionClass($service);

        $this->assertTrue($reflection->hasProperty('stripeSecretKey'));
        $this->assertTrue($reflection->hasProperty('validator'));
        $this->assertTrue($reflection->hasProperty('frontendUrl'));
    }

    public function testClassMethods(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $stripeKey = 'sk_test_valid_key';
        $frontendUrl = 'https://example.com';

        $service = new PaymentService($stripeKey, $validator, $frontendUrl);
        $reflection = new \ReflectionClass($service);

        $this->assertTrue($reflection->hasMethod('createPaymentLink'));
        $this->assertTrue($reflection->hasMethod('validateDonationData'));
    }

    public function testConstructorParametersValidation(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);

        // Test avec différents types de clés
        $validKeys = ['sk_test_123', 'sk_live_456', 'rk_test_789'];
        $frontendUrl = 'https://example.com';

        foreach ($validKeys as $key) {
            $service = new PaymentService($key, $validator, $frontendUrl);
            $this->assertInstanceOf(PaymentService::class, $service);
        }
    }
}
