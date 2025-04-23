<?php

namespace App\Controller;

use App\Entity\Badge;
use App\Service\BadgeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/badge')]
class BadgeController extends AbstractController
{
    private BadgeService $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    #[Route('/', name: 'badge_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $badges = $this->badgeService->list();

        return $this->json($badges, 200, [], [
            'groups' => ['badge:read']
        ]);
    }

    #[Route('/', name: 'badge_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $badge = $this->badgeService->create($data);

            return $this->json($badge, 201, [], [
                'groups' => ['badge:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'badge_show', methods: ['GET'])]
    public function show(Badge $badge): JsonResponse
    {
        return $this->json($badge, 200, [], [
            'groups' => ['badge:read']
        ]);
    }

    #[Route('/{id}', name: 'badge_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Badge $badge): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $badge = $this->badgeService->update($badge, $data);

            return $this->json($badge, 200, [], [
                'groups' => ['badge:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'badge_delete', methods: ['DELETE'])]
    public function delete(Badge $badge): JsonResponse
    {
        $this->badgeService->delete($badge);

        return $this->json(null, 204);
    }
}
