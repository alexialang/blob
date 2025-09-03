<?php

namespace App\Tests\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\CategoryQuiz;
use App\Entity\Company;
use App\Enum\Status;
use App\Service\QuizCrudService;
use App\Repository\QuizRepository;
use App\Repository\CategoryQuizRepository;
use App\Repository\TypeQuestionRepository;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class QuizCrudServiceTest extends TestCase
{
    private QuizCrudService $quizCrudService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|QuizRepository $quizRepository;
    private MockObject|CategoryQuizRepository $categoryQuizRepository;
    private MockObject|TypeQuestionRepository $typeQuestionRepository;
    private MockObject|GroupRepository $groupRepository;
    private MockObject|ValidatorInterface $validator;
    private MockObject|LoggerInterface $logger;
    private MockObject|EventDispatcherInterface $eventDispatcher;

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

    public function testCreateWithQuestionsSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $company = new Company();
        $company->setName('Test Company');
        $user->setCompany($company);

        $category = new CategoryQuiz();
        $category->setName('Test Category');

        $quizData = [
            'title' => 'Test Quiz',
            'description' => 'A test quiz',
            'category_id' => 1,
            'isPublic' => true,
            'status' => 'published',
            'questions' => [
                [
                    'question' => 'What is 2+2?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => '4', 'is_correct' => true],
                        ['answer' => '3', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->categoryQuizRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Quiz::class));

        $this->entityManager
            ->expects($this->exactly(2)) // Une fois pour le quiz, une fois pour la question
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $result = $this->quizCrudService->createWithQuestions($quizData, $user);

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals('Test Quiz', $result->getTitle());
        $this->assertEquals('A test quiz', $result->getDescription());
        $this->assertTrue($result->isPublic());
        $this->assertEquals(Status::PUBLISHED, $result->getStatus());
    }

    public function testCreateWithQuestionsRollbackOnError(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $quizData = [
            'title' => 'Test Quiz',
            'description' => 'A test quiz',
            'category_id' => 1,
            'questions' => []
        ];

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur création quiz'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->quizCrudService->createWithQuestions($quizData, $user);
    }

    public function testShowQuizWithUserAccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('Test Description');
        $quiz->setStatus(Status::PUBLISHED);
        $quiz->setIsPublic(true);
        $quiz->setDateCreation(new \DateTimeImmutable());
        $quiz->setUser($user);

        $this->quizRepository
            ->expects($this->once())
            ->method('findWithUserAccess')
            ->with(1, $user)
            ->willReturn($quiz);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->quizCrudService->show($quiz, $user);

        $this->assertIsArray($result);
        $this->assertEquals('Test Quiz', $result['title']);
        $this->assertEquals('Test Description', $result['description']);
        $this->assertEquals('published', $result['status']);
        $this->assertTrue($result['isPublic']);
    }

    public function testShowQuizWithoutUserAccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');

        $this->quizRepository
            ->expects($this->once())
            ->method('findWithUserAccess')
            ->with(1, $user)
            ->willReturn(null);

        $this->quizRepository
            ->expects($this->once())
            ->method('findWithAllRelations')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quiz non accessible pour cet utilisateur');

        $this->quizCrudService->show($quiz, $user);
    }

    public function testDeleteQuizSuccess(): void
    {
        $quiz = new Quiz();
        $quiz->setTitle('Quiz to Delete');

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($quiz);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->quizCrudService->delete($quiz);
    }

    public function testDeleteQuizWithError(): void
    {
        $quiz = new Quiz();
        $quiz->setTitle('Quiz to Delete');

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->willThrowException(new \Exception('Delete error'));

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur lors de la suppression du quiz'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Erreur lors de la suppression du quiz');

        $this->quizCrudService->delete($quiz);
    }

    public function testUpdateWithQuestions(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $quiz = new Quiz();
        $quiz->setTitle('Original Title');
        $quiz->setDescription('Original Description');

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'status' => 'draft',
            'isPublic' => false
        ];

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->quizCrudService->updateWithQuestions($quiz, $updateData, $user);

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals('Updated Title', $result->getTitle());
        $this->assertEquals('Updated Description', $result->getDescription());
        $this->assertEquals(Status::DRAFT, $result->getStatus());
        $this->assertFalse($result->isPublic());
    }

    public function testGetQuizForEdit(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $quiz = new Quiz();
        $quiz->setTitle('Quiz for Edit');
        $quiz->setDescription('Description');
        $quiz->setStatus(Status::DRAFT);
        $quiz->setIsPublic(true);
        $quiz->setDateCreation(new \DateTimeImmutable());

        $category = new CategoryQuiz();
        $category->setName('Test Category');
        $quiz->setCategory($category);

        $this->quizRepository
            ->expects($this->once())
            ->method('findWithAllRelations')
            ->with(1)
            ->willReturn($quiz);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->quizCrudService->getQuizForEdit($quiz, $user);

        $this->assertIsArray($result);
        $this->assertEquals('Quiz for Edit', $result['title']);
        $this->assertEquals('Description', $result['description']);
        $this->assertEquals('draft', $result['status']);
        $this->assertTrue($result['isPublic']);
        $this->assertArrayHasKey('category', $result);
        $this->assertEquals('Test Category', $result['category']['name']);
    }

    public function testGetQuizForEditNotFound(): void
    {
        $user = new User();
        $quiz = new Quiz();

        $this->quizRepository
            ->expects($this->once())
            ->method('findWithAllRelations')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quiz non trouvé');

        $this->quizCrudService->getQuizForEdit($quiz, $user);
    }

    public function testFindQuiz(): void
    {
        $quiz = new Quiz();
        $quiz->setTitle('Found Quiz');

        $this->quizRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($quiz);

        $result = $this->quizCrudService->find(1);

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals('Found Quiz', $result->getTitle());
    }

    public function testFindQuizNotFound(): void
    {
        $this->quizRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->quizCrudService->find(999);

        $this->assertNull($result);
    }
}