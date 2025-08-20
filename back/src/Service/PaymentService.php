<?php

namespace App\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentLink;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class PaymentService
{
    private string $stripeSecretKey;
    private ValidatorInterface $validator;

    public function __construct(string $stripeSecretKey, ValidatorInterface $validator)
    {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->validator = $validator;
        
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
        $this->validateDonationData(['amount' => $amount, 'donor_email' => $donorEmail, 'donor_name' => $donorName]);
        
        try {

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

    private function validateDonationData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'amount' => [
                    new Assert\NotBlank(['message' => 'Le montant est requis']),
                    new Assert\Type(['type' => 'numeric', 'message' => 'Le montant doit être un nombre']),
                    new Assert\Range(['min' => 0.01, 'max' => 10000, 'notInRangeMessage' => 'Le montant doit être entre 0.01€ et 10000€'])
                ],
                'donor_email' => [
                    new Assert\Optional([
                        new Assert\Email(['message' => 'L\'email du donateur n\'est pas valide']),
                        new Assert\Length(['max' => 180, 'maxMessage' => 'L\'email ne peut pas dépasser 180 caractères'])
                    ])
                ],
                'donor_name' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 100, 'maxMessage' => 'Le nom du donateur ne peut pas dépasser 100 caractères'])
                    ])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
