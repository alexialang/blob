<?php

namespace App\Service;

use App\Entity\Answer;
use App\Service\QuestionService;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnswerService
{
    private EntityManagerInterface $em;
    private AnswerRepository $answerRepository;
    private QuestionService $questionService;

    public function __construct(EntityManagerInterface $em, AnswerRepository $answerRepository, QuestionService $questionService)
    {
        $this->em = $em;
        $this->answerRepository = $answerRepository;
        $this->questionService = $questionService;
    }

    public function list(): array
    {
        return $this->answerRepository->findAll();
    }

    public function create(array $data): Answer
    {
        $answer = new Answer();
        $answer->setAnswer($data['answer']);
        $answer->setIsCorrect($data['is_correct'] ?? null);
        $answer->setOrderCorrect($data['order_correct'] ?? null);
        $answer->setPairId($data['pair_id'] ?? null);
        $answer->setIsIntrus($data['is_intrus'] ?? null);

        $question = $this->questionService->find($data['question_id']);
        $answer->setQuestion($question);

        $this->em->persist($answer);
        $this->em->flush();

        return $answer;
    }

    public function show(Answer $answer): Answer
    {
        return $answer;
    }

    public function update(Answer $answer, array $data): Answer
    {
        if (isset($data['answer'])) {
            $answer->setAnswer($data['answer']);
        }
        if (array_key_exists('is_correct', $data)) {
            $answer->setIsCorrect($data['is_correct']);
        }
        if (array_key_exists('order_correct', $data)) {
            $answer->setOrderCorrect($data['order_correct']);
        }
        if (array_key_exists('pair_id', $data)) {
            $answer->setPairId($data['pair_id']);
        }
        if (array_key_exists('is_intrus', $data)) {
            $answer->setIsIntrus($data['is_intrus']);
        }
        if (isset($data['question_id'])) {
            $question = $this->questionService->find($data['question_id']);
            $answer->setQuestion($question);
        }

        $this->em->flush();

        return $answer;
    }

    public function delete(Answer $answer): void
    {
        $this->em->remove($answer);
        $this->em->flush();
    }
}
