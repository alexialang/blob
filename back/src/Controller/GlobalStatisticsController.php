<?php

namespace App\Controller;

use App\Entity\Company;
use App\Service\GlobalStatisticsService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/global-statistics')]
class GlobalStatisticsController extends AbstractController
{
    public function __construct(
        private GlobalStatisticsService $globalStatisticsService,
        private CacheInterface $cache,
    ) {
    }

    /**
     * @OA\Get(summary="Obtenir les statistiques d'une entreprise", tags={"Global Statistics"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Statistiques de l'entreprise")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/company/{id}', name: 'get_company_statistics', methods: ['GET'])]
    #[IsGranted('VIEW_RESULTS', subject: 'company')]
    public function getCompanyStatistics(Company $company): JsonResponse
    {
        $cacheKey = "company_statistics_{$company->getId()}";
        $this->cache->delete($cacheKey);

        $data = $this->globalStatisticsService->getCompanyStatistics($company->getId());

        return $this->json($data, 200, [], ['groups' => ['statistics:read']]);
    }
}
