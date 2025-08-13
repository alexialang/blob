<?php

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/donations', name: 'api_donations_')]
class DonationController extends AbstractController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    #[Route('/create-payment-link', name: 'create_payment_link', methods: ['POST'])]
    public function createPaymentLink(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        

        
        if (!$data) {
            return new JsonResponse(['error' => 'Données JSON invalides'], 400);
        }

        if (!isset($data['amount']) || $data['amount'] <= 0) {
            return new JsonResponse(['error' => 'Montant invalide'], 400);
        }

        if ($data['amount'] > 10000) {
            return new JsonResponse(['error' => 'Montant trop élevé'], 400);
        }

        try {
            $result = $this->paymentService->createPaymentLink(
                (float) $data['amount'],
                $data['donor_email'] ?? null,
                $data['donor_name'] ?? null
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du paiement'], 500);
        }
    }

}
