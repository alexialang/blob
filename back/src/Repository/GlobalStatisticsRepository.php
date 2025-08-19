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

    public function getTeamScoresByQuiz(): array
    {
        try {
            $qb = $this->createQueryBuilder('q');
            $results = $qb->select('
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(DISTINCT ua.user) as participants
                ')
                ->leftJoin('App\Entity\UserAnswer', 'ua', 'WITH', 'q.id = ua.quiz')
                ->leftJoin('ua.user', 'u')
                ->where('u.isActive = true')
                ->andWhere('u.deletedAt IS NULL')
                ->andWhere('q.isPublic = true')
                ->groupBy('q.id, q.title')
                ->having('COUNT(ua.id) > 0')
                ->orderBy('q.date_creation', 'ASC')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();
            
            $teamScores = [];
            foreach ($results as $result) {
                $teamScores[] = [
                    'quizTitle' => $result['quizTitle'],
                    'quizId' => (int)$result['quizId'],
                    'averageScore' => (float)$result['averageScore'],
                    'participants' => (int)$result['participants']
                ];
            }
            return $teamScores;
        } catch (\Exception $e) {
            error_log("Erreur dans getTeamScoresByQuiz: " . $e->getMessage());
            return [];
        }
    }

    public function getTeamScoresByQuizForCompany(int $companyId): array
    {
        try {
            error_log("=== DÉBUT getTeamScoresByQuizForCompany ===");
            
            $qb = $this->createQueryBuilder('q');
            $results = $qb->select('
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(ua.user) as participants
                ')
                ->leftJoin('App\Entity\UserAnswer', 'ua', 'WITH', 'q.id = ua.quiz')
                ->where('q.isPublic = true')
                ->groupBy('q.id, q.title')
                ->orderBy('q.date_creation', 'ASC')
                ->getQuery()
                ->getResult();
            
            error_log("Résultats bruts de la requête: " . json_encode($results));
            
            $teamScores = [];
            foreach ($results as $result) {
                $averageScore = $result['averageScore'] !== null ? (float)$result['averageScore'] : 0.0;
                $teamScores[] = [
                    'quizTitle' => $result['quizTitle'],
                    'quizId' => (int)$result['quizId'],
                    'averageScore' => $averageScore,
                    'participants' => (int)$result['participants']
                ];
            }
            
            error_log("Quiz d'équipe trouvés: " . count($teamScores));
            error_log("=== FIN getTeamScoresByQuizForCompany ===");
            return $teamScores;
        } catch (\Exception $e) {
            error_log("Erreur dans getTeamScoresByQuizForCompany: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    public function getGroupScoresByQuiz(): array
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
                ->from('App\Entity\Group', 'g')
                ->innerJoin('g.users', 'u')
                ->innerJoin('App\Entity\UserAnswer', 'ua', 'WITH', 'ua.user = u.id')
                ->innerJoin('ua.quiz', 'q')
                ->where('u.isActive = true')
                ->andWhere('u.deletedAt IS NULL')
                ->andWhere('q.isPublic = false')
                ->groupBy('g.id, g.name, q.id, q.title')
                ->orderBy('g.name', 'ASC')
                ->addOrderBy('q.date_creation', 'ASC')
                ->setMaxResults(50)
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
                    'quizId' => (int)$result['quizId'],
                    'averageScore' => (float)$result['averageScore'],
                    'participants' => (int)$result['participants']
                ];
            }
            return $groupScores;
        } catch (\Exception $e) {
            error_log("Erreur dans getGroupScoresByQuiz: " . $e->getMessage());
            return [];
        }
    }

    public function getGroupScoresByQuizForCompany(int $companyId): array
    {
        try {
            error_log("=== DÉBUT getGroupScoresByQuizForCompany ===");
            
            $qb = $this->createQueryBuilder('q');
            $results = $qb->select('
                    q.title as quizTitle,
                    q.id as quizId,
                    AVG(ua.total_score) as averageScore,
                    COUNT(ua.user) as participants
                ')
                ->leftJoin('App\Entity\UserAnswer', 'ua', 'WITH', 'q.id = ua.quiz')
                ->where('q.isPublic = false')
                ->groupBy('q.id, q.title')
                ->orderBy('q.date_creation', 'ASC')
                ->getQuery()
                ->getResult();
            
            error_log("Résultats bruts de la requête groupes: " . json_encode($results));
            
            $groupScores = [];
            if (!empty($results)) {
                $groupScores['Groupe Entreprise Test'] = [];
                foreach ($results as $result) {
                    $averageScore = $result['averageScore'] !== null ? (float)$result['averageScore'] : 0.0;
                    $groupScores['Groupe Entreprise Test'][] = [
                        'quizTitle' => $result['quizTitle'],
                        'quizId' => (int)$result['quizId'],
                        'averageScore' => $averageScore,
                        'participants' => (int)$result['participants']
                    ];
                }
            }
            
            error_log("Quiz de groupes trouvés: " . count($groupScores));
            error_log("=== FIN getGroupScoresByQuizForCompany ===");
            return $groupScores;
        } catch (\Exception $e) {
            error_log("Erreur dans getGroupScoresByQuizForCompany: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
}
