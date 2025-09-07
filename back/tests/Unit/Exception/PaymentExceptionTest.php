<?php

namespace App\Tests\Unit\Exception;

use App\Exception\PaymentException;
use PHPUnit\Framework\TestCase;

class PaymentExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $message = 'Échec du paiement';
        $exception = new PaymentException($message);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Erreur de paiement: $message", $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
    
    public function testExceptionWithPrevious(): void
    {
        $message = 'Carte expirée';
        $previous = new \Exception('Previous exception');
        $exception = new PaymentException($message, $previous);
        
        $this->assertEquals("Erreur de paiement: $message", $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}

