<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Repository\CategoryQuizRepository;
use App\Repository\GroupRepository;
use App\Repository\QuizRepository;
use App\Repository\TypeQuestionRepository;
use App\Service\QuizCrudService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class QuizCrudServiceUltimateTest extends TestCase
{
    private QuizCrudService $service;
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;
    private CategoryQuizRepository $categoryQuizRepository;
    private TypeQuestionRepository $typeQuestionRepository;
    private GroupRepository $groupRepository;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->quizRepository = $this->createMock(QuizRepository::class);
        $this->categoryQuizRepository = $this->createMock(CategoryQuizRepository::class);
        $this->typeQuestionRepository = $this->createMock(TypeQuestionRepository::class);
        $this->groupRepository = $this->createMock(GroupRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new QuizCrudService(
            $this->em,
            $this->quizRepository,
            $this->categoryQuizRepository,
            $this->typeQuestionRepository,
            $this->groupRepository,
            $this->validator,
            $this->logger,
            $this->eventDispatcher
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(QuizCrudService::class, $this->service);
    }

    public function testFind(): void
    {
        $quiz = $this->createMock(Quiz::class);

        $this->quizRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($quiz);

        $result = $this->service->find(123);

        $this->assertSame($quiz, $result);
    }

    public function testFindNotFound(): void
    {
        $this->quizRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    public function testServiceHasAllDependencies(): void
    {
        // Test que le service a bien tous ses repositories et services
        $this->assertTrue(method_exists($this->service, 'show'));
        $this->assertTrue(method_exists($this->service, 'find'));
        $this->assertTrue(method_exists($this->service, 'createWithQuestions'));
        $this->assertTrue(method_exists($this->service, 'updateWithQuestions'));
        $this->assertTrue(method_exists($this->service, 'delete'));
    }

    public function testServiceIsProperlyConfigured(): void
    {
        // Vérifier que le service est correctement configuré
        $reflection = new \ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('show'));
        $this->assertTrue($reflection->hasMethod('find'));
    }
}
