<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\QuizRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class QuizSearchService
{
    private QuizRepository $quizRepository;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;

    public function __construct(
        QuizRepository $quizRepository,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        $this->quizRepository = $quizRepository;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Liste les quiz publiés ou tous les quiz selon le contexte
     * 
     * @param bool $forManagement Si true, retourne tous les quiz pour la gestion
     * @return array Liste des quiz
     */
    public function list(bool $forManagement = false): array
    {
        return $this->quizRepository->findPublishedOrAll($forManagement);
    }

    /**
     * Récupère les quiz pour la gestion d'entreprise avec pagination
     * Filtre selon les permissions de l'utilisateur
     *
     * @param User $user L'utilisateur demandant les quiz
     * @param int $page Numéro de page (défaut: 1)
     * @param int $limit Nombre d'éléments par page (défaut: 20)
     * @param string|null $search Terme de recherche optionnel
     * @param string $sort Champ de tri (défaut: 'id')
     * @return array Résultat avec données et pagination
     * @throws \Exception
     */
    public function getQuizzesForCompanyManagement(User $user, int $page = 1, int $limit = 20, ?string $search = null, string $sort = 'id'): array
    {
        try {
            $result = $this->quizRepository->findWithPagination($page, $limit, $search, $sort, $user);
            
            $formattedData = json_decode(
                $this->serializer->serialize($result['data'], 'json', ['groups' => ['quiz:organized']]),
                true
            );

            return [
                'data' => $formattedData,
                'pagination' => $result['pagination']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans getQuizzesForCompanyManagement: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les quiz privés accessibles à un utilisateur
     * 
     * @param User $user L'utilisateur
     * @return array Quiz privés accessibles
     */
    public function getPrivateQuizzesForUser(User $user): array
    {
        $privateQuizzes = $this->quizRepository->findPrivateQuizzesForUser($user);
        
        return json_decode(
            $this->serializer->serialize($privateQuizzes, 'json', ['groups' => ['quiz:organized']]),
            true
        );
    }

    /**
     * Récupère les quiz créés par un utilisateur
     * 
     * @param User $user L'utilisateur créateur
     * @return array Ses quiz
     */
    public function getMyQuizzes(User $user): array
    {
        $quizzes = $this->quizRepository->findByUser($user);
        
        return json_decode(
            $this->serializer->serialize($quizzes, 'json', ['groups' => ['quiz:organized']]),
            true
        );
    }

    /**
     * Récupère les quiz les plus populaires
     * 
     * @param int $limit Nombre maximum de quiz à retourner
     * @return array Quiz populaires
     */
    public function getMostPopularQuizzes(int $limit = 8): array
    {
        return $this->quizRepository->findMostPopular($limit);
    }

    /**
     * Récupère les quiz les plus récents
     * 
     * @param int $limit Nombre maximum de quiz à retourner
     * @return array Quiz récents
     */
    public function getMostRecentQuizzes(int $limit = 6): array
    {
        return $this->quizRepository->findMostRecent($limit);
    }

    /**
     * Vérifie si un utilisateur peut accéder à un quiz
     * 
     * @param User $user L'utilisateur
     * @param mixed $quiz Le quiz
     * @return bool True si l'utilisateur peut accéder au quiz
     */
    private function canUserAccessQuiz(User $user, $quiz): bool
    {
        if ($quiz->isPublic()) {
            return true;
        }

        if ($quiz->getUser()->getId() === $user->getId()) {
            return true;
        }

        $userGroups = $user->getGroups();
        $quizGroups = $quiz->getGroups();
        
        foreach ($userGroups as $userGroup) {
            foreach ($quizGroups as $quizGroup) {
                if ($userGroup->getId() === $quizGroup->getId()) {
                    return true;
                }
            }
        }

        return false;
    }
}

