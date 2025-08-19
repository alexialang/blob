<?php

namespace App\Controller;

use App\Service\GlobalStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/global-statistics')]
#[IsGranted('ROLE_ADMIN')]
class GlobalStatisticsController extends AbstractController
{
    public function __construct(
        private GlobalStatisticsService $globalStatisticsService,
        private CacheInterface $cache
    ) {}

    /**
     * @OA\Get(summary="Obtenir les statistiques globales", tags={"Global Statistics"})
     * @OA\Response(response=200, description="Statistiques globales")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('', name: 'get_global_statistics', methods: ['GET'])]
    public function getGlobalStatistics(): JsonResponse
    {
        return $this->cache->get('global_statistics', function() {
            $data = $this->globalStatisticsService->getGlobalStatistics();
            return $this->json($data);
        });
    }

    /**
     * @OA\Get(summary="Obtenir les statistiques d'une entreprise", tags={"Global Statistics"})
     * @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Statistiques de l'entreprise")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/company/{companyId}', name: 'get_company_statistics', methods: ['GET'])]
    public function getCompanyStatistics(int $companyId): JsonResponse
    {
        $cacheKey = "company_statistics_{$companyId}";
        $this->cache->delete($cacheKey);
        
        $data = $this->globalStatisticsService->getCompanyStatistics($companyId);
        return $this->json($data);
    }
}
