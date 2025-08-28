<?php

namespace App\Controller;

use App\Service\PaymentService;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use OpenApi\Annotations as OA;

#[Route('/api/donations', name: 'api_donations_')]
class DonationController extends AbstractController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @OA\Post(summary="Créer un lien de paiement pour donation", tags={"Donation"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="amount", type="number"),
     *         @OA\Property(property="donor_email", type="string"),
     *         @OA\Property(property="donor_name", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Lien de paiement créé")
     */
    #[Route('/create-payment-link', name: 'create_payment_link', methods: ['POST'])]
    public function createPaymentLink(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Données JSON invalides'], 400);
            }

            $result = $this->paymentService->createPaymentLink(
                (float) $data['amount'],
                $data['donor_email'] ?? null,
                $data['donor_name'] ?? null
            );

            return $this->json($result);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => 'Erreur de paiement: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création du paiement'], 500);
        }
    }

}
