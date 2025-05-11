<?php

namespace App\Controller;

use App\Entity\TypeQuestion;
use App\Service\TypeQuestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/type-question')]
class TypeQuestionController extends AbstractController
{
    private TypeQuestionService $typeQuestionService;

    public function __construct(TypeQuestionService $typeQuestionService)
    {
        $this->typeQuestionService = $typeQuestionService;
    }

    /**
     * @OA\Get(summary="Lister les types de question", tags={"TypeQuestion"})
     * @OA\Response(response=200, description="Liste des types de question")
     */
    #[Route('/', name: 'type_question_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $typeQuestionList = $this->typeQuestionService->list();

        return $this->json($typeQuestionList, 200, [], ['groups' => ['question:read']]);
    }

    /**
     * @OA\Post(summary="Créer un type de question", tags={"TypeQuestion"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     * @OA\Response(response=201, description="Type de question créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'type_question_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $typeQuestion = $this->typeQuestionService->create($data);

            return $this->json($typeQuestion, 201, [], ['groups' => ['question:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher un type de question", tags={"TypeQuestion"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails du type de question")
     */
    #[Route('/{id}', name: 'type_question_show', methods: ['GET'])]
    public function show(TypeQuestion $typeQuestion): JsonResponse
    {
        $typeQuestion = $this->typeQuestionService->show($typeQuestion);

        return $this->json($typeQuestion, 200, [], ['groups' => ['question:read']]);
    }

    /**
     * @OA\Put(summary="Modifier un type de question", tags={"TypeQuestion"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Type de question modifié")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'type_question_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, TypeQuestion $typeQuestion): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $typeQuestion = $this->typeQuestionService->update($typeQuestion, $data);

            return $this->json($typeQuestion, 200, [], ['groups' => ['question:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer un type de question", tags={"TypeQuestion"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Type de question supprimé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'type_question_delete', methods: ['DELETE'])]
    public function delete(TypeQuestion $typeQuestion): JsonResponse
    {
        $this->typeQuestionService->delete($typeQuestion);

        return $this->json(null, 204);
    }
}
