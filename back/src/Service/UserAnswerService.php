<?php

namespace App\Service;

use App\Entity\UserAnswer;
use App\Service\UserService;
use App\Service\QuizService;
use App\Repository\UserAnswerRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserAnswerService
{
    private EntityManagerInterface $em;
    private UserAnswerRepository $userAnswerRepository;
    private UserService $userService;
    private QuizService $quizService;

    public function __construct(EntityManagerInterface $em, UserAnswerRepository $userAnswerRepository, UserService $userService, QuizService $quizService)
    {
        $this->em = $em;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->userService = $userService;
        $this->quizService = $quizService;
    }

    public function list(): array
    {
        return $this->userAnswerRepository->findAll();
    }

    public function create(array $data): UserAnswer
    {
        $userAnswer = new UserAnswer();
        $userAnswer->setDateAttempt(new \DateTimeImmutable());
        $userAnswer->setTotalScore($data['total_score']);

        $user = $this->userService->find($data['user_id']);
        $quiz = $this->quizService->find($data['quiz_id']);

        $userAnswer->setUser($user);
        $userAnswer->setQuiz($quiz);

        $this->em->persist($userAnswer);
        $this->em->flush();

        return $userAnswer;
    }

    public function show(UserAnswer $userAnswer): UserAnswer
    {
        return $userAnswer;
    }

    public function update(UserAnswer $userAnswer, array $data): UserAnswer
    {
        if (isset($data['total_score'])) {
            $userAnswer->setTotalScore($data['total_score']);
        }
        if (isset($data['quiz_id'])) {
            $quiz = $this->quizService->find($data['quiz_id']);
            $userAnswer->setQuiz($quiz);
        }
        if (isset($data['user_id'])) {
            $user = $this->userService->find($data['user_id']);
            $userAnswer->setUser($user);
        }

        $this->em->flush();

        return $userAnswer;
    }

    public function delete(UserAnswer $userAnswer): void
    {
        $this->em->remove($userAnswer);
        $this->em->flush();
    }
}
