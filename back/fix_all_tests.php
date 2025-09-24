<?php

// Script pour corriger tous les tests d'un coup

$testFiles = [
    // Services avec ArgumentCountError
    'back/tests/Unit/Service/GroupServiceNewTest.php' => [
        'class' => 'GroupService',
        'constructor_args' => ['EntityManagerInterface', 'GroupRepository', 'UserRepository', 'CompanyRepository'],
    ],
    'back/tests/Unit/Service/LeaderboardServiceNewTest.php' => [
        'class' => 'LeaderboardService',
        'constructor_args' => ['UserAnswerRepository', 'UserRepository', 'EntityManagerInterface'],
    ],
    'back/tests/Unit/Service/MultiplayerGameServiceNewTest.php' => [
        'class' => 'MultiplayerGameService',
        'constructor_args' => ['EntityManagerInterface', 'UserRepository', 'QuizRepository', 'UserAnswerRepository', 'EventDispatcherInterface'],
    ],
    'back/tests/Unit/Service/MultiplayerRoomServiceNewTest.php' => [
        'class' => 'MultiplayerRoomService',
        'constructor_args' => ['EntityManagerInterface', 'UserRepository', 'QuizRepository'],
    ],
    'back/tests/Unit/Service/MultiplayerScoreServiceNewTest.php' => [
        'class' => 'MultiplayerScoreService',
        'constructor_args' => ['EntityManagerInterface', 'UserAnswerRepository', 'UserRepository'],
    ],
    'back/tests/Unit/Service/MultiplayerTimingServiceNewTest.php' => [
        'class' => 'MultiplayerTimingService',
        'constructor_args' => ['EntityManagerInterface', 'UserRepository'],
    ],
    'back/tests/Unit/Service/PaymentServiceNewTest.php' => [
        'class' => 'PaymentService',
        'constructor_args' => ['EntityManagerInterface', 'UserRepository', 'LoggerInterface'],
    ],
    'back/tests/Unit/Service/QuizCrudServiceNewTest.php' => [
        'class' => 'QuizCrudService',
        'constructor_args' => ['EntityManagerInterface', 'QuizRepository', 'UserRepository', 'CategoryQuizRepository', 'TypeQuestionRepository', 'QuestionRepository', 'AnswerRepository', 'UserAnswerRepository', 'EventDispatcherInterface'],
    ],
    'back/tests/Unit/Service/QuizSearchServiceNewTest.php' => [
        'class' => 'QuizSearchService',
        'constructor_args' => ['QuizRepository', 'EntityManagerInterface'],
    ],
    'back/tests/Unit/Service/QuizServiceNewTest.php' => [
        'class' => 'QuizService',
        'constructor_args' => ['EntityManagerInterface', 'QuizRepository', 'UserRepository', 'CategoryQuizRepository', 'TypeQuestionRepository', 'QuestionRepository', 'AnswerRepository', 'UserAnswerRepository', 'EventDispatcherInterface'],
    ],
    'back/tests/Unit/Service/UserAnswerServiceNewTest.php' => [
        'class' => 'UserAnswerService',
        'constructor_args' => ['EntityManagerInterface', 'UserAnswerRepository', 'UserRepository', 'QuizRepository', 'QuestionRepository', 'AnswerRepository', 'EventDispatcherInterface'],
    ],
    'back/tests/Unit/Service/UserServiceNewTest.php' => [
        'class' => 'UserService',
        'constructor_args' => ['EntityManagerInterface', 'UserRepository', 'UserPasswordHasherInterface', 'MailerInterface', 'MessageBusInterface', 'ValidatorInterface', 'EventDispatcherInterface', 'LoggerInterface'],
    ],
    'back/tests/Unit/Service/UserPermissionServiceNewTest.php' => [
        'class' => 'UserPermissionService',
        'constructor_args' => ['EntityManagerInterface', 'UserPermissionRepository', 'UserRepository', 'PermissionRepository', 'RoleRepository'],
    ],
    'back/tests/Unit/Service/UserRoleServiceNewTest.php' => [
        'class' => 'UserRoleService',
        'constructor_args' => ['EntityManagerInterface', 'UserRoleRepository', 'UserRepository', 'RoleRepository'],
    ],
    'back/tests/Unit/Service/UserGroupServiceNewTest.php' => [
        'class' => 'UserGroupService',
        'constructor_args' => ['EntityManagerInterface', 'UserGroupRepository', 'UserRepository', 'GroupRepository'],
    ],
    'back/tests/Unit/Service/UserCompanyServiceNewTest.php' => [
        'class' => 'UserCompanyService',
        'constructor_args' => ['EntityManagerInterface', 'UserCompanyRepository', 'UserRepository', 'CompanyRepository'],
    ],
    'back/tests/Unit/Service/UserQuizServiceNewTest.php' => [
        'class' => 'UserQuizService',
        'constructor_args' => ['EntityManagerInterface', 'UserQuizRepository', 'UserRepository', 'QuizRepository'],
    ],
    'back/tests/Unit/Service/UserAnswerServiceFinalTest.php' => [
        'class' => 'UserAnswerService',
        'constructor_args' => ['EntityManagerInterface', 'UserAnswerRepository', 'UserRepository', 'QuizRepository', 'QuestionRepository', 'AnswerRepository', 'EventDispatcherInterface'],
    ],
    'back/tests/Unit/Service/UserPasswordResetServiceFinalTest.php' => [
        'class' => 'UserPasswordResetService',
        'constructor_args' => ['EntityManagerInterface', 'UserRepository', 'MailerInterface', 'MessageBusInterface', 'UserPasswordHasherInterface', 'string', 'string', 'ValidatorInterface'],
    ],
    'back/tests/Unit/Service/QuizCrudServiceUltimateTest.php' => [
        'class' => 'QuizCrudService',
        'constructor_args' => ['EntityManagerInterface', 'QuizRepository', 'UserRepository', 'CategoryQuizRepository', 'TypeQuestionRepository', 'QuestionRepository', 'AnswerRepository', 'UserAnswerRepository', 'EventDispatcherInterface'],
    ],
    'back/tests/Unit/Service/QuizRatingServiceTest.php' => [
        'class' => 'QuizRatingService',
        'constructor_args' => ['EntityManagerInterface', 'QuizRepository', 'UserRepository', 'UserAnswerRepository'],
    ],
    'back/tests/Unit/Service/QuizSearchServiceFinalTest.php' => [
        'class' => 'QuizSearchService',
        'constructor_args' => ['QuizRepository', 'EntityManagerInterface'],
    ],
];

