<?php

namespace App\Controller;

use App\Service\UserPasswordResetService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use OpenApi\Annotations as OA;

#[Route('/api')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserPasswordResetService $resetService,
        private UserService $userService,
        ) {}

    /**
     * @OA\Post(summary="Demander une réinitialisation de mot de passe", tags={"Authentication"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="recaptchaToken", type="string", description="Token CAPTCHA obligatoire")
     *     )
     * )
     * @OA\Response(response=200, description="Email de réinitialisation envoyé")
     */
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['recaptchaToken']) || empty($data['recaptchaToken'])) {
                return $this->json(['error' => 'Token CAPTCHA requis'], 400);
            }
            
            if (!$this->userService->verifyCaptcha($data['recaptchaToken'])) {
                return $this->json(['error' => 'Échec de la vérification CAPTCHA'], 400);
            }

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

    /**
     * @OA\Post(summary="Réinitialiser le mot de passe", tags={"Authentication"})
     * @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="confirmPassword", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Mot de passe réinitialisé")
     */
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