<?php

namespace App\Service;

use App\Repository\TypeQuestionRepository;

class TypeQuestionService
{
    private TypeQuestionRepository $typeQuestionRepository;

    public function __construct(TypeQuestionRepository $typeQuestionRepository)
    {
        $this->typeQuestionRepository = $typeQuestionRepository;
    }

    public function list(): array
    {
        return $this->typeQuestionRepository->findAll();
    }

    public function find(int $id)
    {
        return $this->typeQuestionRepository->find($id);
    }

    /**
     * Alias pour find() - compatibilité avec le controller.
     */
    public function show(int $id)
    {
        return $this->find($id);
    }
}
