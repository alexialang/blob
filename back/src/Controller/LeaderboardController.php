<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\User;
use App\Service\LeaderboardService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class LeaderboardController extends AbstractController
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @OA\Get(summary="Classement d'un quiz spécifique", tags={"Leaderboard"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Classement du quiz")
     */
    #[Route('/leaderboard/quiz/{id}', name: 'quiz_leaderboard', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getQuizLeaderboard(int $id): JsonResponse
    {
        if ($id <= 0) {
            return new JsonResponse(['error' => 'L\'ID du quiz doit être positif'], 400);
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($id);
        if (!$quiz) {
            return new JsonResponse(['error' => 'Quiz non trouvé'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $data = $this->leaderboardService->getQuizLeaderboard($quiz, $currentUser);

        return $this->json($data, 200, [], ['groups' => ['leaderboard:read']]);
    }

    /**
     * @OA\Get(summary="Classement général", tags={"Leaderboard"})
     *
     * @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Classement général")
     */
    #[Route('/leaderboard', name: 'general_leaderboard', methods: ['GET'])]
    public function getGeneralLeaderboard(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);

        if ($limit <= 0 || $limit > 1000) {
            return new JsonResponse([
                'error' => 'Paramètre invalide',
                'message' => 'La limite doit être comprise entre 1 et 1000',
            ], 400);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $data = $this->leaderboardService->getGeneralLeaderboard($limit, $currentUser);

        return $this->json($data, 200, [], ['groups' => ['leaderboard:read']]);
    }
}
