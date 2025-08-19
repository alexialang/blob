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
        
        if (empty($this->stripeSecretKey)) {
            throw new \InvalidArgumentException(
                'Clé secrète Stripe non configurée. '
            );
        }
        
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentLink(float $amount, ?string $donorEmail = null, ?string $donorName = null): array
    {
        try {
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Le montant doit être supérieur à 0');
            }
            
            if ($amount > 10000) {
                throw new \InvalidArgumentException('Le montant ne peut pas dépasser 10 000€');
            }

            $product = Product::create([
                'name' => 'Don pour Blob - ' . $amount . '€',
                'description' => 'Soutenez la plateforme Blob',
            ]);

            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => (int)($amount * 100),
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
                    'donor_email' => $donorEmail ?? '',
                    'donor_name' => $donorName ?? '',
                    'amount' => $amount,
                ],
            ]);

            return [
                'payment_url' => $paymentLink->url,
                'payment_link_id' => $paymentLink->id,
            ];
            
        } catch (ApiErrorException $e) {
            error_log('Erreur Stripe API: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('Erreur lors de la création du lien de paiement: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la création du lien de paiement: ' . $e->getMessage());
        }
    }
}
