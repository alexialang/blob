<?php

namespace App\Repository;

use App\Entity\QuizRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizRating>
 */
class QuizRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizRating::class);
    }

    public function findAverageRatingForQuiz(int $quizId): ?float
    {
        $result = $this->createQueryBuilder('qr')
            ->select('AVG(qr.rating) as avgRating')
            ->where('qr.quiz = :quiz')
            ->setParameter('quiz', $quizId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 1) : null;
    }

    public function findUserRatingForQuiz(int $userId, int $quizId): ?QuizRating
    {
        return $this->createQueryBuilder('qr')
            ->where('qr.user = :user')
            ->andWhere('qr.quiz = :quiz')
            ->setParameter('user', $userId)
            ->setParameter('quiz', $quizId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countRatingsForQuiz(int $quizId): int
    {
        return $this->createQueryBuilder('qr')
            ->select('COUNT(qr.id)')
            ->where('qr.quiz = :quiz')
            ->setParameter('quiz', $quizId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
