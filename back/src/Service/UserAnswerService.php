<?php

namespace App\Service;

use App\Entity\UserAnswer;
use App\Entity\QuizRating;
use App\Event\QuizCompletedEvent;
use App\Repository\UserAnswerRepository;
use App\Repository\QuizRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class UserAnswerService
{
    private EntityManagerInterface $em;
    private UserAnswerRepository $userAnswerRepository;
    private QuizRatingRepository $quizRatingRepository;
    private UserService $userService;
    private QuizService $quizService;
    private EventDispatcherInterface $eventDispatcher;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, UserAnswerRepository $userAnswerRepository, QuizRatingRepository $quizRatingRepository, UserService $userService, QuizService $quizService, EventDispatcherInterface $eventDispatcher, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->quizRatingRepository = $quizRatingRepository;
        $this->userService = $userService;
        $this->quizService = $quizService;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->userAnswerRepository->findAll();
    }

    public function create(array $data): UserAnswer
    {
        $this->validateUserAnswerData($data);
        
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
        $this->validateUserAnswerData($data);
        
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

    public function saveGameResult(array $data): UserAnswer
    {
        $this->validateGameResultData($data);
        
        if (!isset($data['user']) || !isset($data['quiz_id']) || !isset($data['total_score'])) {
            throw new \InvalidArgumentException('Missing required data: user, quiz_id, total_score');
        }

        $user = $data['user'];
        $quiz = $this->quizService->find($data['quiz_id']);
        
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $userAnswer = new UserAnswer();
        $userAnswer->setUser($user);
        $userAnswer->setQuiz($quiz);
        $userAnswer->setTotalScore($data['total_score']);
        $userAnswer->setDateAttempt(new \DateTime());

        $this->em->persist($userAnswer);
        $this->em->flush();

        $event = new QuizCompletedEvent($userAnswer, $user);
        $this->eventDispatcher->dispatch($event, QuizCompletedEvent::NAME);

        return $userAnswer;
    }

    public function rateQuiz(array $data): array
    {
        if (!isset($data['user']) || !isset($data['quizId']) || !isset($data['rating'])) {
            throw new \InvalidArgumentException('Missing required data: user, quizId, rating');
        }

        $user = $data['user'];
        $quiz = $this->quizService->find($data['quizId']);
        
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $existingRating = $this->quizRatingRepository->findUserRatingForQuiz($user->getId(), $data['quizId']);
        
        if ($existingRating) {
            $existingRating->setRating($data['rating']);
            $existingRating->setRatedAt(new \DateTime());
        } else {
            $quizRating = new QuizRating();
            $quizRating->setUser($user);
            $quizRating->setQuiz($quiz);
            $quizRating->setRating($data['rating']);
            $quizRating->setRatedAt(new \DateTime());
            $this->em->persist($quizRating);
        }
        
        $this->em->flush();

        $avgRating = $this->quizRatingRepository->findAverageRatingForQuiz($data['quizId']);
        $totalRatings = $this->quizRatingRepository->countRatingsForQuiz($data['quizId']);

        return [
            'success' => true,
            'averageRating' => $avgRating,
            'totalRatings' => $totalRatings
        ];
    }





    public function getQuizRating(int $quizId, $currentUser = null): array
    {
        $quiz = $this->quizService->find($quizId);
        if (!$quiz) {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $avgRating = $this->quizRatingRepository->findAverageRatingForQuiz($quizId);
        $totalRatings = $this->quizRatingRepository->countRatingsForQuiz($quizId);
        
        $userRating = null;
        if ($currentUser) {
            $userRatingEntity = $this->quizRatingRepository->findUserRatingForQuiz($currentUser->getId(), $quizId);
            $userRating = $userRatingEntity ? $userRatingEntity->getRating() : null;
        }

        return [
            'averageRating' => $avgRating,
            'totalRatings' => $totalRatings,
            'userRating' => $userRating
        ];
    }

    private function validateUserAnswerData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'total_score' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'Le score total doit être un entier'])
                    ])
                ],
                'user_id' => [
                    new Assert\NotBlank(['message' => 'L\'ID de l\'utilisateur est requis']),
                    new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de l\'utilisateur doit être un entier'])
                ],
                'quiz_id' => [
                    new Assert\NotBlank(['message' => 'L\'ID du quiz est requis']),
                    new Assert\Type(['type' => 'integer', 'message' => 'L\'ID du quiz doit être un entier'])
                ],
                'question_id' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de la question doit être un entier'])
                    ])
                ],
                'answer' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 1000, 'maxMessage' => 'La réponse ne peut pas dépasser 1000 caractères'])
                    ])
                ],
                'score' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'Le score doit être un entier'])
                    ])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    private function validateGameResultData(array $data): void
    {
        $constraints = new Assert\Collection([
            'allowExtraFields' => true,
            'fields' => [
                'total_score' => [
                    new Assert\NotBlank(['message' => 'Le score total est requis']),
                    new Assert\Type(['type' => 'integer', 'message' => 'Le score total doit être un entier'])
                ],
                'quiz_id' => [
                    new Assert\NotBlank(['message' => 'L\'ID du quiz est requis']),
                    new Assert\Type(['type' => 'integer', 'message' => 'L\'ID du quiz doit être un entier'])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
