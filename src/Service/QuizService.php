<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizService
{
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;

    public function __construct(EntityManagerInterface $em, QuizRepository $quizRepository)
    {
        $this->em = $em;
        $this->quizRepository = $quizRepository;
    }

    public function list(): array
    {
        return $this->quizRepository->findAll();
    }

    public function create(array $data): Quiz
    {

    }

    public function show(Quiz $quiz): Quiz
    {
        return $quiz;
    }

    public function update(Quiz $quiz, array $data): Quiz
    {

    }

    public function delete(Quiz $quiz): void
    {
        $this->em->remove($quiz);
        $this->em->flush();
    }
}
