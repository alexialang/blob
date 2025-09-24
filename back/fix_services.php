<?php

// Script pour corriger tous les services d'un coup

$services = [
    'GroupService' => ['EntityManagerInterface', 'GroupRepository', 'UserRepository', 'CompanyRepository'],
    'LeaderboardService' => ['UserAnswerRepository', 'UserRepository', 'EntityManagerInterface'],
    'MultiplayerGameService' => ['EntityManagerInterface', 'UserRepository', 'QuizRepository', 'UserAnswerRepository', 'EventDispatcherInterface', 'LoggerInterface', 'MessageBusInterface'],
    'MultiplayerTimingService' => ['EntityManagerInterface'],
    'PaymentService' => ['EntityManagerInterface', 'UserRepository', 'LoggerInterface'],
    'QuizCrudService' => ['EntityManagerInterface', 'QuizRepository', 'UserRepository', 'CategoryQuizRepository', 'TypeQuestionRepository', 'QuestionRepository', 'AnswerRepository', 'UserAnswerRepository', 'EventDispatcherInterface'],
    'QuizSearchService' => ['QuizRepository', 'EntityManagerInterface'],
    'QuizService' => ['EntityManagerInterface', 'QuizRepository', 'UserRepository', 'CategoryQuizRepository', 'TypeQuestionRepository', 'QuestionRepository', 'AnswerRepository', 'UserAnswerRepository', 'EventDispatcherInterface'],
    'UserAnswerService' => ['EntityManagerInterface', 'UserAnswerRepository', 'UserRepository', 'QuizRepository', 'QuestionRepository', 'AnswerRepository', 'EventDispatcherInterface'],
    'UserService' => ['EntityManagerInterface', 'UserRepository', 'UserPasswordHasherInterface', 'MailerInterface', 'MessageBusInterface', 'ValidatorInterface', 'EventDispatcherInterface', 'LoggerInterface'],
    'UserPermissionService' => ['EntityManagerInterface', 'UserPermissionRepository', 'UserRepository', 'PermissionRepository', 'RoleRepository'],
    'UserRoleService' => ['EntityManagerInterface', 'UserRoleRepository', 'UserRepository', 'RoleRepository'],
    'UserGroupService' => ['EntityManagerInterface', 'UserGroupRepository', 'UserRepository', 'GroupRepository'],
    'UserCompanyService' => ['EntityManagerInterface', 'UserCompanyRepository', 'UserRepository', 'CompanyRepository'],
    'UserQuizService' => ['EntityManagerInterface', 'UserQuizRepository', 'UserRepository', 'QuizRepository'],
];

$testFiles = glob('tests/Unit/Service/*Test.php');

foreach ($testFiles as $file) {
    $content = file_get_contents($file);

    // Trouver le service testé
    $serviceName = null;
    foreach ($services as $service => $args) {
        if (false !== strpos($content, $service.'()')) {
            $serviceName = $service;
            break;
        }
    }

    if (!$serviceName) {
        continue;
    }

    $args = $services[$serviceName];

    // Remplacer new Service() par new Service(mocks...)
    $mocks = array_map(fn ($arg) => '$this->'.lcfirst($arg), $args);
    $newContent = str_replace(
        'new '.$serviceName.'()',
        'new '.$serviceName.'('.implode(', ', $mocks).')',
        $content
    );

    // Ajouter les propriétés et setUp si nécessaire
    if (false === strpos($newContent, 'private function setUp')) {
        $properties = array_map(fn ($arg) => '    private '.$arg.' $'.lcfirst($arg).';', $args);
        $setup = "    protected function setUp(): void\n    {\n";
        foreach ($args as $arg) {
            $setup .= '        $this->'.lcfirst($arg).' = $this->createMock('.$arg."::class);\n";
        }
        $setup .= "    }\n";

        $newContent = str_replace(
            'class '.basename($file, '.php').' extends TestCase',
            'class '.basename($file, '.php').' extends TestCase'."\n{\n".implode("\n", $properties)."\n\n".$setup,
            $newContent
        );
    }

    file_put_contents($file, $newContent);
    echo "Corrigé: $file\n";
}

echo "Tous les services ont été corrigés !\n";
