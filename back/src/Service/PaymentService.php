<?php

namespace App\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentLink;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class PaymentService
{
    private string $stripeSecretKey;

    public function __construct(string $stripeSecretKey)
    {
        $this->stripeSecretKey = $stripeSecretKey;
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentLink(float $amount, ?string $donorEmail = null, ?string $donorName = null): array
    {
        $product = Product::create([
            'name' => 'Don pour Blob - ' . $amount . '€',
            'description' => 'Soutenez la plateforme Blob',
        ]);

        $price = Price::create([
            'product' => $product->id,
            'unit_amount' => $amount * 100,
            'currency' => 'eur',
        ]);

        $paymentLink = PaymentLink::create([
            'line_items' => [
                [
                    'price' => $price->id,
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'type' => 'donation',
                'donor_email' => $donorEmail,
                'donor_name' => $donorName,
                'amount' => $amount,
            ],
        ]);


        return [
            'payment_url' => $paymentLink->url,
            'payment_link_id' => $paymentLink->id,
        ];
    }

}
