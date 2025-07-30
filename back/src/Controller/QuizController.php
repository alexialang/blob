<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Service\QuizService;
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
        private LoggerInterface $logger
    ) {}

    /**
     * @OA\Get(summary="Liste des quiz", tags={"Quiz"})
     * @OA\Response(response=200, description="Liste des quiz")
     */
    #[Route('/quiz/list', name: 'quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $quizList = $this->quizService->list();
        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Get(summary="Liste des quiz pour la gestion", tags={"Quiz"})
     * @OA\Response(response=200, description="Liste de tous les quiz pour la gestion")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/quiz/management/list', name: 'quiz_management_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function managementList(): JsonResponse
    {
        $quizList = $this->quizService->list(true);

        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Post(summary="Créer un quiz avec questions", tags={"Quiz"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="category", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Quiz créé")
     * @OA\Security(name="bearerAuth")
     */
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

    /**
     * @OA\Get(summary="Récupérer les quiz organisés par catégories", tags={"Quiz"})
     * @OA\Response(response=200, description="Quiz organisés par catégories")
     */
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


    /**
     * @OA\Get(summary="Afficher un quiz par ID", tags={"Quiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails du quiz")
     */
    #[Route('/quiz/{id}', name: 'quiz_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Quiz $quiz): JsonResponse
    {
        $quiz = $this->quizService->show($quiz);

        return $this->json($quiz, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Put(summary="Modifier un quiz", tags={"Quiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="category", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Quiz modifié")
     * @OA\Security(name="bearerAuth")
     */
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

    /**
     * @OA\Delete(summary="Supprimer un quiz", tags={"Quiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Quiz supprimé")
     * @OA\Security(name="bearerAuth")
     */
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
}
