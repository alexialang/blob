<?php

namespace App\Controller;

use App\Entity\Quiz;

use App\Service\QuizRatingService;
use App\Service\QuizSearchService;
use App\Service\QuizCrudService;
use App\Service\LeaderboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Constraints as Assert;
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
        private QuizRatingService $quizRatingService,
        private QuizSearchService $quizSearchService,
        private QuizCrudService $quizCrudService,
        private LeaderboardService $leaderboardService,
        private LoggerInterface $logger,
        ) {}

    /**
     * @OA\Get(summary="Lister tous les quiz", tags={"Quiz"})
     * @OA\Response(response=200, description="Liste des quiz")
     */
    #[Route('/quiz/list', name: 'quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $quizList = $this->quizSearchService->list();
        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Get(summary="Lister les quiz pour la gestion", tags={"Quiz"})
     * @OA\Response(response=200, description="Liste des quiz pour la gestion")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/quiz/management/list', name: 'quiz_management_list', methods: ['GET'])]
    #[IsGranted('CREATE_QUIZ')]
    public function managementList(): JsonResponse
    {
        $user = $this->getUser();
        $quizList = $this->quizSearchService->getQuizzesForCompanyManagement($user);

        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Post(summary="Créer un nouveau quiz", tags={"Quiz"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="category_id", type="integer"),
     *         @OA\Property(property="questions", type="array", @OA\Items(type="object"))
     *     )
     * )
     * @OA\Response(response=201, description="Quiz créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/quiz/create', name: 'quiz_create', methods: ['POST'])]
    #[IsGranted('CREATE_QUIZ')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['title']) || empty(trim($data['title']))) {
                return $this->json(['error' => 'Le titre est obligatoire'], 400);
            }
            
            if (!isset($data['description']) || empty(trim($data['description']))) {
                return $this->json(['error' => 'La description est obligatoire'], 400);
            }
            
            if (!isset($data['category_id']) || !is_numeric($data['category_id']) || $data['category_id'] <= 0) {
                return $this->json(['error' => 'L\'ID de catégorie doit être un nombre positif'], 400);
            }
            
            if (!isset($data['questions']) || !is_array($data['questions']) || empty($data['questions'])) {
                return $this->json(['error' => 'Au moins une question est requise'], 400);
            }

            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            $quiz = $this->quizCrudService->createWithQuestions($data, $user);

            return $this->json($quiz, 201, [], ['groups' => ['quiz:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    #[Route('/quiz/organized', name: 'quiz_organized', methods: ['GET'])]
    public function getOrganizedQuizzes(): JsonResponse
    {
        try {
            $user = null;
            try {
                $user = $this->getUser();
            } catch (\Exception $e) {
                $this->logger->info('getOrganizedQuizzes: utilisateur non connecté', [
                    'error' => $e->getMessage(),
                    'route' => 'quiz_organized'
                ]);
            }

            $popularQuizzes = $this->quizSearchService->getMostPopularQuizzes();
            $recentQuizzes = $this->quizSearchService->getMostRecentQuizzes();
            $allQuizzes = $this->quizSearchService->list();
            
            $privateQuizzes = [];
            if ($user) {
                $privateQuizzes = $this->quizSearchService->getPrivateQuizzesForUser($user);
            }

            $myQuizzes = [];
            if ($user) {
                $myQuizzes = $this->quizSearchService->getMyQuizzes($user);
                $privateQuizzesForUser = $this->quizSearchService->getPrivateQuizzesForUser($user);
                
                $myQuizzesIds = array_map(fn($q) => $q->getId(), $myQuizzes);
                foreach ($privateQuizzesForUser as $privateQuiz) {
                    if (!in_array($privateQuiz->getId(), $myQuizzesIds)) {
                        $myQuizzes[] = $privateQuiz;
                    }
                }
            }

            $result = [
                'popular' => $popularQuizzes,
                'myQuizzes' => $myQuizzes,
                'recent' => $recentQuizzes,
                'categories' => $this->organizeQuizzesByCategory($allQuizzes, $privateQuizzes, $user)
            ];

            return $this->json($result, 200, [], ['groups' => ['quiz:organized']]);
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


    private function organizeQuizzesByCategory(array $publicQuizzes, array $privateQuizzes, ?User $user): array
    {
        $categoriesData = [];

        if (!empty($privateQuizzes) && $user) {
            $userCompany = $user->getCompany();
            $companyName = $userCompany ? $userCompany->getName() : 'Mon Entreprise';
            $categoryName = $companyName . ' (Quiz Privés)';

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

        return array_values($categoriesData);
    }


    #[Route('/quiz/{id}', name: 'quiz_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('CREATE_QUIZ', subject: 'quiz')]
    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            if (isset($data['title']) && empty(trim($data['title']))) {
                return $this->json(['error' => 'Le titre ne peut pas être vide'], 400);
            }
            
            if (isset($data['description']) && empty(trim($data['description']))) {
                return $this->json(['error' => 'La description ne peut pas être vide'], 400);
            }
            
            if (isset($data['category_id']) && (!is_numeric($data['category_id']) || $data['category_id'] <= 0)) {
                return $this->json(['error' => 'L\'ID de catégorie doit être un nombre positif'], 400);
            }
            
            if (isset($data['questions']) && (!is_array($data['questions']) || empty($data['questions']))) {
                return $this->json(['error' => 'Les questions doivent être un tableau non vide'], 400);
            }
            
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $user = $this->getUser();
            $quiz = $this->quizCrudService->updateWithQuestions($quiz, $data, $user);

            return $this->json($quiz, 200, [], ['groups' => ['quiz:read']]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour du quiz',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz/{id}', name: 'quiz_delete', methods: ['DELETE'])]
    #[IsGranted('CREATE_QUIZ', subject: 'quiz')]
    public function delete(Quiz $quiz): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            $this->logger->warning('SECURITY: Suppression de quiz', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'quiz_id' => $quiz->getId(),
                'quiz_title' => $quiz->getTitle(),
                'timestamp' => new \DateTime()
            ]);
            
            $this->quizCrudService->delete($quiz);

            return $this->json(null, 204);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression du quiz',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    #[Route('/quiz/{id}', name: 'quiz_show', methods: ['GET'])]
    #[IsGranted('CREATE_QUIZ', subject: 'quiz')]
    public function show(Quiz $quiz): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            $secureQuiz = $this->quizCrudService->show($quiz, $user);

            return $this->json($secureQuiz, 200, [], ['groups' => ['quiz:read']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération du quiz',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz/{id}/average-rating', name: 'quiz_average_rating', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getAverageRating(Quiz $quiz): JsonResponse
    {
        $result = $this->quizRatingService->getAverageRating($quiz);
        return $this->json($result, 200, [], ['groups' => ['quiz:rating']]);
    }

    #[Route('/quiz/{id}/public-leaderboard', name: 'quiz_public_leaderboard', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getPublicLeaderboard(Quiz $quiz): JsonResponse
    {
        $user = $this->getUser();
        $result = $this->leaderboardService->getQuizLeaderboard($quiz, $user);
        return $this->json($result, 200, [], ['groups' => ['quiz:leaderboard']]);
    }



    #[Route('/quiz/{id}/edit', name: 'quiz_edit_data', methods: ['GET'])]
    #[IsGranted('CREATE_QUIZ', subject: 'quiz')]
    public function getQuizForEdit(Quiz $quiz): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            $quizData = $this->quizCrudService->getQuizForEdit($quiz, $user);
    
            return $this->json($quizData, 200, [], ['groups' => ['quiz:read']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération du quiz',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
