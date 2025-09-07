<?php

namespace App\Repository;

use App\Entity\TypeQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeQuestion>
 */
class TypeQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeQuestion::class);
    }

    //    /**
    //     */
    //    {
    //        ;
    //    }

    //    {
    //        ;
    //    }
}
