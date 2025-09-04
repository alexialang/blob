<?php

namespace App\Service;

use App\Repository\TypeQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;

class TypeQuestionService
{
    private EntityManagerInterface $em;
    private TypeQuestionRepository $typeQuestionRepository;

    public function __construct(EntityManagerInterface $em, TypeQuestionRepository $typeQuestionRepository)
    {
        $this->em = $em;
        $this->typeQuestionRepository = $typeQuestionRepository;
    }

    public function list(): array
    {
        return $this->typeQuestionRepository->findAll();
    }

    /**
     * Show a specific type question by ID
     */
    public function show(int $id): ?object
    {
        return $this->typeQuestionRepository->find($id);
    }
}
