<?php

namespace App\Controller;

use App\Entity\CategoryQuiz;
use App\Service\CategoryQuizService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/category-quiz')]
class CategoryQuizController extends AbstractController
{
    public function __construct(
        private CategoryQuizService $categoryQuizService,
    ) {
    }

    /**
     * @OA\Get(summary="Lister les catégories de quiz", tags={"CategoryQuiz"})
     *
     * @OA\Response(response=200, description="Liste des catégories")
     */
    #[Route('', name: 'category_quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryQuizService->list();

        return $this->json($categories, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Get(summary="Afficher une catégorie de quiz", tags={"CategoryQuiz"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Catégorie affichée")
     */
    #[Route('/{id}', name: 'category_quiz_show', methods: ['GET'])]
    public function show(CategoryQuiz $category): JsonResponse
    {
        return $this->json($category, 200, [], ['groups' => ['quiz:read']]);
    }
}
