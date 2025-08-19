<?php

namespace App\Service;

use AllowDynamicProperties;
use App\Entity\CategoryQuiz;
use App\Repository\CategoryQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AllowDynamicProperties]
class CategoryQuizService
{
    private EntityManagerInterface $em;
    private CategoryQuizRepository $categoryQuizRepository;

    public function __construct(EntityManagerInterface $em, CategoryQuizRepository $categoryQuizRepository, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->categoryQuizRepository = $categoryQuizRepository;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->categoryQuizRepository->findAll();
    }

    public function find(int $id): ?CategoryQuiz
    {
        return $this->categoryQuizRepository->find($id);
    }

}
