<?php

namespace App\Service;

use App\Entity\CategoryQuiz;
use App\Repository\CategoryQuizRepository;

class CategoryQuizService
{
    private CategoryQuizRepository $categoryQuizRepository;

    public function __construct(CategoryQuizRepository $categoryQuizRepository)
    {
        $this->categoryQuizRepository = $categoryQuizRepository;
    }

    public function list(): array
    {
        return $this->categoryQuizRepository->findAll();
    }

    /**
     * Trouve une catégorie par son ID.
     *
     * @param int $id L'ID de la catégorie
     *
     * @return CategoryQuiz|null La catégorie trouvée ou null
     *
     * @throws \InvalidArgumentException Si l'ID est invalide
     */
    public function find(int $id): ?CategoryQuiz
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('L\'ID de la catégorie doit être positif');
        }

        return $this->categoryQuizRepository->find($id);
    }
}
