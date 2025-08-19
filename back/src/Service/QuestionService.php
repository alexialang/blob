<?php

namespace App\Service;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\TypeQuestion;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class QuestionService
{
    private EntityManagerInterface $em;
    private QuestionRepository $questionRepository;
    private ManagerRegistry $registry;

    public function __construct(EntityManagerInterface $em, QuestionRepository $questionRepository, ManagerRegistry $registry, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->questionRepository = $questionRepository;
        $this->registry = $registry;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->questionRepository->findAll();
    }

    public function create(array $data): Question
    {
        $this->validateQuestionData($data);
        
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
        $this->validateQuestionData($data);
        
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
    public function find(int $id): ?Question
    {
        return $this->questionRepository->find($id);
    }

    private function validateQuestionData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'question' => [
                    new Assert\NotBlank(['message' => 'La question est requise']),
                    new Assert\Length(['max' => 1000, 'maxMessage' => 'La question ne peut pas dépasser 1000 caractères'])
                ],
                'quiz_id' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'L\'ID du quiz doit être un entier'])
                    ])
                ],
                'type_question_id' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'L\'ID du type de question doit être un entier'])
                    ])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
