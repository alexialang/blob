<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class GlobalStatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /**
     * @param int $limit Limite de résultats (défaut: 20)
     *
     * @return array Array de statistiques par quiz
     */
    public function getTeamScoresByQuiz(int $limit = 20): array
    {
        try {
            $qb = $this->createQueryBuilder('q');
            $results = $qb->select('
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(DISTINCT ua.user) as participants
                ')
                ->leftJoin(\App\Entity\UserAnswer::class, 'ua', 'WITH', 'q.id = ua.quiz')
                ->leftJoin('ua.user', 'u')
                ->where('u.isActive = true')
                ->andWhere('u.deletedAt IS NULL')
                ->andWhere('q.isPublic = true')
                ->groupBy('q.id, q.title')
                ->having('COUNT(ua.id) > 0')
                ->orderBy('q.date_creation', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $teamScores = [];
            foreach ($results as $result) {
                $teamScores[] = [
                    'quizTitle' => $result['quizTitle'],
                    'quizId' => (int) $result['quizId'],
                    'averageScore' => (float) $result['averageScore'],
                    'participants' => (int) $result['participants'],
                ];
            }

            return $teamScores;
        } catch (\Exception) {
            // Erreur dans getTeamScoresByQuiz: " . $e->getMessage()
            return [];
        }
    }

    /**
     * Récupère les scores par quiz filtrés pour une entreprise spécifique (OPTIMIZED).
     *
     * @param int $companyId ID de l'entreprise à analyser
     * @param int $limit     Limite de résultats (défaut: 20)
     *
     * @return array Statistiques des quiz pour cette entreprise
     */
    public function getTeamScoresByQuizForCompany(int $companyId, int $limit = 20): array
    {
        try {
            $qb = $this->createQueryBuilder('q');
            $results = $qb->select('
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(DISTINCT ua.user) as participants
                ')
                ->leftJoin(\App\Entity\UserAnswer::class, 'ua', 'WITH', 'q.id = ua.quiz')
                ->leftJoin('ua.user', 'u')
                ->where('u.isActive = true')
                ->andWhere('u.deletedAt IS NULL')
                ->andWhere('u.company = :companyId')
                ->andWhere('q.isPublic = true')
                ->setParameter('companyId', $companyId)
                ->groupBy('q.id, q.title')
                ->having('COUNT(ua.id) > 0')
                ->orderBy('q.date_creation', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $teamScores = [];
            foreach ($results as $result) {
                $averageScore = null !== $result['averageScore'] ? (float) $result['averageScore'] : 0.0;
                $teamScores[] = [
                    'quizTitle' => $result['quizTitle'],
                    'quizId' => (int) $result['quizId'],
                    'averageScore' => $averageScore,
                    'participants' => (int) $result['participants'],
                ];
            }

            return $teamScores;
        } catch (\Exception $e) {
            error_log('Erreur dans getTeamScoresByQuizForCompany: '.$e->getMessage());

            return [];
        }
    }

    public function getGroupScoresByQuiz(int $limit = 100): array
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $results = $qb->select('
                    g.name as groupName,
                    g.id as groupId,
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(DISTINCT ua.user) as participants
                ')
                ->from(\App\Entity\Group::class, 'g')
                ->innerJoin('g.users', 'u')
                ->innerJoin(\App\Entity\UserAnswer::class, 'ua', 'WITH', 'ua.user = u.id')
                ->innerJoin('ua.quiz', 'q')
                ->where('u.isActive = true')
                ->andWhere('u.deletedAt IS NULL')
                ->andWhere('q.isPublic = false')
                ->groupBy('g.id, g.name, q.id, q.title')
                ->orderBy('g.name', 'ASC')
                ->addOrderBy('q.date_creation', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $groupScores = [];
            foreach ($results as $result) {
                $groupName = $result['groupName'];
                if (!isset($groupScores[$groupName])) {
                    $groupScores[$groupName] = [];
                }
                $groupScores[$groupName][] = [
                    'quizTitle' => $result['quizTitle'],
                    'quizId' => (int) $result['quizId'],
                    'averageScore' => (float) $result['averageScore'],
                    'participants' => (int) $result['participants'],
                ];
            }

            return $groupScores;
        } catch (\Exception) {
            return [];
        }
    }

    public function getGroupScoresByQuizForCompany(int $companyId, int $limit = 100): array
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $results = $qb->select('
                    g.name as groupName,
                    g.id as groupId,
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(DISTINCT ua.user) as participants
                ')
                ->from(\App\Entity\Group::class, 'g')
                ->innerJoin('g.users', 'u')
                ->innerJoin(\App\Entity\UserAnswer::class, 'ua', 'WITH', 'ua.user = u.id')
                ->innerJoin('ua.quiz', 'q')
                ->where('u.isActive = true')
                ->andWhere('u.deletedAt IS NULL')
                ->andWhere('g.company = :companyId')
                ->andWhere('q.isPublic = false')
                ->setParameter('companyId', $companyId)
                ->groupBy('g.id, g.name, q.id, q.title')
                ->orderBy('g.name', 'ASC')
                ->addOrderBy('q.date_creation', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $groupScores = [];
            foreach ($results as $result) {
                $groupName = $result['groupName'];
                if (!isset($groupScores[$groupName])) {
                    $groupScores[$groupName] = [];
                }
                $groupScores[$groupName][] = [
                    'quizTitle' => $result['quizTitle'],
                    'quizId' => (int) $result['quizId'],
                    'averageScore' => (float) $result['averageScore'],
                    'participants' => (int) $result['participants'],
                ];
            }

            return $groupScores;
        } catch (\Exception) {
            return [];
        }
    }
}
