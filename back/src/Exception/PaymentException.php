<?php

namespace App\Exception;

class PaymentException extends \Exception
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct("Erreur de paiement: $message", 500, $previous);
    }
}
