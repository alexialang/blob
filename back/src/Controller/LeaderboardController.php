<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Service\LeaderboardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api')]
class LeaderboardController extends AbstractController
{
    public function __construct(
        private LeaderboardService $leaderboardService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @OA\Get(summary="Classement d'un quiz spécifique", tags={"Leaderboard"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Classement du quiz")
     */
    #[Route('/leaderboard/quiz/{id}', name: 'quiz_leaderboard', methods: ['GET'])]
    public function getQuizLeaderboard(int $id): JsonResponse
    {
        $quiz = $this->entityManager->getRepository(Quiz::class)->find($id);
        if (!$quiz) {
            return new JsonResponse(['error' => 'Quiz non trouvé'], 404);
        }

        $currentUser = $this->getUser();
        $data = $this->leaderboardService->getQuizLeaderboard($quiz, $currentUser);

        return new JsonResponse($data);
    }

    /**
     * @OA\Get(summary="Classement général", tags={"Leaderboard"})
     * @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Classement général")
     */
    #[Route('/leaderboard', name: 'general_leaderboard', methods: ['GET'])]
    public function getGeneralLeaderboard(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);
        $currentUser = $this->getUser();
        
        $data = $this->leaderboardService->getGeneralLeaderboard($limit, $currentUser);

        return new JsonResponse($data);
    }
}
