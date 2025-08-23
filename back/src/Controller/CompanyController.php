<?php

namespace App\Controller;

use App\Entity\Company;
use App\Service\CompanyService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class CompanyController extends AbstractController
{
    public function __construct(
        private CompanyService $companyService,
        private UserService $userService,
        ) {}

    #[Route('/companies', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function list(): JsonResponse
    {
        try {
            $user = $this->getUser();

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
            
            $data = [];
            foreach ($companies as $company) {
                $users = [];
                foreach ($company->getUsers() as $user) {
                    $users[] = [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'pseudo' => $user->getPseudo(),
                        'avatar' => $user->getAvatar(),
                        'roles' => $user->getRoles(),
                        'isActive' => $user->isActive(),
                        'isVerified' => $user->isVerified(),
                        'lastAccess' => $user->getLastAccess() ? $user->getLastAccess()->format('Y-m-d H:i:s') : null,
                        'dateRegistration' => $user->getDateRegistration() ? $user->getDateRegistration()->format('Y-m-d H:i:s') : null
                    ];
                }
                
                $groups = [];
                foreach ($company->getGroups() as $group) {
                    $groups[] = [
                        'id' => $group->getId(),
                        'name' => $group->getName(),
                        'accesCode' => $group->getAccesCode(),
                        'userCount' => $group->getUsers()->count()
                    ];
                }
                
                $activeUsersCount = 0;
                $lastActivity = null;
                $thirtyDaysAgo = new \DateTime('-30 days');
                
                foreach ($company->getUsers() as $user) {
                    if ($user->getLastAccess() && $user->getLastAccess() > $thirtyDaysAgo && $user->isActive()) {
                        $activeUsersCount++;
                    }
                    
                    if ($user->getLastAccess() && (!$lastActivity || $user->getLastAccess() > $lastActivity)) {
                        $lastActivity = $user->getLastAccess();
                    }
                }

                $companyData = [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                    'users' => $users,
                    'groups' => $groups,
                    'userCount' => $company->getUsers()->count(),
                    'activeUsers' => $activeUsersCount,
                    'groupCount' => $company->getGroups()->count(),
                    'quizCount' => $company->getQuizs()->count(),
                    'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null,
                    'lastActivity' => $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : null
                ];
                
                $data[] = $companyData;
            }
            
            return $this->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entreprises: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/companies/{id}', methods: ['GET'])]
    #[IsGranted('VIEW_RESULTS', subject: 'company')]
    public function show(Company $company): JsonResponse
    {
        try {
            $data = [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'userCount' => $company->getUsers()->count(),
                'groupCount' => $company->getGroups()->count(),
                'quizCount' => $company->getQuizs()->count(),
                'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null,
                'users' => [],
                'groups' => []
            ];

            foreach ($company->getUsers() as $user) {
                $data['users'][] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'pseudo' => $user->getPseudo(),
                    'avatar' => $user->getAvatar(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'isVerified' => $user->isVerified(),
                    'lastAccess' => $user->getLastAccess() ? $user->getLastAccess()->format('Y-m-d H:i:s') : null,
                    'dateRegistration' => $user->getDateRegistration() ? $user->getDateRegistration()->format('Y-m-d H:i:s') : null
                ];
            }

            foreach ($company->getGroups() as $group) {
                $groupUsers = [];
                foreach ($group->getUsers() as $user) {
                    $groupUsers[] = [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'pseudo' => $user->getPseudo()
                    ];
                }
                
                $data['groups'][] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'accesCode' => $group->getAccesCode(),
                    'userCount' => $group->getUsers()->count(),
                    'users' => $groupUsers
                ];
            }

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'entreprise: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/companies/{id}/basic', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function showBasic(Company $company): JsonResponse
    {
        try {
            $data = [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'userCount' => $company->getUsers()->count(),
                'groupCount' => $company->getGroups()->count(),
                'quizCount' => $company->getQuizs()->count(),
                'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null,
                'users' => [],
                'groups' => []
            ];

            foreach ($company->getUsers() as $user) {
                $data['users'][] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'pseudo' => $user->getPseudo(),
                    'avatar' => $user->getAvatar(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'isVerified' => $user->isVerified(),
                    'lastAccess' => $user->getLastAccess() ? $user->getLastAccess()->format('Y-m-d H:i:s') : null,
                    'dateRegistration' => $user->getDateRegistration() ? $user->getDateRegistration()->format('Y-m-d H:i:s') : null
                ];
            }

            foreach ($company->getGroups() as $group) {
                $groupUsers = [];
                foreach ($group->getUsers() as $user) {
                    $groupUsers[] = [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'pseudo' => $user->getPseudo()
                    ];
                }
                
                $data['groups'][] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'accesCode' => $group->getAccesCode(),
                    'userCount' => $group->getUsers()->count(),
                    'users' => $groupUsers
                ];
            }

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de base: ' . $e->getMessage()
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
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des groupes: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/companies', methods: ['POST'])]
    #[IsGranted('MANAGE_USERS')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $company = $this->companyService->create($data);
            
            return $this->json([
                'success' => true,
                'message' => 'Entreprise créée avec succès',
                'data' => [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                    'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null
                ]
            ], 201);

        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Données invalides',
                'details' => $errorMessages
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entreprise: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Delete(summary="Supprimer une entreprise", tags={"Company"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Entreprise supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/companies/{id}', name: 'company_delete', methods: ['DELETE'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function delete(Company $company): JsonResponse
    {
        try {
            error_log("Tentative de suppression de l'entreprise ID: " . $company->getId() . ", Nom: " . $company->getName());
            
            $this->companyService->delete($company);
            
            error_log("Entreprise supprimée avec succès: " . $company->getName());
            
            return $this->json([
                'success' => true,
                'message' => 'Entreprise supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression de l'entreprise: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'entreprise: ' . $e->getMessage()
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
                'message' => 'Erreur lors de l\'export CSV: ' . $e->getMessage()
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
                'data' => json_decode($json, true)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export JSON: ' . $e->getMessage()
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
                    'message' => 'Aucun fichier fourni'
                ], 400);
            }

            if ($file->getClientMimeType() !== 'text/csv') {
                return $this->json([
                    'success' => false,
                    'message' => 'Le fichier doit être au format CSV'
                ], 400);
            }

            $results = $this->companyService->importCompaniesFromCsv($file);
            
            return $this->json([
                'success' => true,
                'message' => 'Import terminé',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import CSV: ' . $e->getMessage()
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
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }
    #[Route('/companies/{id}/assign-user', methods: ['POST'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function assignUserToCompany(Company $company, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['userId'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'ID utilisateur requis'
                ], 400);
            }
            
            $userId = $data['userId'];
            $roles = $data['roles'] ?? ['ROLE_USER'];
            $permissions = $data['permissions'] ?? [];
            
            $result = $this->companyService->assignUserToCompany($company, $userId, $roles, $permissions);
            
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur assigné avec succès',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/companies/{id}/available-users', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function getAvailableUsers(Company $company): JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            
            $availableUsers = $this->userService->getUsersWithoutCompany();
            
            if ($currentUser->isAdmin()) {
                $usersFromOtherCompanies = $this->userService->getUsersFromOtherCompanies($company->getId());
                $availableUsers = array_merge($availableUsers, $usersFromOtherCompanies);
            }
            
            $formattedUsers = [];
            foreach ($availableUsers as $user) {
                $formattedUsers[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'pseudo' => $user->getPseudo(),
                    'isVerified' => $user->isVerified(),
                    'currentCompany' => $user->getCompany() ? [
                        'id' => $user->getCompany()->getId(),
                        'name' => $user->getCompany()->getName()
                    ] : null,
                    'roles' => $user->getRoles()
                ];
            }
            
            return $this->json([
                'success' => true,
                'data' => $formattedUsers
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs disponibles: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/companies/{id}/users', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS', subject: 'company')]
    public function getCompanyUsers(Company $company): JsonResponse
    {
        try {
            $users = $company->getUsers()->toArray();
            
            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'pseudo' => $user->getPseudo(),
                    'avatar' => $user->getAvatar(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'isVerified' => $user->isVerified(),
                    'lastAccess' => $user->getLastAccess() ? $user->getLastAccess()->format('Y-m-d H:i:s') : null,
                    'dateRegistration' => $user->getDateRegistration() ? $user->getDateRegistration()->format('Y-m-d H:i:s') : null
                ];
            }
            
            return $this->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
            ], 500);
        }
    }
}
