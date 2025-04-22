<?php

namespace App\Service;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\TypeQuestion;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class QuestionService
{
    private EntityManagerInterface $em;
    private QuestionRepository $questionRepository;
    private ManagerRegistry $registry;

    public function __construct(EntityManagerInterface $em, QuestionRepository $questionRepository, ManagerRegistry $registry)
    {
        $this->em = $em;
        $this->questionRepository = $questionRepository;
        $this->registry = $registry;
    }

    public function list(): array
    {
        return $this->questionRepository->findAll();
    }

    public function create(array $data): Question
    {
        $question = new Question();
        $question->setQuestion($data['question']);
        $quiz = $this->registry->getRepository(Quiz::class)->find($data['quiz_id']);
        $typeQuestion = $this->registry->getRepository(TypeQuestion::class)->find($data['type_question_id']);

        $question->setQuiz($quiz);
        $question->setTypeQuestion($typeQuestion);

        $this->em->persist($question);
        $this->em->flush();

        return $question;
    }

    public function show(Question $question): Question
    {
        return $question;
    }

    public function update(Question $question, array $data): Question
    {
        if (isset($data['question'])) {
            $question->setQuestion($data['question']);
        }

        if (isset($data['quiz_id'])) {
            $quiz = $this->registry->getRepository(Quiz::class)->find($data['quiz_id']);
            $question->setQuiz($quiz);
        }

        if (isset($data['type_question_id'])) {
            $typeQuestion = $this->registry->getRepository(TypeQuestion::class)->find($data['type_question_id']);
            $question->setTypeQuestion($typeQuestion);
        }

        $this->em->flush();

        return $question;
    }

    public function delete(Question $question): void
    {
        $this->em->remove($question);
        $this->em->flush();
    }
}
