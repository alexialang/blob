<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\CategoryQuiz;
use App\Entity\TypeQuestion;
use App\Entity\Group;
use App\Entity\Badge;
use App\Entity\UserPermission;
use App\Enum\Status;
use App\Enum\Difficulty;
use App\Enum\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const REGULAR_USER_REFERENCE = 'regular-user';
    public const COMPANY_REFERENCE = 'test-company';
    public const QUIZ_REFERENCE = 'test-quiz';
    public const CATEGORY_REFERENCE = 'test-category';
    public const GROUP_REFERENCE = 'test-group';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(EntityManagerInterface $manager): void
    {
        // Créer une entreprise de test
        $company = new Company();
        $company->setName('Test Company');
        $company->setDateCreation(new \DateTime());
        $manager->persist($company);
        $this->addReference(self::COMPANY_REFERENCE, $company);

        // Créer un utilisateur admin
        $adminUser = new User();
        $adminUser->setEmail('admin@test.com');
        $adminUser->setFirstName('Admin');
        $adminUser->setLastName('Test');
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'password123'));
        $adminUser->setDateRegistration(new \DateTimeImmutable());
        $adminUser->setLastAccess(new \DateTime());
        $adminUser->setIsActive(true);
        $adminUser->setIsVerified(true);
        $adminUser->setCompany($company);
        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        // Créer un utilisateur régulier
        $regularUser = new User();
        $regularUser->setEmail('user@test.com');
        $regularUser->setFirstName('User');
        $regularUser->setLastName('Test');
        $regularUser->setRoles(['ROLE_USER']);
        $regularUser->setPassword($this->passwordHasher->hashPassword($regularUser, 'password123'));
        $regularUser->setDateRegistration(new \DateTimeImmutable());
        $regularUser->setLastAccess(new \DateTime());
        $regularUser->setIsActive(true);
        $regularUser->setIsVerified(true);
        $regularUser->setCompany($company);
        $manager->persist($regularUser);
        $this->addReference(self::REGULAR_USER_REFERENCE, $regularUser);

        // Ajouter des permissions à l'utilisateur régulier
        $createQuizPermission = new UserPermission();
        $createQuizPermission->setUser($regularUser);
        $createQuizPermission->setPermission(Permission::CREATE_QUIZ);
        $manager->persist($createQuizPermission);

        // Créer une catégorie de quiz
        $category = new CategoryQuiz();
        $category->setName('Test Category');
        $category->setDescription('Category for testing');
        $manager->persist($category);
        $this->addReference(self::CATEGORY_REFERENCE, $category);

        // Créer un type de question
        $typeQuestion = new TypeQuestion();
        $typeQuestion->setName('QCM');
        $manager->persist($typeQuestion);

        // Créer un groupe
        $group = new Group();
        $group->setName('Test Group');
        $group->setAccesCode('TEST123');
        $group->setCompany($company);
        $group->addUser($regularUser);
        $manager->persist($group);
        $this->addReference(self::GROUP_REFERENCE, $group);

        // Créer un quiz de test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('A quiz for testing purposes');
        $quiz->setIsPublic(true);
        $quiz->setStatus(Status::PUBLISHED);
        $quiz->setDateCreation(new \DateTimeImmutable());
        $quiz->setUser($adminUser);
        $quiz->setCompany($company);
        $quiz->setCategory($category);
        $manager->persist($quiz);
        $this->addReference(self::QUIZ_REFERENCE, $quiz);

        // Créer des questions pour le quiz
        for ($i = 1; $i <= 3; $i++) {
            $question = new Question();
            $question->setQuestion("Question de test numéro $i ?");
            $question->setDifficulty(Difficulty::EASY);
            $question->setQuiz($quiz);
            $question->setTypeQuestion($typeQuestion);
            $manager->persist($question);

            // Créer des réponses pour chaque question
            for ($j = 1; $j <= 4; $j++) {
                $answer = new Answer();
                $answer->setAnswer("Réponse $j pour question $i");
                $answer->setIsCorrect($j === 1); // La première réponse est toujours correcte
                $answer->setQuestion($question);
                $manager->persist($answer);
            }
        }

        // Créer un quiz privé
        $privateQuiz = new Quiz();
        $privateQuiz->setTitle('Private Quiz');
        $privateQuiz->setDescription('A private quiz for testing');
        $privateQuiz->setIsPublic(false);
        $privateQuiz->setStatus(Status::PUBLISHED);
        $privateQuiz->setDateCreation(new \DateTimeImmutable());
        $privateQuiz->setUser($regularUser);
        $privateQuiz->setCompany($company);
        $privateQuiz->setCategory($category);
        $privateQuiz->addGroup($group);
        $manager->persist($privateQuiz);

        // Créer un badge
        $badge = new Badge();
        $badge->setName('First Quiz');
        $badge->setDescription('Badge for completing first quiz');
        $badge->setIcon('first-quiz.svg');
        $manager->persist($badge);

        // Créer un utilisateur inactif pour les tests
        $inactiveUser = new User();
        $inactiveUser->setEmail('inactive@test.com');
        $inactiveUser->setFirstName('Inactive');
        $inactiveUser->setLastName('User');
        $inactiveUser->setRoles(['ROLE_USER']);
        $inactiveUser->setPassword($this->passwordHasher->hashPassword($inactiveUser, 'password123'));
        $inactiveUser->setDateRegistration(new \DateTimeImmutable());
        $inactiveUser->setIsActive(false);
        $inactiveUser->setIsVerified(true);
        $manager->persist($inactiveUser);

        // Créer un utilisateur non vérifié
        $unverifiedUser = new User();
        $unverifiedUser->setEmail('unverified@test.com');
        $unverifiedUser->setFirstName('Unverified');
        $unverifiedUser->setLastName('User');
        $unverifiedUser->setRoles(['ROLE_USER']);
        $unverifiedUser->setPassword($this->passwordHasher->hashPassword($unverifiedUser, 'password123'));
        $unverifiedUser->setDateRegistration(new \DateTimeImmutable());
        $unverifiedUser->setIsActive(true);
        $unverifiedUser->setIsVerified(false);
        $unverifiedUser->setConfirmationToken('test-token-123');
        $manager->persist($unverifiedUser);

        $manager->flush();
    }
}
