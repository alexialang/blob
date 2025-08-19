<?php

namespace App\Controller;

use App\Entity\Badge;
use App\Service\BadgeService;
use App\Service\InputSanitizerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/badge')]
class BadgeController extends AbstractController
{
    public function __construct(
        private BadgeService $badgeService,
        private InputSanitizerService $inputSanitizer
    ) {}

    /**
     * @OA\Get(summary="Liste des badges", tags={"Badge"})
     * @OA\Response(response=200, description="Retourne tous les badges")
     */
    #[Route('/', name: 'badge_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $badges = $this->badgeService->list();

        return $this->json($badges, 200, [], ['groups' => ['badge:read']]);
    }

    /**
     * @OA\Post(summary="Créer un badge", tags={"Badge"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="image", type="string")
     *     )
     * )
     * @OA\Response(response=201, description="Badge créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'badge_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $sanitizedData = $this->inputSanitizer->sanitizeBadgeData($data);

            $badge = $this->badgeService->create($sanitizedData);

            return $this->json($badge, 201, [], ['groups' => ['badge:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher un badge", tags={"Badge"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Badge affiché")
     */
    #[Route('/{id}', name: 'badge_show', methods: ['GET'])]
    public function show(Badge $badge): JsonResponse
    {
        return $this->json($badge, 200, [], ['groups' => ['badge:read']]);
    }

    /**
     * @OA\Put(summary="Modifier un badge", tags={"Badge"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="image", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Badge modifié")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'badge_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Badge $badge): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $sanitizedData = $this->inputSanitizer->sanitizeBadgeData($data);

            $badge = $this->badgeService->update($badge, $sanitizedData);

            return $this->json($badge, 200, [], ['groups' => ['badge:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer un badge", tags={"Badge"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Badge supprimé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'badge_delete', methods: ['DELETE'])]
    public function delete(Badge $badge): JsonResponse
    {
        $this->badgeService->delete($badge);

        return $this->json(null, 204);
    }

    /**
     * @OA\Post(summary="Initialiser les badges par défaut", tags={"Badge"})
     * @OA\Response(response=200, description="Badges initialisés")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/initialize', name: 'badge_initialize', methods: ['POST'])]
    public function initialize(): JsonResponse
    {
        try {
            $this->badgeService->initializeBadges();
            return $this->json(['message' => 'Badges initialisés avec succès'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'initialisation: ' . $e->getMessage()], 500);
        }
    }


}
