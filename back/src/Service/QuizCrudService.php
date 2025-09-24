<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\QuizRating;
use App\Entity\TypeQuestion;
use App\Entity\User;
use App\Enum\Difficulty;
use App\Enum\Status;
use App\Event\QuizCreatedEvent;
use App\Repository\CategoryQuizRepository;
use App\Repository\GroupRepository;
use App\Repository\QuizRepository;
use App\Repository\TypeQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class QuizCrudService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly QuizRepository $quizRepository, private readonly CategoryQuizRepository $categoryQuizRepository, private readonly TypeQuestionRepository $typeQuestionRepository, private readonly GroupRepository $groupRepository, private readonly ValidatorInterface $validator, private readonly LoggerInterface $logger, private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * Affiche un quiz avec tous ses détails.
     *
     * @param Quiz      $quiz Le quiz à afficher
     * @param User|null $user L'utilisateur qui demande l'affichage
     *
     * @return Quiz Quiz avec toutes ses relations chargées
     */
    public function show(Quiz $quiz, ?User $user = null): Quiz
    {
        if ($user) {
            $accessibleQuiz = $this->quizRepository->findWithUserAccess($quiz->getId(), $user);
            if (!$accessibleQuiz) {
                $accessibleQuiz = $this->quizRepository->findWithAllRelations($quiz->getId());
                if (!$accessibleQuiz) {
                    throw new \InvalidArgumentException('Quiz non accessible pour cet utilisateur');
                }
            }
            $quiz = $accessibleQuiz;
        } else {
            $quiz = $this->quizRepository->findWithAllRelations($quiz->getId()) ?? $quiz;
            if (!$quiz->isPublic()) {
                throw new \InvalidArgumentException('Accès refusé : ce quiz n\'est pas public');
            }
        }

        // Force loading des questions et réponses
        foreach ($quiz->getQuestions() as $question) {
            $question->getAnswers()->toArray();
        }

        return $quiz;
    }

    /**
     * Trouve un quiz par son ID.
     *
     * @param int $id L'ID du quiz
     *
     * @return Quiz|null Le quiz trouvé ou null
     */
    public function find(int $id): ?Quiz
    {
        return $this->quizRepository->find($id);
    }

    /**
     * Supprime un quiz et toutes ses données associées.
     *
     * @param Quiz $quiz Le quiz à supprimer
     *
     * @throws \InvalidArgumentException Si l'utilisateur n'a pas les droits
     */
    public function delete(Quiz $quiz): void
    {
        $this->em->beginTransaction();

        try {
            foreach ($quiz->getUserAnswers() as $userAnswer) {
                $this->em->remove($userAnswer);
            }

            $quizRatings = $this->em->getRepository(QuizRating::class)->findBy(['quiz' => $quiz]);
            foreach ($quizRatings as $rating) {
                $this->em->remove($rating);
            }

            foreach ($quiz->getQuestions() as $question) {
                foreach ($question->getAnswers() as $answer) {
                    $this->em->remove($answer);
                }
                $this->em->remove($question);
            }

            $this->em->remove($quiz);
            $this->em->flush();

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erreur lors de la suppression du quiz: '.$e->getMessage());
            throw new \InvalidArgumentException('Erreur lors de la suppression du quiz');
        }
    }

    /**
     * Crée un nouveau quiz avec ses questions.
     *
     * @param array $data Les données du quiz (déjà validées par le contrôleur)
     * @param User  $user L'utilisateur créateur
     *
     * @return Quiz Le quiz créé
     */
    public function createWithQuestions(array $data, User $user): Quiz
    {
        // Validation complète des données avant traitement
        $this->validateQuizData($data);

        $this->em->beginTransaction();

        try {
            $quiz = new Quiz();
            $quiz->setTitle($data['title']);
            $quiz->setDescription($data['description']);
            $quiz->setStatus(Status::from($data['status'] ?? 'draft'));
            $quiz->setIsPublic($data['isPublic'] ?? false);
            $quiz->setDateCreation(new \DateTimeImmutable());
            $quiz->setUser($user);

            if (isset($data['category_id']) && is_numeric($data['category_id'])) {
                $category = $this->categoryQuizRepository->find($data['category_id']);
                if ($category) {
                    $quiz->setCategory($category);
                }
            }

            if (!$quiz->isPublic() && isset($data['groups']) && is_array($data['groups'])) {
                $userCompany = $user->getCompany();
                if ($userCompany) {
                    foreach ($data['groups'] as $groupId) {
                        if (!is_numeric($groupId) || $groupId <= 0) {
                            continue;
                        }

                        $group = $this->groupRepository->find($groupId);
                        if ($group && $group->getCompany() === $userCompany) {
                            $quiz->addGroup($group);
                        }
                    }
                }
            }

            // Persister le quiz
            $this->em->persist($quiz);

            // Créer les questions et réponses
            if (isset($data['questions']) && is_array($data['questions']) && !empty($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $this->createQuestion($quiz, $questionData);
                }
            }

            $this->em->flush();
            $this->em->commit();

            $event = new QuizCreatedEvent($quiz, $user);
            $this->eventDispatcher->dispatch($event, QuizCreatedEvent::NAME);

            return $quiz;
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erreur création quiz: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour un quiz existant avec ses questions.
     *
     * @param Quiz  $quiz Le quiz à mettre à jour
     * @param array $data Les nouvelles données
     * @param User  $user L'utilisateur effectuant la modification
     *
     * @return Quiz Le quiz mis à jour
     */
    public function updateWithQuestions(Quiz $quiz, array $data, User $user): Quiz
    {
        if (isset($data['title'])) {
            $quiz->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $quiz->setDescription($data['description']);
        }
        // Note: difficulty is calculated from questions, not set directly on quiz
        if (isset($data['status'])) {
            $quiz->setStatus(Status::from($data['status']));
        }
        if (isset($data['isPublic'])) {
            $quiz->setIsPublic($data['isPublic']);
        }

        if (isset($data['category']) && is_numeric($data['category'])) {
            $category = $this->categoryQuizRepository->find($data['category']);
            if ($category) {
                $quiz->setCategory($category);
            }
        }

        if (isset($data['groups']) && is_array($data['groups'])) {
            $quiz->getGroups()->clear();

            if (!$quiz->isPublic()) {
                $userCompany = $user->getCompany();
                if ($userCompany) {
                    foreach ($data['groups'] as $groupId) {
                        if (!is_numeric($groupId) || $groupId <= 0) {
                            continue;
                        }

                        $group = $this->groupRepository->find($groupId);
                        if ($group && $group->getCompany() === $userCompany) {
                            $quiz->addGroup($group);
                        }
                    }
                }
            }
        }

        if (isset($data['questions']) && is_array($data['questions'])) {
            try {
                $this->updateQuizQuestions($quiz, $data['questions']);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $this->em->flush();

        return $quiz;
    }

    /**
     * Récupère un quiz pour l'édition avec toutes ses relations.
     *
     * @param Quiz $quiz Le quiz à préparer pour l'édition
     * @param User $user L'utilisateur demandant l'édition
     *
     * @return Quiz Quiz avec toutes ses relations chargées pour l'édition
     */
    public function getQuizForEdit(Quiz $quiz, User $user): Quiz
    {
        try {
            $fullQuiz = $this->quizRepository->findWithAllRelations($quiz->getId());

            if (!$fullQuiz) {
                throw new \InvalidArgumentException('Quiz non trouvé');
            }

            foreach ($fullQuiz->getQuestions() as $question) {
                $question->getAnswers()->toArray();
                $question->getTypeQuestion();
            }

            return $fullQuiz;
        } catch (\Exception $e) {
            $this->logger->error('Erreur getQuizForEdit: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Valide les données d'un quiz.
     *
     * @param array $data Les données à valider
     *
     * @throws ValidationFailedException Si les données sont invalides
     */
    private function validateQuizData(array $data): void
    {
        $constraints = new Assert\Collection([
            'title' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 255])],
            'description' => [new Assert\NotBlank(), new Assert\Length(['min' => 10])],
            'status' => [new Assert\NotBlank()],
            'isPublic' => [new Assert\Type('bool')],
            'category_id' => new Assert\Optional([new Assert\Type('numeric'), new Assert\GreaterThan(0)]),
            'groups' => new Assert\Optional([new Assert\Type('array')]),
            'questions' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\Count(['min' => 1, 'minMessage' => 'Au moins une question est requise']),
            ]),
        ]);

        $violations = $this->validator->validate($data, $constraints);
        if (count($violations) > 0) {
            throw new ValidationFailedException($data, $violations);
        }

        // Validation des questions si elles sont présentes
        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $index => $questionData) {
                $this->validateQuestionData($questionData, $index);
            }
        }
    }

    /**
     * Valide les données d'une question.
     */
    private function validateQuestionData(array $questionData, int $index): void
    {
        $constraints = new Assert\Collection([
            'question' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 500])],
            'difficulty' => new Assert\Optional([new Assert\Choice(['choices' => ['easy', 'medium', 'hard']])]),
            'type_question' => new Assert\Optional([new Assert\Type('numeric')]),
            'answers' => [
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\Count(['min' => 2, 'minMessage' => 'Au moins 2 réponses sont requises par question']),
            ],
        ]);

        $violations = $this->validator->validate($questionData, $constraints);
        if (count($violations) > 0) {
            throw new ValidationFailedException($questionData, $violations);
        }

        // Validation des réponses
        if (isset($questionData['answers']) && is_array($questionData['answers'])) {
            foreach ($questionData['answers'] as $answerIndex => $answerData) {
                $this->validateAnswerData($answerData, $index, $answerIndex);
            }
        }
    }

    /**
     * Valide les données d'une réponse.
     */
    private function validateAnswerData(array $answerData, int $questionIndex, int $answerIndex): void
    {
        $constraints = new Assert\Collection([
            'answer' => [new Assert\NotBlank(), new Assert\Length(['min' => 1, 'max' => 255])],
            'is_correct' => new Assert\Optional([new Assert\Type('bool')]),
            'order_correct' => new Assert\Optional([new Assert\Type('string')]),
            'pair_id' => new Assert\Optional([new Assert\Type('string')]),
            'is_intrus' => new Assert\Optional([new Assert\Type('bool')]),
        ]);

        $violations = $this->validator->validate($answerData, $constraints);
        if (count($violations) > 0) {
            throw new ValidationFailedException($answerData, $violations);
        }
    }

    /**
     * Crée une question pour un quiz.
     */
    private function createQuestion(Quiz $quiz, array $questionData): Question
    {
        $this->logger->info('DEBUG createQuestion', [
            'questionData' => $questionData,
            'type_question_value' => $questionData['type_question'] ?? 'NOT_SET',
            'type_question_type' => gettype($questionData['type_question'] ?? null),
        ]);

        $typeQuestion = $this->getTypeQuestionFromData($questionData);

        $this->logger->info('DEBUG TypeQuestion trouvé', [
            'typeQuestion_id' => $typeQuestion->getId(),
            'typeQuestion_name' => $typeQuestion->getName(),
        ]);

        $question = new Question();
        $question->setQuestion($questionData['question']);
        $question->setQuiz($quiz);
        $question->setTypeQuestion($typeQuestion);

        if (isset($questionData['difficulty'])) {
            $difficulty = Difficulty::tryFrom($questionData['difficulty']);
            if (null !== $difficulty) {
                $question->setDifficulty($difficulty);
            }
        }

        $this->em->persist($question);
        $this->em->flush();

        foreach ($questionData['answers'] as $answerData) {
            $this->createAnswer($question, $answerData);
        }

        $this->em->flush();

        return $question;
    }

    /**
     * Crée une réponse pour une question.
     */
    private function createAnswer(Question $question, array $answerData): Answer
    {
        $this->logger->info('DEBUG createAnswer', [
            'answerData' => $answerData,
            'is_correct' => $answerData['is_correct'] ?? 'NOT_SET',
        ]);

        $answer = new Answer();
        $answer->setAnswer($answerData['answer']);
        $answer->setIsCorrect($answerData['is_correct'] ?? false);
        $answer->setQuestion($question);

        if (!empty($answerData['order_correct'])) {
            $answer->setOrderCorrect($answerData['order_correct']);
        }

        if (!empty($answerData['pair_id'])) {
            $answer->setPairId($answerData['pair_id']);
        }

        if (isset($answerData['is_intrus'])) {
            $answer->setIsIntrus($answerData['is_intrus']);
        }

        $this->em->persist($answer);

        return $answer;
    }

    /**
     * Récupère ou crée le type de question à partir des données.
     */
    private function getTypeQuestionFromData(array $questionData): TypeQuestion
    {
        // Le frontend envoie type_question avec l'ID numérique
        if (isset($questionData['type_question']) && is_numeric($questionData['type_question'])) {
            $typeQuestion = $this->typeQuestionRepository->find($questionData['type_question']);
            if ($typeQuestion) {
                return $typeQuestion;
            }
        }

        // Fallback pour l'ancien format avec type_question_id
        if (isset($questionData['type_question_id']) && is_numeric($questionData['type_question_id'])) {
            $typeQuestion = $this->typeQuestionRepository->find($questionData['type_question_id']);
            if ($typeQuestion) {
                return $typeQuestion;
            }
        }

        // Fallback pour les noms de types
        if (isset($questionData['type_question']) && is_string($questionData['type_question'])) {
            return $this->findOrCreateTypeQuestion($questionData['type_question']);
        }

        $defaultType = $this->typeQuestionRepository->findOneBy(['name' => 'QCM']);
        if (!$defaultType) {
            $defaultType = new TypeQuestion();
            $defaultType->setName('QCM');
            $this->em->persist($defaultType);
        }

        return $defaultType;
    }

    /**
     * Trouve ou crée un type de question par son nom.
     */
    private function findOrCreateTypeQuestion(string $name): TypeQuestion
    {
        $typeQuestion = $this->typeQuestionRepository->findOneBy(['name' => $name]);

        if (!$typeQuestion) {
            $typeQuestion = new TypeQuestion();
            $typeQuestion->setName($name);
            $this->em->persist($typeQuestion);
        }

        return $typeQuestion;
    }

    /**
     * Met à jour les questions d'un quiz.
     */
    private function updateQuizQuestions(Quiz $quiz, array $questionsData): void
    {
        $existingQuestions = $quiz->getQuestions()->toArray();
        foreach ($existingQuestions as $existingQuestion) {
            $existingAnswers = $existingQuestion->getAnswers()->toArray();
            foreach ($existingAnswers as $answer) {
                $this->em->remove($answer);
            }
            $this->em->remove($existingQuestion);
        }

        $quiz->getQuestions()->clear();
        $this->em->flush();

        foreach ($questionsData as $questionData) {
            $this->createQuestion($quiz, $questionData);
        }
    }
}
