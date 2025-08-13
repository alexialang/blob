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
use App\Repository\TypeQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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

    public function list(bool $forManagement = false): array
    {
        return $this->quizRepository->findPublishedOrAll($forManagement);
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

            return $this->quizRepository->findPrivateQuizzesForUserGroups($userGroupIds);
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
        if (empty($data['title'])) {
            throw new BadRequestException('Le titre du quiz est requis');
        }

        if (empty($data['description'])) {
            throw new BadRequestException('La description du quiz est requise');
        }

        if (empty($data['status'])) {
            throw new BadRequestException('Le statut du quiz est requis');
        }

        if (!isset($data['isPublic']) || !is_bool($data['isPublic'])) {
            throw new BadRequestException('Le statut public/privé du quiz est requis');
        }

        if (isset($data['questions']) && !is_array($data['questions'])) {
            throw new BadRequestException('Les questions doivent être un tableau');
        }

        if (empty($data['questions'])) {
            throw new BadRequestException('Au moins une question est requise');
        }

        foreach ($data['questions'] as $index => $questionData) {
            $this->validateQuestionData($questionData, $index);
        }
    }

    private function validateQuestionData(array $questionData, int $index): void
    {
        if (empty($questionData['question'])) {
            throw new BadRequestException("La question #{$index} est requise");
        }

        $validTypes = array_column(TypeQuestionName::cases(), 'value');
        if (!in_array($questionData['type_question'], $validTypes)) {
            throw new BadRequestException("Type de question invalide : {$questionData['type_question']}");
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
        $type = $questionData['type_question'];

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
        $typeQuestion = $this->findOrCreateTypeQuestion($questionData['type_question']);

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
}
