<?php

namespace App\Service;

use App\Entity\CategoryQuiz;
use App\Repository\CategoryQuizRepository;
use Doctrine\ORM\EntityManagerInterface;

class CategoryQuizService
{
    private EntityManagerInterface $em;
    private CategoryQuizRepository $categoryQuizRepository;

    public function __construct(EntityManagerInterface $em, CategoryQuizRepository $categoryQuizRepository)
    {
        $this->em = $em;
        $this->categoryQuizRepository = $categoryQuizRepository;
    }

    public function list(): array
    {
        return $this->categoryQuizRepository->findAll();
    }

    public function find(int $id): ?CategoryQuiz
    {
        return $this->categoryQuizRepository->find($id);
    }

    public function create(array $data): CategoryQuiz
    {
        $category = new CategoryQuiz();
        $category->setName($data['name']);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    public function update(CategoryQuiz $category, array $data): CategoryQuiz
    {
        if (isset($data['name'])) {
            $category->setName($data['name']);
        }

        $this->em->flush();

        return $category;
    }

    public function delete(CategoryQuiz $category): void
    {
        $this->em->remove($category);
        $this->em->flush();
    }
}
