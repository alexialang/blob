<?php

namespace App\Controller;

use App\Service\PaymentService;
use App\Service\InputSanitizerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/donations', name: 'api_donations_')]
class DonationController extends AbstractController
{
    public function __construct(
        private PaymentService $paymentService,
        private InputSanitizerService $inputSanitizer
    ) {}

    #[Route('/create-payment-link', name: 'create_payment_link', methods: ['POST'])]
    public function createPaymentLink(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                error_log('Données JSON invalides reçues dans createPaymentLink');
                return new JsonResponse(['error' => 'Données JSON invalides'], 400);
            }
            
            $sanitizedData = $this->inputSanitizer->sanitizeDonationData($data);
            
            if (!$sanitizedData) {
                error_log('Échec de la sanitisation des données de don');
                return new JsonResponse(['error' => 'Données JSON invalides'], 400);
            }

            if (!isset($sanitizedData['amount']) || $sanitizedData['amount'] <= 0) {
                error_log('Montant invalide reçu: ' . ($sanitizedData['amount'] ?? 'non défini'));
                return new JsonResponse(['error' => 'Montant invalide'], 400);
            }

            if ($sanitizedData['amount'] > 10000) {
                error_log('Montant trop élevé reçu: ' . $sanitizedData['amount']);
                return new JsonResponse(['error' => 'Montant trop élevé'], 400);
            }

            error_log('Tentative de création de lien de paiement pour un don de ' . $sanitizedData['amount'] . '€');

            $result = $this->paymentService->createPaymentLink(
                (float) $sanitizedData['amount'],
                $sanitizedData['donor_email'] ?? null,
                $sanitizedData['donor_name'] ?? null
            );

            error_log('Lien de paiement créé avec succès: ' . $result['payment_link_id']);

            return new JsonResponse($result);
            
        } catch (\InvalidArgumentException $e) {
            error_log('Argument invalide dans createPaymentLink: ' . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Erreur Stripe API dans createPaymentLink: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur de paiement: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            error_log('Erreur inattendue dans createPaymentLink: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la création du paiement'], 500);
        }
    }

}
