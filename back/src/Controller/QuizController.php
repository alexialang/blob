<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Service\QuizService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;


#[Route('/api')]
class QuizController extends AbstractController
{
    public function __construct(
        private QuizService $quizService,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/quiz/list', name: 'quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $quizList = $this->quizService->list();
        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    #[Route('/quiz/management/list', name: 'quiz_management_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function managementList(): JsonResponse
    {
        $quizList = $this->quizService->list(true);

        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    #[Route('/quiz/create', name: 'quiz_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $quiz = $this->quizService->createWithQuestions($data, $user);

        return $this->json($quiz, 201, [], ['groups' => ['quiz:read']]);
    }

    #[Route('/quiz/organized', name: 'quiz_organized', methods: ['GET'])]
    public function getOrganizedQuizzes(): JsonResponse
    {
        try {
            $user = $this->getUser();

            $popularQuizzes = $this->quizService->getMostPopularQuizzes(8);

            $recentQuizzes = $this->quizService->getMostRecentQuizzes(6);

            $allQuizzes = $this->quizService->list();
            if ($user) {
                $privateQuizzes = $this->quizService->getPrivateQuizzesForUser($user);
                $allQuizzes = array_merge($allQuizzes, $privateQuizzes);
            }

            $categoriesData = [];

            $publicQuizzes = [];
            $privateQuizzes = [];

            foreach ($allQuizzes as $quiz) {
                if ($quiz->isPublic()) {
                    $publicQuizzes[] = $quiz;
                } else {
                    $privateQuizzes[] = $quiz;
                }
            }

            if (!empty($privateQuizzes) && $user) {
                $userCompany = $user->getCompany();
                $companyName = $userCompany ? $userCompany->getName() : 'Mon Entreprise';
                $categoryName =  $companyName . ' (Quiz Privés)';

                $categoriesData[$categoryName] = [
                    'id' => 'private_company',
                    'name' => $categoryName,
                    'quizzes' => $privateQuizzes
                ];
            }

            foreach ($publicQuizzes as $quiz) {
                $category = $quiz->getCategory();
                if ($category) {
                    $categoryName = $category->getName();
                    if (!isset($categoriesData[$categoryName])) {
                        $categoriesData[$categoryName] = [
                            'id' => $category->getId(),
                            'name' => $categoryName,
                            'quizzes' => []
                        ];
                    }
                    $categoriesData[$categoryName]['quizzes'][] = $quiz;
                }
            }

            $myQuizzes = $user ? $this->quizService->getMyQuizzes($user) : [];

            $result = [
                'popular' => $popularQuizzes,
                'myQuizzes' => $myQuizzes,
                'recent' => $recentQuizzes,
                'categories' => array_values($categoriesData)
            ];

            return $this->json($result, 200, [], ['groups' => ['quiz:read']]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans le contrôleur: ' . $e->getMessage());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'popular' => [],
                'myQuizzes' => [],
                'recent' => [],
                'categories' => []
            ], 500);
        }
    }


    #[Route('/quiz/{id}', name: 'quiz_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Quiz $quiz): JsonResponse
    {
        $quiz = $this->quizService->show($quiz);

        return $this->json($quiz, 200, [], ['groups' => ['quiz:read']]);
    }

    #[Route('/quiz/{id}', name: 'quiz_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $this->getUser();
        if ($quiz->getUser() !== $user && !$user->isAdmin()) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $quiz = $this->quizService->updateWithQuestions($quiz, $data);

            return $this->json($quiz, 200, [], ['groups' => ['quiz:read']]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour du quiz',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz/{id}', name: 'quiz_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Quiz $quiz): JsonResponse
    {
        $user = $this->getUser();
        if ($quiz->getUser() !== $user) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        $this->quizService->delete($quiz);

        return $this->json(null, 204);
    }

    #[Route('/quiz/{id}/average-rating', name: 'quiz_average_rating', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getAverageRating(Quiz $quiz): JsonResponse
    {
        $result = $this->entityManager
            ->createQuery('
                SELECT AVG(qr.rating) as averageRating, COUNT(qr.id) as ratingCount
                FROM App\Entity\QuizRating qr 
                WHERE qr.quiz = :quiz
            ')
            ->setParameter('quiz', $quiz)
            ->getSingleResult();

        return $this->json([
            'averageRating' => round($result['averageRating'] ?? 0, 1),
            'ratingCount' => $result['ratingCount'] ?? 0
        ]);
    }

    #[Route('/quiz/{id}/public-leaderboard', name: 'quiz_public_leaderboard', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getPublicLeaderboard(Quiz $quiz): JsonResponse
    {
        $leaderboard = $this->entityManager
            ->createQuery('
                SELECT u.firstName, u.lastName, u.pseudo, c.name as companyName, 
                       ua.total_score as score, ua.date_attempt
                FROM App\Entity\UserAnswer ua
                JOIN ua.user u
                LEFT JOIN u.company c
                WHERE ua.quiz = :quiz
                ORDER BY ua.total_score DESC, ua.date_attempt ASC
            ')
            ->setParameter('quiz', $quiz)
            ->getResult();

        $userBestScores = [];
        foreach ($leaderboard as $entry) {
            $userId = $entry['firstName'] . ' ' . $entry['lastName'];
            if (!isset($userBestScores[$userId]) || $entry['score'] > $userBestScores[$userId]['score']) {
                $userBestScores[$userId] = $entry;
            }
        }

        uasort($userBestScores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $formattedLeaderboard = [];
        $rank = 1;
        foreach ($userBestScores as $entry) {
            $displayName = $entry['pseudo'] ?? ($entry['firstName'] . ' ' . substr($entry['lastName'], 0, 1) . '.');
            $formattedLeaderboard[] = [
                'rank' => $rank,
                'name' => $displayName,
                'company' => $entry['companyName'] ?? 'Indépendant',
                'score' => (int)$entry['score'],
                'isCurrentUser' => false
            ];
            $rank++;
        }

        return $this->json([
            'leaderboard' => $formattedLeaderboard,
            'totalPlayers' => count($userBestScores)
        ]);
    }
}
