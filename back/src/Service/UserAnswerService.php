<?php

namespace App\Service;

use App\Entity\UserAnswer;
use App\Entity\QuizRating;
use App\Event\QuizCompletedEvent;
use App\Service\UserService;
use App\Service\QuizService;
use App\Repository\UserAnswerRepository;
use App\Repository\QuizRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserAnswerService
{
    private EntityManagerInterface $em;
    private UserAnswerRepository $userAnswerRepository;
    private QuizRatingRepository $quizRatingRepository;
    private UserService $userService;
    private QuizService $quizService;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $em, UserAnswerRepository $userAnswerRepository, QuizRatingRepository $quizRatingRepository, UserService $userService, QuizService $quizService, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->quizRatingRepository = $quizRatingRepository;
        $this->userService = $userService;
        $this->quizService = $quizService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function list(): array
    {
        return $this->userAnswerRepository->findAll();
    }

    public function create(array $data): UserAnswer
    {
        $userAnswer = new UserAnswer();
        $userAnswer->setDateAttempt(new \DateTimeImmutable());
        $userAnswer->setTotalScore($data['total_score']);

        $user = $this->userService->find($data['user_id']);
        $quiz = $this->quizService->find($data['quiz_id']);

        $userAnswer->setUser($user);
        $userAnswer->setQuiz($quiz);

        $this->em->persist($userAnswer);
        $this->em->flush();

        return $userAnswer;
    }

    public function show(UserAnswer $userAnswer): UserAnswer
    {
        return $userAnswer;
    }

    public function update(UserAnswer $userAnswer, array $data): UserAnswer
    {
        if (isset($data['total_score'])) {
            $userAnswer->setTotalScore($data['total_score']);
        }
        if (isset($data['quiz_id'])) {
            $quiz = $this->quizService->find($data['quiz_id']);
            $userAnswer->setQuiz($quiz);
        }
        if (isset($data['user_id'])) {
            $user = $this->userService->find($data['user_id']);
            $userAnswer->setUser($user);
        }

        $this->em->flush();

        return $userAnswer;
    }

    public function delete(UserAnswer $userAnswer): void
    {
        $this->em->remove($userAnswer);
        $this->em->flush();
    }

    public function saveGameResult(array $data): UserAnswer
    {
        if (!isset($data['user']) || !isset($data['quiz_id']) || !isset($data['total_score'])) {
            throw new \InvalidArgumentException('Missing required data: user, quiz_id, total_score');
        }

        $user = $data['user'];
        $quiz = $this->quizService->find($data['quiz_id']);
        
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $userAnswer = new UserAnswer();
        $userAnswer->setUser($user);
        $userAnswer->setQuiz($quiz);
        $userAnswer->setTotalScore($data['total_score']);
        $userAnswer->setDateAttempt(new \DateTime());

        $this->em->persist($userAnswer);
        $this->em->flush();

        $event = new QuizCompletedEvent($userAnswer, $user);
        $this->eventDispatcher->dispatch($event, QuizCompletedEvent::NAME);

        return $userAnswer;
    }

    public function rateQuiz(array $data): array
    {
        if (!isset($data['user']) || !isset($data['quizId']) || !isset($data['rating'])) {
            throw new \InvalidArgumentException('Missing required data: user, quizId, rating');
        }

        $user = $data['user'];
        $quiz = $this->quizService->find($data['quizId']);
        
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $existingRating = $this->quizRatingRepository->findUserRatingForQuiz($user->getId(), $data['quizId']);
        
        if ($existingRating) {
            $existingRating->setRating($data['rating']);
            $existingRating->setRatedAt(new \DateTime());
        } else {
            $quizRating = new QuizRating();
            $quizRating->setUser($user);
            $quizRating->setQuiz($quiz);
            $quizRating->setRating($data['rating']);
            $quizRating->setRatedAt(new \DateTime());
            $this->em->persist($quizRating);
        }
        
        $this->em->flush();

        $avgRating = $this->quizRatingRepository->findAverageRatingForQuiz($data['quizId']);
        $totalRatings = $this->quizRatingRepository->countRatingsForQuiz($data['quizId']);

        return [
            'success' => true,
            'averageRating' => $avgRating,
            'totalRatings' => $totalRatings
        ];
    }



    public function getQuizLeaderboard(int $quizId, $currentUser = null): array
    {
        $quiz = $this->quizService->find($quizId);
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $qb = $this->em->createQueryBuilder();
        $results = $qb->select('MAX(ua.total_score) as score, u.pseudo as name, u.firstName as firstName, u.lastName as lastName, c.name as company, u.id as userId, MIN(ua.date_attempt) as firstAttempt')
            ->from(UserAnswer::class, 'ua')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('ua.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->groupBy('u.id')
            ->orderBy('score', 'DESC')
            ->addOrderBy('firstAttempt', 'ASC')
            ->getQuery()
            ->getResult();

        $leaderboard = [];
        $currentUserRank = null;
        
        foreach ($results as $index => $result) {
            $rank = $index + 1;
            $isCurrentUser = $currentUser && $result['userId'] == $currentUser?->getId();
            
            if ($isCurrentUser) {
                $currentUserRank = $rank;
            }

            $displayName = $result['name'] ?: ($result['firstName'] . ' ' . $result['lastName']);
            if (!$displayName || trim($displayName) === '') {
                $displayName = 'Joueur anonyme';
            }

            $leaderboard[] = [
                'rank' => $rank,
                'name' => trim($displayName),
                'company' => $result['company'] ?: 'Aucune entreprise',
                'score' => $result['score'],
                'isCurrentUser' => $isCurrentUser
            ];
        }

        return [
            'leaderboard' => array_slice($leaderboard, 0, 10),
            'currentUserRank' => $currentUserRank ?: count($results) + 1,
            'totalPlayers' => count($results),
            'currentUserScore' => $currentUser ? $this->getCurrentUserScore($quizId, $currentUser?->getId() ?: 0) : 0
        ];
    }

    private function getCurrentUserScore(int $quizId, int $userId): int
    {
        $result = $this->em->createQueryBuilder()
            ->select('MAX(ua.total_score) as maxScore')
            ->from(UserAnswer::class, 'ua')
            ->where('ua.quiz = :quiz AND ua.user = :user')
            ->setParameter('quiz', $quizId)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    public function getQuizRating(int $quizId, $currentUser = null): array
    {
        $quiz = $this->quizService->find($quizId);
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $avgRating = $this->quizRatingRepository->findAverageRatingForQuiz($quizId);
        $totalRatings = $this->quizRatingRepository->countRatingsForQuiz($quizId);
        
        $userRating = null;
        if ($currentUser) {
            $userRatingEntity = $this->quizRatingRepository->findUserRatingForQuiz($currentUser->getId(), $quizId);
            $userRating = $userRatingEntity ? $userRatingEntity->getRating() : null;
        }

        return [
            'averageRating' => $avgRating,
            'totalRatings' => $totalRatings,
            'userRating' => $userRating
        ];
    }
}
