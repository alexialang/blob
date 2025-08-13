<?php

namespace App\Repository;

use App\Entity\GameSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameSession>
 */
class GameSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameSession::class);
    }

    public function findByGameCode(string $gameCode): ?GameSession
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.gameCode = :gameCode')
            ->setParameter('gameCode', $gameCode)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
