<?php

namespace App\Controller;

use App\Entity\Company;
use App\Service\CompanyService;
use App\Service\UserService;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/api')]
class CompanyController extends AbstractSecureController
{
    public function __construct(
        private readonly CompanyService $companyService,
        private readonly UserService $userService,
    ) {
    }

    /**
     * @OA\Get(summary="Lister les entreprises", tags={"Company"})
     *
     * @OA\Response(response=200, description="Liste des entreprises")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/companies', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function list(): JsonResponse
    {
        try {
            $user = $this->getCurrentUser();

            if (!$user->isAdmin()) {
                $userCompany = $user->getCompany();
                if (!$userCompany) {
                    return $this->json([
                        'success' => false,
                    ], 403);
                }

                $companies = [$userCompany];
            } else {
                $companies = $this->companyService->list();
            }

            return $this->json([
                'success' => true,
                'data' => $companies,
            ], 200, [], ['groups' => ['company:detail']]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entreprises: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(summary="Afficher une entreprise", tags={"Company"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Détails de l'entreprise")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/companies/{id}', methods: ['GET'])]
    #[IsGranted('VIEW_RESULTS', subject: 'company')]
    public function show(Company $company): JsonResponse
    {
        try {
            return $this->json([
                'success' => true,
                'data' => $company,
            ], 200, [], ['groups' => ['company:detail']]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'entreprise: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/{id}/basic', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function showBasic(Company $company): JsonResponse
    {
        try {
            return $this->json([
                'success' => true,
                'data' => $company,
            ], 200, [], ['groups' => ['company:detail']]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de base: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/{id}/groups', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function getCompanyGroups(Company $company): JsonResponse
    {
        try {
            $groups = $this->companyService->getCompanyGroups($company);

            return $this->json([
                'success' => true,
                'data' => $groups,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des groupes: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(summary="Créer une entreprise", tags={"Company"})
     *
     * @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *
     *         @OA\Property(property="name", type="string")
     *     )
     * )
     *
     * @OA\Response(response=201, description="Entreprise créée")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/companies', methods: ['POST'])]
    #[IsGranted('MANAGE_USERS')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['name']) || empty(trim((string) $data['name']))) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le nom de l\'entreprise est obligatoire',
                ], 400);
            }

            if (strlen(trim((string) $data['name'])) < 2) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le nom de l\'entreprise doit contenir au moins 2 caractères',
                ], 400);
            }

            $company = $this->companyService->create($data);

            return $this->json([
                'success' => true,
                'message' => 'Entreprise créée avec succès',
                'data' => $company,
            ], 201, [], ['groups' => ['company:create']]);
        } catch (\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'Format JSON invalide',
            ], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => 'Données invalides',
                'details' => $errorMessages,
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entreprise: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une entreprise", tags={"Company"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=204, description="Entreprise supprimée")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/companies/{id}', name: 'company_delete', methods: ['DELETE'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function delete(Company $company): JsonResponse
    {
        try {
            $this->companyService->delete($company);

            return $this->json([
                'success' => true,
                'message' => 'Entreprise supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'entreprise: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/export/csv', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function exportCsv(): Response
    {
        try {
            $csv = $this->companyService->exportCompaniesToCsv();

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="entreprises.csv"');

            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export CSV: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/export/json', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function exportJson(): JsonResponse
    {
        try {
            $json = $this->companyService->exportCompaniesToJson();

            return $this->json([
                'success' => true,
                'data' => json_decode($json, true),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export JSON: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/import/csv', methods: ['POST'])]
    #[IsGranted('MANAGE_USERS')]
    public function importCsv(Request $request): JsonResponse
    {
        try {
            $file = $request->files->get('file');

            if (!$file) {
                return $this->json([
                    'success' => false,
                    'message' => 'Aucun fichier fourni',
                ], 400);
            }

            if ('text/csv' !== $file->getClientMimeType()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le fichier doit être au format CSV',
                ], 400);
            }

            $results = $this->companyService->importCompaniesFromCsv($file);

            return $this->json([
                'success' => true,
                'message' => 'Import terminé',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import CSV: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/{id}/stats', methods: ['GET'])]
    #[IsGranted('VIEW_RESULTS', subject: 'company')]
    public function stats(Company $company): JsonResponse
    {
        try {
            $stats = $this->companyService->getCompanyStats($company);

            return $this->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/{id}/assign-user', methods: ['POST'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function assignUserToCompany(Company $company, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['userId']) || !is_numeric($data['userId']) || $data['userId'] <= 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'ID utilisateur invalide',
                ], 400);
            }

            $userId = (int) $data['userId'];
            $roles = $data['roles'] ?? ['ROLE_USER'];
            $permissions = $data['permissions'] ?? [];

            if (!is_array($roles)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Les rôles doivent être un tableau',
                ], 400);
            }

            if (!is_array($permissions)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Les permissions doivent être un tableau',
                ], 400);
            }

            $result = $this->companyService->assignUserToCompany($company, $userId, $roles, $permissions);

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur assigné avec succès',
                'data' => $result,
            ]);
        } catch (\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'Format JSON invalide',
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/{id}/available-users', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function getAvailableUsers(Company $company): JsonResponse
    {
        try {
            $currentUser = $this->getCurrentUser();

            $availableUsers = $this->userService->getUsersWithoutCompany();

            if ($currentUser->isAdmin()) {
                $usersFromOtherCompanies = $this->userService->getUsersFromOtherCompanies($company->getId());
                $availableUsers = array_merge($availableUsers, $usersFromOtherCompanies);
            }

            return $this->json([
                'success' => true,
                'data' => $availableUsers,
            ], 200, [], ['groups' => ['company:available_users']]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs disponibles: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/companies/{id}/users', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function getCompanyUsers(Company $company): JsonResponse
    {
        try {
            $users = $company->getUsers()->toArray();

            return $this->json([
                'success' => true,
                'data' => $users,
            ], 200, [], ['groups' => ['company:detail']]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs: '.$e->getMessage(),
            ], 500);
        }
    }
}
