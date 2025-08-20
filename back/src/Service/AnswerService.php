<?php

namespace App\Service;

use App\Entity\Answer;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class AnswerService
{
    private EntityManagerInterface $em;
    private AnswerRepository $answerRepository;
    private QuestionService $questionService;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $em, 
        AnswerRepository $answerRepository, 
        QuestionService $questionService,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->answerRepository = $answerRepository;
        $this->questionService = $questionService;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->answerRepository->findAll();
    }

    public function create(array $data): Answer
    {
        $this->validateAnswerData($data);
        
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
        $this->validateAnswerData($data);
        
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

    private function validateAnswerData(array $data): void
    {
        $constraints = new Assert\Collection([
            'answer' => [
                new Assert\NotBlank(['message' => 'La réponse est requise']),
                new Assert\Length(['max' => 255, 'maxMessage' => 'La réponse ne peut pas dépasser 255 caractères'])
            ],
            'is_correct' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'bool', 'message' => 'Le champ is_correct doit être un booléen'])
                ])
            ],
            'order_correct' => [
                new Assert\Optional([
                    new Assert\Length(['max' => 50, 'maxMessage' => 'L\'ordre correct ne peut pas dépasser 50 caractères'])
                ])
            ],
            'pair_id' => [
                new Assert\Optional([
                    new Assert\Length(['max' => 20, 'maxMessage' => 'L\'ID de paire ne peut pas dépasser 20 caractères'])
                ])
            ],
            'is_intrus' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'bool', 'message' => 'Le champ is_intrus doit être un booléen'])
                ])
            ],
            'question_id' => [
                new Assert\NotBlank(['message' => 'L\'ID de la question est requis']),
                new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de la question doit être un entier'])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
