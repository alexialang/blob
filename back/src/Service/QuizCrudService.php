<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Question;
use App\Entity\Answer;
use App\Enum\Status;
use App\Enum\Difficulty;
use App\Event\QuizCreatedEvent;
use App\Repository\CategoryQuizRepository;
use App\Repository\GroupRepository;
use App\Repository\QuizRepository;
use App\Repository\TypeQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use App\Entity\TypeQuestion;

class QuizCrudService
{
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;
    private CategoryQuizRepository $categoryQuizRepository;
    private TypeQuestionRepository $typeQuestionRepository;
    private GroupRepository $groupRepository;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        CategoryQuizRepository $categoryQuizRepository,
        TypeQuestionRepository $typeQuestionRepository,
        GroupRepository $groupRepository,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->quizRepository = $quizRepository;
        $this->categoryQuizRepository = $categoryQuizRepository;
        $this->typeQuestionRepository = $typeQuestionRepository;
        $this->groupRepository = $groupRepository;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Affiche un quiz avec tous ses détails
     * 
     * @param Quiz $quiz Le quiz à afficher
     * @param User|null $user L'utilisateur qui demande l'affichage
     * @return array Quiz formaté pour l'affichage
     */
    public function show(Quiz $quiz, ?User $user = null): array
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
        }

        $quizData = [
            'id' => $quiz->getId(),
            'title' => $quiz->getTitle(),
            'description' => $quiz->getDescription(),
            'status' => $quiz->getStatus()->value,
            'isPublic' => $quiz->isPublic(),
            'dateCreation' => $quiz->getDateCreation()?->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $quiz->getUser()->getId(),
                'firstName' => $quiz->getUser()->getFirstName(),
                'lastName' => $quiz->getUser()->getLastName()
            ],
            'category' => $quiz->getCategory() ? [
                'id' => $quiz->getCategory()->getId(),
                'name' => $quiz->getCategory()->getName()
            ] : null,
            'groups' => array_map(function($group) {
                return [
                    'id' => $group->getId(),
                    'name' => $group->getName()
                ];
            }, $quiz->getGroups()->toArray()),
            'questions' => []
        ];

        $this->logger->info('DEBUG show method', [
            'quiz_id' => $quiz->getId(),
            'questions_count' => $quiz->getQuestions()->count(),
            'questions_loaded' => $quiz->getQuestions()->isInitialized()
        ]);

        foreach ($quiz->getQuestions() as $question) {
            $questionData = [
                'id' => $question->getId(),
                'question' => $question->getQuestion(),
                'type_question' => $question->getTypeQuestion() ? [
                    'id' => $question->getTypeQuestion()->getId(),
                    'name' => $question->getTypeQuestion()->getName()
                ] : null,
                'difficulty' => $question->getDifficulty()?->value,
                'answers' => []
            ];

            $this->logger->info('DEBUG question', [
                'question_id' => $question->getId(),
                'answers_count' => $question->getAnswers()->count(),
                'answers_loaded' => $question->getAnswers()->isInitialized()
            ]);

            foreach ($question->getAnswers() as $answer) {
                $questionData['answers'][] = [
                    'id' => $answer->getId(),
                    'answer' => $answer->getAnswer(),
                    'is_correct' => $answer->isCorrect(),
                    'order_correct' => $answer->getOrderCorrect(),
                    'pair_id' => $answer->getPairId(),
                    'is_intrus' => $answer->isIntrus()
                ];
            }

            $quizData['questions'][] = $questionData;
        }

        return $quizData;
    }

    /**
     * Trouve un quiz par son ID
     * 
     * @param int $id L'ID du quiz
     * @return Quiz|null Le quiz trouvé ou null
     */
    public function find(int $id): ?Quiz
    {
        return $this->quizRepository->find($id);
    }

    /**
     * Supprime un quiz et toutes ses données associées
     * 
     * @param Quiz $quiz Le quiz à supprimer
     * @throws \InvalidArgumentException Si l'utilisateur n'a pas les droits
     */
    public function delete(Quiz $quiz): void
    {
        try {
            foreach ($quiz->getQuestions() as $question) {
                foreach ($question->getAnswers() as $answer) {
                    $this->em->remove($answer);
                }
                $this->em->remove($question);
            }

            $this->em->remove($quiz);
            $this->em->flush();

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression du quiz: ' . $e->getMessage());
            throw new \InvalidArgumentException('Erreur lors de la suppression du quiz');
        }
    }

    /**
     * Crée un nouveau quiz avec ses questions
     * 
     * @param array $data Les données du quiz
     * @param User $user L'utilisateur créateur
     * @return Quiz Le quiz créé
     */
    public function createWithQuestions(array $data, User $user): Quiz
    {
        $this->validateQuizData($data);

        $this->em->beginTransaction();

        try {
            $quiz = new Quiz();
            $quiz->setTitle($data['title']);
            $quiz->setDescription($data['description']);
            $quiz->setStatus(Status::from($data['status']));
            $quiz->setIsPublic($data['isPublic'] ?? false);
            $quiz->setDateCreation(new \DateTimeImmutable());
            $quiz->setUser($user);

            if (isset($data['category']) && is_numeric($data['category'])) {
                $category = $this->categoryQuizRepository->find($data['category']);
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
            $this->logger->error('Erreur création quiz: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour un quiz existant avec ses questions
     * 
     * @param Quiz $quiz Le quiz à mettre à jour
     * @param array $data Les nouvelles données
     * @param User $user L'utilisateur effectuant la modification
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
        if (isset($data['difficulty'])) {
            $quiz->setDifficulty(Difficulty::from($data['difficulty']));
        }
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
            $this->updateQuizQuestions($quiz, $data['questions']);
        }

        $this->em->flush();
        return $quiz;
    }

    /**
     * Récupère un quiz pour l'édition avec toutes ses relations
     * 
     * @param Quiz $quiz Le quiz à préparer pour l'édition
     * @param User $user L'utilisateur demandant l'édition
     * @return array Quiz formaté pour l'édition
     */
    public function getQuizForEdit(Quiz $quiz, User $user): array
    {
        try {
            $fullQuiz = $this->quizRepository->findWithAllRelations($quiz->getId());
            
            if (!$fullQuiz) {
                throw new \InvalidArgumentException('Quiz non trouvé');
            }

            // Debug temporaire
            $this->logger->info('DEBUG getQuizForEdit', [
                'quiz_id' => $fullQuiz->getId(),
                'quiz_title' => $fullQuiz->getTitle(),
                'questions_count' => $fullQuiz->getQuestions()->count()
            ]);

            $quizData = $this->transformQuizForFrontend($fullQuiz, $user);

            // Debug données transformées
            $this->logger->info('DEBUG quiz transformé', [
                'data_keys' => array_keys($quizData),
                'title' => $quizData['title'] ?? 'VIDE',
                'questions_count' => count($quizData['questions'] ?? [])
            ]);

            return $quizData;
        } catch (\Exception $e) {
            $this->logger->error('Erreur getQuizForEdit: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valide les données d'un quiz
     * 
     * @param array $data Les données à valider
     * @throws ValidationFailedException Si les données sont invalides
     */
    private function validateQuizData(array $data): void
    {
        $constraints = new Assert\Collection([
            'title' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 255])],
            'description' => [new Assert\NotBlank(), new Assert\Length(['min' => 10])],
            'status' => [new Assert\NotBlank()],
            'isPublic' => [new Assert\Type('bool')],
            'category' => new Assert\Optional([new Assert\Type('numeric')]),
            'groups' => new Assert\Optional([new Assert\Type('array')]),
            'questions' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\Count(['min' => 1, 'minMessage' => 'Au moins une question est requise'])
            ])
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
     * Valide les données d'une question
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
                new Assert\Count(['min' => 2, 'minMessage' => 'Au moins 2 réponses sont requises par question'])
            ]
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
     * Valide les données d'une réponse
     */
    private function validateAnswerData(array $answerData, int $questionIndex, int $answerIndex): void
    {
        $constraints = new Assert\Collection([
            'answer' => [new Assert\NotBlank(), new Assert\Length(['min' => 1, 'max' => 255])],
            'is_correct' => new Assert\Optional([new Assert\Type('bool')]),
            'order_correct' => new Assert\Optional([new Assert\Type('string')]),
            'pair_id' => new Assert\Optional([new Assert\Type('string')]),
            'is_intrus' => new Assert\Optional([new Assert\Type('bool')])
        ]);

        $violations = $this->validator->validate($answerData, $constraints);
        if (count($violations) > 0) {
            throw new ValidationFailedException($answerData, $violations);
        }
    }

    /**
     * Crée une question pour un quiz
     */
    private function createQuestion(Quiz $quiz, array $questionData): Question
    {
        $typeQuestion = $this->getTypeQuestionFromData($questionData);

        $question = new Question();
        $question->setQuestion($questionData['question']);
        $question->setQuiz($quiz);
        $question->setTypeQuestion($typeQuestion);

        if (isset($questionData['difficulty'])) {
            $difficulty = Difficulty::tryFrom($questionData['difficulty']);
            if ($difficulty !== null) {
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
     * Crée une réponse pour une question
     */
    private function createAnswer(Question $question, array $answerData): Answer
    {
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
     * Récupère ou crée le type de question à partir des données
     */
    private function getTypeQuestionFromData(array $questionData): TypeQuestion
    {
        if (isset($questionData['type_question_id']) && is_numeric($questionData['type_question_id'])) {
            $typeQuestion = $this->typeQuestionRepository->find($questionData['type_question_id']);
            if ($typeQuestion) {
                return $typeQuestion;
            }
        }

        if (isset($questionData['type_question']) && is_string($questionData['type_question'])) {
            return $this->findOrCreateTypeQuestion($questionData['type_question']);
        }

        // Type de question par défaut si aucun n'est spécifié
        $defaultType = $this->typeQuestionRepository->findOneBy(['name' => 'QCM']);
        if (!$defaultType) {
            $defaultType = new TypeQuestion();
            $defaultType->setName('QCM');
            $this->em->persist($defaultType);
        }

        return $defaultType;
    }

    /**
     * Trouve ou crée un type de question par son nom
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
     * Met à jour les questions d'un quiz
     */
    private function updateQuizQuestions(Quiz $quiz, array $questionsData): void
    {
        // Supprimer toutes les questions et réponses existantes
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

        // Créer les nouvelles questions et réponses
        foreach ($questionsData as $questionData) {
            $this->createQuestion($quiz, $questionData);
        }
    }

    /**
     * Transforme un quiz pour le frontend
     */
    private function transformQuizForFrontend(Quiz $quiz, User $user): array
    {
        $quizData = [
            'id' => $quiz->getId(),
            'title' => $quiz->getTitle(),
            'description' => $quiz->getDescription(),
            'status' => $quiz->getStatus()?->value,
            'isPublic' => $quiz->isPublic(),
            'dateCreation' => $quiz->getDateCreation()?->format('Y-m-d H:i:s'),
            'category' => $quiz->getCategory() ? [
                'id' => $quiz->getCategory()->getId(),
                'name' => $quiz->getCategory()->getName()
            ] : null,
            'groups' => [],
            'questions' => []
        ];

        foreach ($quiz->getGroups() as $group) {
            $quizData['groups'][] = [
                'id' => $group->getId(),
                'name' => $group->getName()
            ];
        }

        foreach ($quiz->getQuestions() as $question) {
            $questionData = [
                'id' => $question->getId(),
                'question' => $question->getQuestion(),
                'type_question' => $question->getTypeQuestion() ? [
                    'id' => $question->getTypeQuestion()->getId(),
                    'name' => $question->getTypeQuestion()->getName()
                ] : null,
                'difficulty' => $question->getDifficulty()?->value,
                'points' => 10,
                'answers' => []
            ];

            foreach ($question->getAnswers() as $answer) {
                $questionData['answers'][] = [
                    'id' => $answer->getId(),
                    'answer' => $answer->getAnswer(),
                    'is_correct' => $answer->isCorrect(),
                    'order_correct' => $answer->getOrderCorrect(),
                    'pair_id' => $answer->getPairId(),
                    'is_intrus' => $answer->isIntrus()
                ];
            }

            $quizData['questions'][] = $questionData;
        }

        return $quizData;
    }
}
