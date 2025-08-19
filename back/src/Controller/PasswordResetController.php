<?php

namespace App\Controller;

use App\Service\UserPasswordResetService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/api')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserPasswordResetService $resetService,
        ) {}

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $this->resetService->requestPasswordReset($data['email']);

            return $this->json(['message' => 'Si un compte existe, un email a été envoyé.']);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(string $token, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $success = $this->resetService->resetPassword($token, $data['password'], $data['confirmPassword']);

            if (!$success) {
                return $this->json(['error' => 'Lien invalide ou expiré'], 400);
            }

            return $this->json(['message' => 'Mot de passe réinitialisé avec succès']);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}