<?php

namespace App\Controller;

use App\Service\UserPasswordResetService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserPasswordResetService $resetService
    ) {}

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['error' => 'Email manquant'], 400);
        }

        $this->resetService->requestPasswordReset($data['email']);

        return $this->json(['message' => 'Si un compte existe, un email a été envoyé.']);
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['password']) || empty($data['confirmPassword'])) {
            return $this->json(['error' => 'Mots de passe manquants'], 400);
        }

        $success = $this->resetService->resetPassword($token, $data['password'], $data['confirmPassword']);

        if (!$success) {
            return $this->json(['error' => 'Lien invalide, expiré ou mots de passe différents'], 400);
        }

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }
}
