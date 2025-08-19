<?php
namespace App\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\TypeQuestion;
use App\Enum\Status;
use App\Enum\Difficulty;
use App\Enum\TypeQuestionName;
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


    public function getPrivateQuizzesForUser(User $user): array
    {
        try {
            $this->logger->info('=== DEBUG getPrivateQuizzesForUser ===');
            $this->logger->info('Utilisateur: ' . $user->getId() . ' (' . $user->getEmail() . ')');
            
            if (!$user->getCompany()) {
                $this->logger->info('PROBLÈME: Utilisateur sans entreprise');
                return [];
            }
            
            $this->logger->info('Entreprise: ' . $user->getCompany()->getName() . ' (ID: ' . $user->getCompany()->getId() . ')');

            $userGroups = $user->getGroups();
            $this->logger->info('Nombre de groupes utilisateur: ' . $userGroups->count());
            
            if ($userGroups->isEmpty()) {
                $this->logger->info('PROBLÈME: Utilisateur sans groupes');
                return [];
            }

            $userGroupIds = [];
            foreach ($userGroups as $group) {
                $userGroupIds[] = $group->getId();
                $this->logger->info('Groupe utilisateur: ' . $group->getName() . ' (ID: ' . $group->getId() . ')');
            }

            $allPrivateQuizzes = $this->quizRepository->findBy(['isPublic' => false, 'status' => Status::PUBLISHED]);
            $this->logger->info('Total quiz privés publiés: ' . count($allPrivateQuizzes));
            
            foreach ($allPrivateQuizzes as $quiz) {
                $quizGroups = $quiz->getGroups();
                $this->logger->info('Quiz "' . $quiz->getTitle() . '" (ID: ' . $quiz->getId() . ') a ' . $quizGroups->count() . ' groupes');
                foreach ($quizGroups as $group) {
                    $this->logger->info('  - Groupe: ' . $group->getName() . ' (ID: ' . $group->getId() . ')');
                }
            }

            $privateQuizzes = $this->quizRepository->findPrivateQuizzesForUserGroups($userGroupIds);
            $this->logger->info('Quiz privés trouvés pour l\'utilisateur: ' . count($privateQuizzes));
            
            foreach ($privateQuizzes as $quiz) {
                $this->logger->info('Quiz privé accessible: ' . $quiz->getTitle() . ' (ID: ' . $quiz->getId() . ')');
            }
            
            return $privateQuizzes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur getPrivateQuizzesForUser: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
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
        try {
            return $this->quizRepository->findMostPopular($limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur getMostPopularQuizzes: ' . $e->getMessage());
            return [];
        }
    }

    public function getMostRecentQuizzes(int $limit = 6): array
    {
        try {
            return $this->quizRepository->findMostRecent($limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur getMostRecentQuizzes: ' . $e->getMessage());
            return [];
        }
    }



    public function show(Quiz $quiz): Quiz
    {
        return $quiz;
    }

    public function find(int $id): ?Quiz
    {
        return $this->quizRepository->find($id);
    }

    public function delete(Quiz $quiz): void
    {
        try {
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
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression du quiz: ' . $e->getMessage());
        }
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
                    $maxGroups = 50;
                    $groupsToProcess = array_slice($data['groups'], 0, $maxGroups);

                    foreach ($groupsToProcess as $groupId) {
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

    private function validateQuestionData(array $questionData, int $index): void
    {
        if (empty($questionData['question'])) {
            throw new BadRequestException("La question #{$index} est requise");
        }

        if (isset($questionData['type_question_id']) && is_numeric($questionData['type_question_id'])) {
            $typeQuestion = $this->em->getRepository(TypeQuestion::class)->find($questionData['type_question_id']);
            if (!$typeQuestion) {
                throw new BadRequestException("Type de question avec l'ID {$questionData['type_question_id']} introuvable");
            }
        } elseif (isset($questionData['type_question']) && is_string($questionData['type_question'])) {
            $validTypes = array_column(TypeQuestionName::cases(), 'value');
            if (!in_array($questionData['type_question'], $validTypes)) {
                throw new BadRequestException("Type de question invalide : {$questionData['type_question']}");
            }
        } else {
            throw new BadRequestException("Type de question manquant (type_question ou type_question_id requis)");
        }

        if (empty($questionData['answers']) || !is_array($questionData['answers'])) {
            throw new BadRequestException("Les réponses pour la question #{$index} sont requises");
        }

        if (count($questionData['answers']) < 2) {
            throw new BadRequestException("Au moins 2 réponses sont requises pour la question #{$index}");
        }

        $this->validateAnswersByType($questionData, $index);
    }

    private function validateAnswersByType(array $questionData, int $index): void
    {
        $answers = $questionData['answers'];
        $type = $this->getQuestionTypeFromData($questionData);

        foreach ($answers as $answerIndex => $answerData) {
            if (empty($answerData['answer'])) {
                throw new BadRequestException("La réponse #{$answerIndex} de la question #{$index} ne peut pas être vide");
            }
        }

        switch ($type) {
            case 'find_the_intruder':
                $this->validateFindIntruderAnswers($answers, $index);
                break;
            case 'matching':
                $this->validateMatchingAnswers($answers, $index);
                break;
            case 'right_order':
                $this->validateRightOrderAnswers($answers, $index);
                break;
            case 'blind_test':
                break;
            default:
                $hasCorrectAnswer = false;
                foreach ($answers as $answerData) {
                    if (isset($answerData['is_correct']) && $answerData['is_correct']) {
                        $hasCorrectAnswer = true;
                        break;
                    }
                }
                if (!$hasCorrectAnswer) {
                    throw new BadRequestException("Au moins une réponse correcte est requise pour la question #{$index}");
                }
        }
    }

    private function getQuestionTypeFromData(array $questionData): string
    {
        if (isset($questionData['type_question_id']) && is_numeric($questionData['type_question_id'])) {
            $typeQuestion = $this->em->getRepository(TypeQuestion::class)->find($questionData['type_question_id']);
            if ($typeQuestion) {
                return $typeQuestion->getName();
            }
        }

        if (isset($questionData['type_question']) && is_string($questionData['type_question'])) {
            return $questionData['type_question'];
        }

        throw new BadRequestException('Type de question manquant ou invalide');
    }

    private function validateFindIntruderAnswers(array $answers, int $index): void
    {
        $intrusCount = 0;
        foreach ($answers as $answerData) {
            if (isset($answerData['is_intrus']) && $answerData['is_intrus']) {
                $intrusCount++;
            }
        }

        if ($intrusCount !== 1) {
            throw new BadRequestException("Un intrus doit être désigné pour la question #{$index}");
        }
    }

    private function validateMatchingAnswers(array $answers, int $index): void
    {
        $leftPairs = [];
        $rightPairs = [];

        foreach ($answers as $answerData) {
            if (!isset($answerData['pair_id'])) {
                throw new BadRequestException("Chaque réponse de matching doit avoir un pair_id pour la question #{$index}");
            }

            $pairId = $answerData['pair_id'];
            if (str_starts_with($pairId, 'left_')) {
                $leftPairs[] = $pairId;
            } elseif (str_starts_with($pairId, 'right_')) {
                $rightPairs[] = $pairId;
            }
        }

        if (count($leftPairs) !== count($rightPairs)) {
            throw new BadRequestException("Le nombre de réponses gauche et droite doit être égal pour la question #{$index}");
        }
    }

    private function validateRightOrderAnswers(array $answers, int $index): void
    {
        $orders = [];
        foreach ($answers as $answerData) {
            if (!isset($answerData['order_correct']) || !is_numeric($answerData['order_correct'])) {
                throw new BadRequestException("Chaque réponse de right_order doit avoir un order_correct pour la question #{$index}");
            }
            $orders[] = (int) $answerData['order_correct'];
        }

        sort($orders);
        $expectedOrders = range(1, count($answers));
        if ($orders !== $expectedOrders) {
            throw new BadRequestException("Les ordres doivent être consécutifs de 1 à " . count($answers) . " pour la question #{$index}");
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

    public function updateWithQuestions(Quiz $quiz, array $data): Quiz
    {

        $this->validateQuizData($data);
        $this->em->beginTransaction();

        try {
            if (isset($data['title'])) {
                $quiz->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $quiz->setDescription($data['description']);
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

            if (!$quiz->isPublic() && isset($data['groups']) && is_array($data['groups'])) {
                $quiz->getGroups()->clear();

                $user = $quiz->getUser();
                $userCompany = $user->getCompany();
                if ($userCompany) {
                    $maxGroups = 50;
                    $groupsToProcess = array_slice($data['groups'], 0, $maxGroups);

                    foreach ($groupsToProcess as $groupId) {
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

            if (isset($data['questions']) && is_array($data['questions'])) {

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

                foreach ($data['questions'] as $index => $questionData) {
                    $this->createQuestion($quiz, $questionData);
                }
            }

            $this->em->flush();
            $this->em->commit();
            return $quiz;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new BadRequestException('Erreur lors de la mise à jour du quiz: ' . $e->getMessage());
        }
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

    public function getPublicLeaderboard(Quiz $quiz): array
    {
        return $this->quizRepository->getPublicLeaderboard($quiz);
    }
}