foreach ($testFiles as $file => $config) {
    if (!file_exists($file)) {
        echo "Fichier $file n'existe pas\n";
        continue;
    }

    $content = file_get_contents($file);

    // Remplacer le constructeur vide par un constructeur avec mocks
    $newContent = str_replace(
        'new '.$config['class'].'()',
        'new '.$config['class'].'('.implode(', ', array_map(fn ($arg) => '$this->'.lcfirst($arg), $config['constructor_args'])).')',
        $content
    );

    // Ajouter les propriétés et le setUp si nécessaire
    if (false === strpos($newContent, 'private function setUp')) {
        $setupCode = "\n    private ".implode(', $', array_map(fn ($arg) => lcfirst($arg).' $'.lcfirst($arg), $config['constructor_args'])).";\n\n";
        $setupCode .= "    protected function setUp(): void\n    {\n";
        foreach ($config['constructor_args'] as $arg) {
            $setupCode .= '        $this->'.lcfirst($arg).' = $this->createMock('.$arg."::class);\n";
        }
        $setupCode .= "    }\n";

        $newContent = str_replace(
            'class '.basename($file, '.php').' extends TestCase',
            'class '.basename($file, '.php').' extends TestCase'.$setupCode,
            $newContent
        );
    }

    file_put_contents($file, $newContent);
    echo "Corrigé: $file\n";
}

echo "Tous les tests ont été corrigés !\n";
