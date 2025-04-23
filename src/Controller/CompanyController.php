<?php

namespace App\Controller;

use App\Entity\Company;
use App\Service\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/company')]
class CompanyController extends AbstractController
{
    private CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    #[Route('/', name: 'company_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $companies = $this->companyService->list();

        return $this->json($companies, 200, [], [
            'groups' => ['company:read']
        ]);
    }

    #[Route('/', name: 'company_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $company = $this->companyService->create($data);

            return $this->json($company, 201, [], [
                'groups' => ['company:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'company_show', methods: ['GET'])]
    public function show(Company $company): JsonResponse
    {
        return $this->json($company, 200, [], [
            'groups' => ['company:read', 'company:details']
        ]);
    }

    #[Route('/{id}', name: 'company_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Company $company): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $company = $this->companyService->update($company, $data);

            return $this->json($company, 200, [], [
                'groups' => ['company:read', 'company:details']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'company_delete', methods: ['DELETE'])]
    public function delete(Company $company): JsonResponse
    {
        $this->companyService->delete($company);

        return $this->json(null, 204);
    }
}
