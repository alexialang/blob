<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\QuizRating;
use App\Entity\User;
use App\Repository\QuizRatingRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizRatingService
{
    private QuizRatingRepository $quizRatingRepository;
    private EntityManagerInterface $em;

    public function __construct(QuizRatingRepository $quizRatingRepository, EntityManagerInterface $em)
    {
        $this->quizRatingRepository = $quizRatingRepository;
        $this->em = $em;
    }

    /**
     * Récupère la note moyenne et le nombre d'évaluations d'un quiz
     * 
     * @param Quiz $quiz Le quiz à évaluer
     * @return array Array contenant 'averageRating' et 'ratingCount'
     */
    public function getAverageRating(Quiz $quiz): array
    {
        $averageRating = $this->quizRatingRepository->findAverageRatingForQuiz($quiz->getId());
        $ratingCount = $this->quizRatingRepository->countRatingsForQuiz($quiz->getId());

        return [
            'averageRating' => $averageRating ?? 0,
            'ratingCount' => $ratingCount
        ];
    }

    /**
     * Récupère les statistiques détaillées des ratings d'un quiz
     * 
     * @param Quiz $quiz Le quiz à analyser
     * @param User|null $currentUser L'utilisateur courant (pour sa note)
     * @return array Statistiques détaillées (moyenne, total, note utilisateur)
     */
    public function getRatingStatistics(Quiz $quiz, ?User $currentUser = null): array
    {
        $avgRating = $this->quizRatingRepository->findAverageRatingForQuiz($quiz->getId());
        $totalRatings = $this->quizRatingRepository->countRatingsForQuiz($quiz->getId());
        
        $userRating = null;
        if ($currentUser) {
            $userRatingEntity = $this->quizRatingRepository->findUserRatingForQuiz($currentUser->getId(), $quiz->getId());
            $userRating = $userRatingEntity ? $userRatingEntity->getRating() : null;
        }

        return [
            'averageRating' => $avgRating,
            'totalRatings' => $totalRatings,
            'userRating' => $userRating
        ];
    }

    /**
     * Ajoute ou met à jour la note d'un utilisateur pour un quiz
     * 
     * @param User $user L'utilisateur qui note
     * @param Quiz $quiz Le quiz à noter
     * @param int $rating La note (1-5)
     * @return array Statistiques mises à jour
     */
    public function rateQuiz(User $user, Quiz $quiz, int $rating): array
    {
        $existingRating = $this->quizRatingRepository->findUserRatingForQuiz($user->getId(), $quiz->getId());
        
        if ($existingRating) {
            $existingRating->setRating($rating);
            $existingRating->setRatedAt(new \DateTime());
        } else {
            $quizRating = new QuizRating();
            $quizRating->setUser($user);
            $quizRating->setQuiz($quiz);
            $quizRating->setRating($rating);
            $quizRating->setRatedAt(new \DateTime());
            $this->em->persist($quizRating);
        }
        
        $this->em->flush();

        return $this->getRatingStatistics($quiz, $user);
    }
}
