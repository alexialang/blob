<?php

namespace App\Tests\Integration;

use App\Tests\Functional\AbstractFunctionalTest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test d'intégration complet du workflow des quiz
 * Teste le cycle de vie complet : création, modification, jeu, suppression
 */
class QuizWorkflowTest extends AbstractFunctionalTest
{
    public function testCompleteQuizWorkflow(): void
    {
        $userToken = $this->getUserToken();
        
        // 1. Créer un quiz
        $quizData = [
            'title' => 'Quiz Workflow Test',
            'description' => 'Un quiz pour tester le workflow complet',
            'category_id' => 1,
            'isPublic' => true,
            'status' => 'published',
            'questions' => [
                [
                    'question' => 'Quelle est la capitale de la France ?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => 'Paris', 'is_correct' => true],
                        ['answer' => 'Lyon', 'is_correct' => false],
                        ['answer' => 'Marseille', 'is_correct' => false],
                        ['answer' => 'Toulouse', 'is_correct' => false]
                    ]
                ],
                [
                    'question' => 'Combien font 2 + 2 ?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => '4', 'is_correct' => true],
                        ['answer' => '3', 'is_correct' => false],
                        ['answer' => '5', 'is_correct' => false],
                        ['answer' => '22', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $userToken);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        
        $this->assertArrayHasKey('id', $createResponse);
        $this->assertEquals('Quiz Workflow Test', $createResponse['title']);
        $quizId = $createResponse['id'];

        // 2. Vérifier que le quiz apparaît dans la liste
        $this->client->request('GET', '/api/quiz/list');
        $listResponse = $this->assertJsonResponse();
        
        $quizFound = false;
        foreach ($listResponse as $quiz) {
            if ($quiz['id'] === $quizId) {
                $quizFound = true;
                $this->assertEquals('Quiz Workflow Test', $quiz['title']);
                break;
            }
        }
        $this->assertTrue($quizFound, 'Le quiz créé devrait apparaître dans la liste publique');

        // 3. Récupérer le quiz pour édition
        $this->makeAuthenticatedRequest('GET', "/api/quiz/{$quizId}/edit", [], $userToken);
        $editResponse = $this->assertJsonResponse();
        
        $this->assertEquals('Quiz Workflow Test', $editResponse['title']);
        $this->assertArrayHasKey('questions', $editResponse);
        $this->assertCount(2, $editResponse['questions']);

        // 4. Modifier le quiz
        $updateData = [
            'title' => 'Quiz Workflow Test - Modifié',
            'description' => 'Description mise à jour'
        ];

        $this->makeAuthenticatedRequest('PUT', "/api/quiz/{$quizId}", $updateData, $userToken);
        $updateResponse = $this->assertJsonResponse();
        
        $this->assertEquals('Quiz Workflow Test - Modifié', $updateResponse['title']);
        $this->assertEquals('Description mise à jour', $updateResponse['description']);

        // 5. Vérifier les statistiques du quiz
        $this->client->request('GET', "/api/quiz/{$quizId}/average-rating");
        $ratingResponse = $this->assertJsonResponse();
        
        $this->assertArrayHasKey('averageRating', $ratingResponse);
        $this->assertArrayHasKey('totalRatings', $ratingResponse);

        // 6. Supprimer le quiz
        $this->makeAuthenticatedRequest('DELETE', "/api/quiz/{$quizId}", [], $userToken);
        $this->assertJsonResponse(Response::HTTP_NO_CONTENT);

        // 7. Vérifier que le quiz n'existe plus
        $this->makeAuthenticatedRequest('GET', "/api/quiz/{$quizId}", [], $userToken);
        $this->assertNotFound();
    }

    public function testPrivateQuizWorkflow(): void
    {
        $userToken = $this->getUserToken();
        
        // Créer un quiz privé
        $privateQuizData = [
            'title' => 'Quiz Privé Test',
            'description' => 'Un quiz privé pour tester les permissions',
            'category_id' => 1,
            'isPublic' => false,
            'status' => 'published',
            'questions' => [
                [
                    'question' => 'Question privée ?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => 'Oui', 'is_correct' => true],
                        ['answer' => 'Non', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $privateQuizData, $userToken);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        
        $this->assertFalse($createResponse['isPublic']);
        $quizId = $createResponse['id'];

        // Nettoyer
        $this->makeAuthenticatedRequest('DELETE', "/api/quiz/{$quizId}", [], $userToken);
        $this->assertJsonResponse(Response::HTTP_NO_CONTENT);
    }

    public function testQuizValidationWorkflow(): void
    {
        $userToken = $this->getUserToken();
        
        // Test avec des données invalides
        $invalidQuizData = [
            'title' => '', // Titre vide
            'description' => 'ab', // Description trop courte
            'category_id' => 'invalid', // ID invalide
            'questions' => [] // Pas de questions
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $invalidQuizData, $userToken);
        $this->assertValidationError(['title', 'category_id', 'questions']);
    }
}
