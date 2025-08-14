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

        if ($data['password'] !== $data['confirmPassword']) {
            return $this->json(['error' => 'Les mots de passe ne correspondent pas'], 400);
        }

        $password = $data['password'];
        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins une majuscule'], 400);
        }

        if (!preg_match('/[a-z]/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins une minuscule'], 400);
        }

        if (!preg_match('/\d/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins un chiffre'], 400);
        }

        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins un caractère spécial'], 400);
        }

        $success = $this->resetService->resetPassword($token, $data['password'], $data['confirmPassword']);

        if (!$success) {
            return $this->json(['error' => 'Lien invalide ou expiré'], 400);
        }

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }
}
