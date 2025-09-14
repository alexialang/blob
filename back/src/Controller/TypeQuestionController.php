<?php

namespace App\Controller;

use App\Entity\TypeQuestion;
use App\Repository\TypeQuestionRepository;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/type-question')]
class TypeQuestionController extends AbstractController
{
    public function __construct(
        private TypeQuestionRepository $typeQuestionRepository,
    ) {
    }

    /**
     * @OA\Get(summary="Lister les types de question", tags={"TypeQuestion"})
     *
     * @OA\Response(response=200, description="Liste des types de question")
     */
    #[Route('/list', name: 'type_question_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $typeQuestions = $this->typeQuestionRepository->findAll();

        $result = array_map(fn (TypeQuestion $typeQuestion) => [
            'id' => $typeQuestion->getId(),
            'name' => $typeQuestion->getName(),
            'key' => $typeQuestion->getName(), // Pour compatibilité avec le frontend
        ], $typeQuestions);

        return $this->json($result);
    }

    /**
     * @OA\Get(summary="Afficher un type de question", tags={"TypeQuestion"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Détails du type de question")
     */
    #[Route('/{id}', name: 'type_question_show', methods: ['GET'])]
    public function show(TypeQuestion $typeQuestion): JsonResponse
    {
        return $this->json($typeQuestion, 200, [], ['groups' => ['question:read']]);
    }
}
