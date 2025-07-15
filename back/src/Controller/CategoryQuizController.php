<?php

namespace App\Controller;

use App\Entity\CategoryQuiz;
use App\Service\CategoryQuizService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/category-quiz')]
class CategoryQuizController extends AbstractController
{
    private CategoryQuizService $categoryQuizService;

    public function __construct(CategoryQuizService $categoryQuizService)
    {
        $this->categoryQuizService = $categoryQuizService;
    }

    /**
     * @OA\Get(summary="Lister les catégories de quiz", tags={"CategoryQuiz"})
     * @OA\Response(response=200, description="Liste des catégories")
     */
    #[Route('', name: 'category_quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryQuizService->list();

        return $this->json($categories, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Post(summary="Créer une catégorie de quiz", tags={"CategoryQuiz"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     * @OA\Response(response=201, description="Catégorie créée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'category_quiz_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $category = $this->categoryQuizService->create($data);

            return $this->json($category, 201, [], ['groups' => ['quiz:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une catégorie de quiz", tags={"CategoryQuiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Catégorie affichée")
     */
    #[Route('/{id}', name: 'category_quiz_show', methods: ['GET'])]
    public function show(CategoryQuiz $category): JsonResponse
    {
        return $this->json($category, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Put(summary="Modifier une catégorie", tags={"CategoryQuiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Catégorie modifiée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'category_quiz_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, CategoryQuiz $category): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $category = $this->categoryQuizService->update($category, $data);

            return $this->json($category, 200, [], ['groups' => ['quiz:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une catégorie de quiz", tags={"CategoryQuiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Catégorie supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'category_quiz_delete', methods: ['DELETE'])]
    public function delete(CategoryQuiz $category): JsonResponse
    {
        $this->categoryQuizService->delete($category);

        return $this->json(null, 204);
    }
}
