<?php

namespace App\Controller;

use App\Service\UserPasswordResetService;
use App\Service\InputSanitizerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserPasswordResetService $resetService,
        private InputSanitizerService $inputSanitizer
    ) {}

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $sanitizedData = $this->inputSanitizer->sanitizePasswordResetData($data);

        if (empty($sanitizedData['email'])) {
            return $this->json(['error' => 'Email manquant'], 400);
        }

        $this->resetService->requestPasswordReset($sanitizedData['email']);

        return $this->json(['message' => 'Si un compte existe, un email a été envoyé.']);
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sanitizedData = $this->inputSanitizer->sanitizePasswordResetData($data);

        if (empty($sanitizedData['password']) || empty($sanitizedData['confirmPassword'])) {
            return $this->json(['error' => 'Mots de passe manquants'], 400);
        }

        if ($sanitizedData['password'] !== $sanitizedData['confirmPassword']) {
            return $this->json(['error' => 'Les mots de passe ne correspondent pas'], 400);
        }

        $password = $sanitizedData['password'];
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

        $success = $this->resetService->resetPassword($token, $sanitizedData['password'], $sanitizedData['confirmPassword']);

        if (!$success) {
            return $this->json(['error' => 'Lien invalide ou expiré'], 400);
        }

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }
}
