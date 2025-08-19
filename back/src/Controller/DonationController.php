<?php

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/api/donations', name: 'api_donations_')]
class DonationController extends AbstractController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    #[Route('/create-payment-link', name: 'create_payment_link', methods: ['POST'])]
    public function createPaymentLink(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                error_log('Données JSON invalides reçues dans createPaymentLink');
                return new JsonResponse(['error' => 'Données JSON invalides'], 400);
            }

            error_log('Tentative de création de lien de paiement pour un don de ' . $data['amount'] . '€');

            $result = $this->paymentService->createPaymentLink(
                (float) $data['amount'],
                $data['donor_email'] ?? null,
                $data['donor_name'] ?? null
            );

            error_log('Lien de paiement créé avec succès: ' . $result['payment_link_id']);

            return new JsonResponse($result);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return new JsonResponse(['error' => 'Données invalides', 'details' => $errorMessages], 400);
            
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
