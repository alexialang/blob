<?php

namespace App\Controller;

use App\Entity\TypeQuestion;
use App\Enum\TypeQuestionName;
use App\Service\TypeQuestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    #[Route('/list', name: 'type_question_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $typeQuestions = array_map(function($enum) {
            return [
                'id' => $enum->value,
                'name' => $enum->getName(),
                'key' => $enum->value
            ];
        }, TypeQuestionName::cases());
        
        return $this->json($typeQuestions);
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

}
