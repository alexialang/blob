<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\QuizRepository;
use Psr\Log\LoggerInterface;

class QuizSearchService
{
    private QuizRepository $quizRepository;
    private LoggerInterface $logger;

    public function __construct(
        QuizRepository $quizRepository,
        LoggerInterface $logger
    ) {
        $this->quizRepository = $quizRepository;
        $this->logger = $logger;
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
     * Récupère les quiz pour la gestion d'entreprise
     * Filtre selon les permissions de l'utilisateur
     *
     * @param User $user L'utilisateur demandant les quiz
     * @return array Liste des quiz accessibles
     * @throws \Exception
     */
    public function getQuizzesForCompanyManagement(User $user): array
    {
        try {
            if ($user->isAdmin()) {
                $quizzes = $this->quizRepository->findAll();
            } else {
                $allQuizzes = $this->quizRepository->findAll();
                $quizzes = [];
                
                foreach ($allQuizzes as $quiz) {
                    if ($this->canUserAccessQuiz($user, $quiz)) {
                        $quizzes[] = $quiz;
                    }
                }
            }

            $result = [];
            foreach ($quizzes as $quiz) {
                $result[] = [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'description' => $quiz->getDescription(),
                    'status' => $quiz->getStatus()->value,
                    'isPublic' => $quiz->isPublic(),
                    'dateCreation' => $quiz->getDateCreation()?->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $quiz->getUser()->getId(),
                        'firstName' => $quiz->getUser()->getFirstName(),
                        'lastName' => $quiz->getUser()->getLastName(),
                        'email' => $quiz->getUser()->getEmail()
                    ],
                    'category' => $quiz->getCategory() ? [
                        'id' => $quiz->getCategory()->getId(),
                        'name' => $quiz->getCategory()->getName()
                    ] : null,
                    'groups' => $quiz->getGroups()->map(function($group) {
                        return [
                            'id' => $group->getId(),
                            'name' => $group->getName()
                        ];
                    })->toArray(),
                    'questionCount' => $quiz->getQuestions()->count()
                ];
            }

            return $result;
            
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
        
        return array_map(function($quiz) {
            return [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'description' => $quiz->getDescription(),
                'difficulty' => $quiz->getDifficulty()?->value,
                'category' => $quiz->getCategory() ? [
                    'id' => $quiz->getCategory()->getId(),
                    'name' => $quiz->getCategory()->getName()
                ] : null,
                'questionCount' => $quiz->getQuestions()->count(),
                'dateCreation' => $quiz->getDateCreation()?->format('Y-m-d H:i:s')
            ];
        }, $privateQuizzes);
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
        
        return array_map(function($quiz) {
            return [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'description' => $quiz->getDescription(),
                'status' => $quiz->getStatus()->value,
                'isPublic' => $quiz->isPublic(),
                'dateCreation' => $quiz->getDateCreation()?->format('Y-m-d H:i:s')
            ];
        }, $quizzes);
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

