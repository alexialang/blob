<?php

namespace App\Controller;

use App\Entity\Company;
use App\Service\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/company')]
class CompanyController extends AbstractController
{
    private CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * @OA\Get(summary="Lister toutes les entreprises", tags={"Company"})
     * @OA\Response(response=200, description="Liste des entreprises")
     */
    #[Route('/', name: 'company_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $companies = $this->companyService->list();

        return $this->json($companies, 200, [], ['groups' => ['company:read']]);
    }

    /**
     * @OA\Post(summary="Créer une entreprise", tags={"Company"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     * @OA\Response(response=201, description="Entreprise créée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'company_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $company = $this->companyService->create($data);

            return $this->json($company, 201, [], ['groups' => ['company:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une entreprise", tags={"Company"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails de l'entreprise")
     */
    #[Route('/{id}', name: 'company_show', methods: ['GET'])]
    public function show(Company $company): JsonResponse
    {
        return $this->json($company, 200, [], ['groups' => ['company:read', 'company:details']]);
    }

    /**
     * @OA\Put(summary="Modifier une entreprise", tags={"Company"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Entreprise modifiée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'company_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Company $company): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $company = $this->companyService->update($company, $data);

            return $this->json($company, 200, [], ['groups' => ['company:read', 'company:details']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une entreprise", tags={"Company"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Entreprise supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'company_delete', methods: ['DELETE'])]
    public function delete(Company $company): JsonResponse
    {
        $this->companyService->delete($company);

        return $this->json(null, 204);
    }
}
