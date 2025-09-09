<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\CategoryQuiz;
use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }
    
    public function testUserPersistence(): void
    {
        $user = new User();
        $user->setEmail('test@integration.com');
        $user->setPseudo('testuser');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashedpassword');
        $user->setIsVerified(true);
        $user->setIsActive(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->assertNotNull($user->getId());
        
        // Vérifier que l'utilisateur peut être récupéré
        $savedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertNotNull($savedUser);
        $this->assertEquals('test@integration.com', $savedUser->getEmail());
        
        // Nettoyage
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
    
    public function testCategoryQuizRepository(): void
    {
        $repository = $this->entityManager->getRepository(CategoryQuiz::class);
        
        // Test de la méthode findAll
        $categories = $repository->findAll();
        $this->assertIsArray($categories);
        
        // Test de la méthode find avec un ID invalide
        $category = $repository->find(99999);
        $this->assertNull($category);
    }
    
    public function testQuizWithCategoryRelation(): void
    {
        // Créer une catégorie
        $category = new CategoryQuiz();
        $category->setName('Test Category');
        
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('quiz@integration.com');
        $user->setPseudo('quizuser');
        $user->setFirstName('Quiz');
        $user->setLastName('User');
        $user->setPassword('hashedpassword');
        $user->setIsVerified(true);
        $user->setIsActive(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Créer un quiz avec relation
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('Description quiz test');
        $quiz->setUser($user);
        $quiz->setCategory($category);
        $quiz->setDifficulty(\App\Enum\Difficulty::EASY);
        $quiz->setIsPublic(true);
        
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();
        
        $this->assertNotNull($quiz->getId());
        $this->assertEquals($category->getId(), $quiz->getCategory()->getId());
        $this->assertEquals($user->getId(), $quiz->getUser()->getId());
        
        // Nettoyage
        $this->entityManager->remove($quiz);
        $this->entityManager->remove($user);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
    
    public function testEntityManagerTransactions(): void
    {
        $this->entityManager->beginTransaction();
        
        try {
            $user = new User();
            $user->setEmail('transaction@test.com');
            $user->setPseudo('transactionuser');
            $user->setFirstName('Transaction');
            $user->setLastName('Test');
            $user->setPassword('hashedpassword');
            $user->setIsVerified(true);
            $user->setIsActive(true);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Rollback volontaire
            $this->entityManager->rollback();
            
            // L'utilisateur ne doit pas exister après rollback
            $savedUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => 'transaction@test.com']);
            $this->assertNull($savedUser);
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermer l'entity manager pour éviter les fuites mémoire
        $this->entityManager->close();
    }
}
