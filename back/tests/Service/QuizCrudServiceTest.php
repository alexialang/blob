<?php

namespace App\Tests\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Question;
use App\Entity\Answer;
use App\Enum\Status;
use App\Enum\Difficulty;
use App\Service\QuizCrudService;
use App\Repository\CategoryQuizRepository;
use App\Repository\GroupRepository;
use App\Repository\QuizRepository;
use App\Repository\TypeQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;

class QuizCrudServiceTest extends TestCase
{
    private QuizCrudService $quizCrudService;
    private EntityManagerInterface $entityManager;
    private QuizRepository $quizRepository;
    private CategoryQuizRepository $categoryQuizRepository;
    private TypeQuestionRepository $typeQuestionRepository;
    private GroupRepository $groupRepository;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->quizRepository = $this->createMock(QuizRepository::class);
        $this->categoryQuizRepository = $this->createMock(CategoryQuizRepository::class);
        $this->typeQuestionRepository = $this->createMock(TypeQuestionRepository::class);
        $this->groupRepository = $this->createMock(GroupRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->quizCrudService = new QuizCrudService(
            $this->entityManager,
            $this->quizRepository,
            $this->categoryQuizRepository,
            $this->typeQuestionRepository,
            $this->groupRepository,
            $this->validator,
            $this->logger,
            $this->eventDispatcher
        );
    }

    private function setEntityId($entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    public function testCreateWithQuestions(): void
    {
        $user = new User();
        $this->setEntityId($user, 1);
        
        $quizData = [
            'title' => 'Test Quiz',
            'description' => 'Test Description',
            'status' => 'draft',
            'isPublic' => true,
            'questions' => [
                [
                    'question' => 'Test Question 1',
                    'difficulty' => 'easy',
                    'answers' => [
                        [
                            'answer' => 'Answer 1',
                            'is_correct' => true
                        ],
                        [
                            'answer' => 'Answer 2',
                            'is_correct' => false
                        ]
                    ]
                ]
            ]
        ];

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $result = $this->quizCrudService->createWithQuestions($quizData, $user);

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals('Test Quiz', $result->getTitle());
        $this->assertEquals('Test Description', $result->getDescription());
        $this->assertEquals(Status::DRAFT, $result->getStatus());
        $this->assertTrue($result->isPublic());
        $this->assertEquals($user, $result->getUser());
    }

    public function testUpdateWithQuestions(): void
    {
        $user = new User();
        $this->setEntityId($user, 1);
        
        $quiz = new Quiz();
        $this->setEntityId($quiz, 1);
        $quiz->setTitle('Old Title');
        $quiz->setDescription('Old Description');
        
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'questions' => [
                [
                    'question' => 'Updated Question',
                    'difficulty' => 'medium',
                    'answers' => [
                        [
                            'answer' => 'Updated Answer',
                            'is_correct' => true
                        ]
                    ]
                ]
            ]
        ];

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $result = $this->quizCrudService->updateWithQuestions($quiz, $updateData, $user);

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals('Updated Title', $result->getTitle());
        $this->assertEquals('Updated Description', $result->getDescription());
    }
}
