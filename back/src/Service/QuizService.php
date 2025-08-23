<?php
namespace App\Service;

use App\Entity\Quiz;
use App\Entity\QuizRating;
use App\Entity\User;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\TypeQuestion;
use App\Enum\Status;
use App\Enum\Difficulty;
use App\Event\QuizCreatedEvent;
use App\Repository\CategoryQuizRepository;
use App\Repository\GroupRepository;
use App\Repository\QuizRepository;
use App\Repository\QuizRatingRepository;
use App\Repository\TypeQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class QuizService
{
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;
    private CategoryQuizRepository $categoryQuizRepository;
    private TypeQuestionRepository $typeQuestionRepository;
    private GroupRepository $groupRepository;
    private QuizRatingRepository $quizRatingRepository;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        CategoryQuizRepository $categoryQuizRepository,
        TypeQuestionRepository $typeQuestionRepository,
        GroupRepository $groupRepository,
        QuizRatingRepository $quizRatingRepository,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->quizRepository = $quizRepository;
        $this->categoryQuizRepository = $categoryQuizRepository;
        $this->typeQuestionRepository = $typeQuestionRepository;
        $this->groupRepository = $groupRepository;
        $this->quizRatingRepository = $quizRatingRepository;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function list(bool $forManagement = false): array
    {
        return $this->quizRepository->findPublishedOrAll($forManagement);
    }


    public function getQuizzesForCompanyManagement(User $user): array
    {
        try {
            $this->logger->info('getQuizzesForCompanyManagement appelé pour utilisateur: ' . $user->getId());
            
            if ($user->isAdmin()) {
                $this->logger->info('Utilisateur admin - récupération de tous les quiz');
                $quizzes = $this->quizRepository->findAll();
            } else {
                $this->logger->info('Utilisateur non-admin - récupération et filtrage');
                $allQuizzes = $this->quizRepository->findAll();
                $quizzes = [];
                
                foreach ($allQuizzes as $quiz) {
                    if ($quiz->getUser() && $quiz->getUser()->getId() === $user->getId()) {
                        $quizzes[] = $quiz;
                        continue;
                    }
                    
                    if ($user->getCompany() && $quiz->getCompany() &&
                        $quiz->getCompany()->getId() === $user->getCompany()->getId()) {
                        $quizzes[] = $quiz;
                        continue;
                    }
                    
                    if ($quiz->isPublic()) {
                        $quizzes[] = $quiz;
                        continue;
                    }
                }
            }

            $this->logger->info('Nombre de quiz trouvés: ' . count($quizzes));

            $quizList = [];
            foreach ($quizzes as $quiz) {
                $quizList[] = [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'description' => $quiz->getDescription(),
                    'status' => $quiz->getStatus()?->value,
                    'isPublic' => $quiz->isPublic(),
                    'dateCreation' => $quiz->getDateCreation()?->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $quiz->getUser()?->getId(),
                        'firstName' => $quiz->getUser()?->getFirstName(),
                        'lastName' => $quiz->getUser()?->getLastName(),
                        'email' => $quiz->getUser()?->getEmail()
                    ],
                    'company' => $quiz->getCompany() ? [
                        'id' => $quiz->getCompany()->getId(),
                        'name' => $quiz->getCompany()->getName()
                    ] : null,
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
                    'questionCount' => $quiz->getQuestions()->count(),
                    'canModify' => $this->canUserModifyQuiz($user, $quiz)
                ];
            }

            $this->logger->info('Quiz transformés: ' . count($quizList));
            return $quizList;
        } catch (\Exception $e) {
            $this->logger->error('Erreur getQuizzesForCompanyManagement: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    public function getPrivateQuizzesForUser(User $user): array
    {
        try {
            if (!$user->getCompany()) {
                return [];
            }
            
            $userGroups = $user->getGroups();
            
            if ($userGroups->isEmpty()) {
                return [];
            }

            $userGroupIds = [];
            foreach ($userGroups as $group) {
                $userGroupIds[] = $group->getId();
            }

            $privateQuizzes = $this->quizRepository->findPrivateQuizzesForUserGroups($userGroupIds);
            
            return $privateQuizzes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur getPrivateQuizzesForUser: ' . $e->getMessage());
            return [];
        }
    }

    public function getMyQuizzes(User $user): array
    {
        try {
            return $this->quizRepository->findByUser($user);
        } catch (\Exception $e) {
            $this->logger->error('Erreur getMyQuizzes: ' . $e->getMessage());
            return [];
        }
    }

    public function getMostPopularQuizzes(int $limit = 8): array
    {
        return $this->quizRepository->findMostPopular($limit);
    }

    public function getMostRecentQuizzes(int $limit = 6): array
    {
        return $this->quizRepository->findMostRecent($limit);
    }

    public function show(Quiz $quiz, ?User $user = null): array
    {
        if ($user) {
            $accessibleQuiz = $this->quizRepository->findWithUserAccess($quiz->getId(), $user);
            if ($accessibleQuiz) {
                return $this->transformQuizForFrontend($accessibleQuiz, $user);
            }
        }

        if ($quiz->isPublic() && $quiz->getStatus() === Status::PUBLISHED) {
            return $this->transformQuizForFrontend($quiz, $user);
        }

        throw new \InvalidArgumentException('Quiz non accessible');
    }


    private function transformQuizForFrontend(Quiz $quiz, ?User $user = null): array
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

    public function find(int $id): ?Quiz
    {
        return $this->quizRepository->find($id);
    }

    public function delete(Quiz $quiz): void
    {

            
            $ratings = $this->em->getRepository(QuizRating::class)->findBy(['quiz' => $quiz]);
            foreach ($ratings as $rating) {
                $this->em->remove($rating);
            }

            foreach ($quiz->getQuestions() as $question) {
                foreach ($question->getAnswers() as $answer) {
                    $this->em->remove($answer);
                }
                $this->em->remove($question);
            }

            foreach ($quiz->getUserAnswers() as $userAnswer) {
                $this->em->remove($userAnswer);
            }

            $this->em->remove($quiz);
            $this->em->flush();

    }

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

            $this->em->persist($quiz);
            $this->em->flush();

            if (isset($data['questions']) && is_array($data['questions'])) {
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
            throw new BadRequestException('Erreur lors de la création du quiz: ' . $e->getMessage());
        }
    }

    private function validateQuizData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'title' => [
                    new Assert\NotBlank(['message' => 'Le titre est requis']),
                    new Assert\Length(['min' => 3, 'max' => 255, 'minMessage' => 'Le titre doit contenir au moins 3 caractères', 'maxMessage' => 'Le titre ne peut pas dépasser 255 caractères'])
                ],
                'description' => [
                    new Assert\NotBlank(['message' => 'La description est requise']),
                    new Assert\Length(['min' => 10, 'max' => 1000, 'minMessage' => 'La description doit contenir au moins 10 caractères', 'maxMessage' => 'La description ne peut pas dépasser 1000 caractères'])
                ],
                'status' => [
                    new Assert\NotBlank(['message' => 'Le statut est requis'])
                ],
                'isPublic' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'bool', 'message' => 'Le champ isPublic doit être un booléen'])
                    ])
                ],
                'category' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de la catégorie doit être un entier'])
                    ])
                ],
                'groups' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'array', 'message' => 'Les groupes doivent être un tableau'])
                    ])
                ],
                'questions' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'array', 'message' => 'Les questions doivent être un tableau'])
                    ])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }


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

    private function getTypeQuestionFromData(array $questionData): TypeQuestion
    {
        if (isset($questionData['type_question_id']) && is_numeric($questionData['type_question_id'])) {
            $typeQuestion = $this->em->getRepository(TypeQuestion::class)->find($questionData['type_question_id']);
            if ($typeQuestion) {
                return $typeQuestion;
            }
        }

        if (isset($questionData['type_question']) && is_string($questionData['type_question'])) {
            return $this->findOrCreateTypeQuestion($questionData['type_question']);
        }

        throw new BadRequestException('Type de question manquant ou invalide');
    }

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
    private function findOrCreateTypeQuestion(string $name): TypeQuestion
    {
        $typeQuestion = $this->em->getRepository(TypeQuestion::class)->findOneBy(['name' => $name]);

        if (!$typeQuestion) {
            $typeQuestion = new TypeQuestion();
            $typeQuestion->setName($name);
            $this->em->persist($typeQuestion);
        }

        return $typeQuestion;
    }

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

    public function getAverageRating(Quiz $quiz): array
    {
        $averageRating = $this->quizRatingRepository->findAverageRatingForQuiz($quiz->getId());
        $ratingCount = $this->quizRatingRepository->countRatingsForQuiz($quiz->getId());

        return [
            'averageRating' => $averageRating ?? 0,
            'ratingCount' => $ratingCount
        ];
    }

    private function canUserModifyQuiz(User $user, Quiz $quiz): bool
    {
        return $this->quizRepository->canUserModifyQuiz($quiz->getId(), $user);
    }

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
    public function getQuizForEdit(Quiz $quiz, User $user): array
    {
        try {

            $fullQuiz = $this->quizRepository->findWithAllRelations($quiz->getId());
            
            if (!$fullQuiz) {
                throw new \InvalidArgumentException('Quiz non trouvé');
            }

            $quizData = $this->transformQuizForFrontend($fullQuiz, $user);

            return $quizData;
        
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
