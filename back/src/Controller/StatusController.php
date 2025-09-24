<?php

namespace App\Controller;

use App\Enum\Status;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/status')]
class StatusController extends AbstractController
{
    /**
     * @OA\Get(summary="Lister les statuts disponibles", tags={"Status"})
     *
     * @OA\Response(response=200, description="Liste des statuts")
     */
    #[Route('/list', name: 'status_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $modernStatus = [
            Status::DRAFT,
            Status::PUBLISHED,
            Status::ARCHIVED,
        ];

        $statuses = array_map(fn ($enum) => [
            'id' => array_search($enum, $modernStatus) + 1,
            'name' => $enum->getName(),
            'value' => $enum->value,
        ], $modernStatus);

        return $this->json($statuses, 200);
    }
}
