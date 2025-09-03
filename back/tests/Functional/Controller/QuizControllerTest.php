<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\AbstractFunctionalTest;
use Symfony\Component\HttpFoundation\Response;

class QuizControllerTest extends AbstractFunctionalTest
{
    public function testGetPublicQuizList(): void
    {
        $this->client->request('GET', '/api/quiz/list');
        
        $data = $this->assertJsonResponse();
        $this->assertIsArray($data);
    }

    public function testGetOrganizedQuizzes(): void
    {
        $this->client->request('GET', '/api/quiz/organized');
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('popular', $data);
        $this->assertArrayHasKey('recent', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('myQuizzes', $data);
    }

    public function testCreateQuizWithValidData(): void
    {
        $token = $this->getUserToken();
        $quizData = [
            'title' => 'Test Quiz Creation',
            'description' => 'A test quiz created via API',
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
                        ['answer' => '3', 'is_correct' => false],
                        ['answer' => '5', 'is_correct' => false],
                        ['answer' => '6', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $token);
        
        $data = $this->assertJsonResponse(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('Test Quiz Creation', $data['title']);
    }

    public function testCreateQuizWithoutAuthentication(): void
    {
        $quizData = [
            'title' => 'Test Quiz',
            'description' => 'Test description',
            'category_id' => 1,
            'questions' => []
        ];

        $this->client->request('POST', '/api/quiz/create', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($quizData));

        $this->assertUnauthorized();
    }

    public function testCreateQuizWithInvalidData(): void
    {
        $token = $this->getUserToken();
        $quizData = [
            'title' => '',
            'description' => 'Test description',
            'category_id' => 'invalid',
            'questions' => []
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $token);
        $this->assertValidationError(['title', 'category_id', 'questions']);
    }

    public function testGetQuizForEdit(): void
    {
        $token = $this->getUserToken();
        
        // D'abord, créer un quiz
        $quizData = [
            'title' => 'Quiz to Edit',
            'description' => 'A quiz for edit testing',
            'category_id' => 1,
            'isPublic' => true,
            'questions' => [
                [
                    'question' => 'Test question?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => 'Answer 1', 'is_correct' => true],
                        ['answer' => 'Answer 2', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $token);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        $quizId = $createResponse['id'];

        // Ensuite, récupérer le quiz pour édition
        $this->makeAuthenticatedRequest('GET', "/api/quiz/{$quizId}/edit", [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertEquals('Quiz to Edit', $data['title']);
        $this->assertArrayHasKey('questions', $data);
    }

    public function testUpdateQuiz(): void
    {
        $token = $this->getUserToken();
        
        // Créer un quiz d'abord
        $quizData = [
            'title' => 'Original Title',
            'description' => 'Original description',
            'category_id' => 1,
            'isPublic' => true,
            'questions' => [
                [
                    'question' => 'Original question?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => 'Answer 1', 'is_correct' => true],
                        ['answer' => 'Answer 2', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $token);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        $quizId = $createResponse['id'];

        // Mettre à jour le quiz
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description'
        ];

        $this->makeAuthenticatedRequest('PUT', "/api/quiz/{$quizId}", $updateData, $token);
        
        $data = $this->assertJsonResponse();
        $this->assertEquals('Updated Title', $data['title']);
        $this->assertEquals('Updated description', $data['description']);
    }

    public function testDeleteQuiz(): void
    {
        $token = $this->getUserToken();
        
        // Créer un quiz d'abord
        $quizData = [
            'title' => 'Quiz to Delete',
            'description' => 'This quiz will be deleted',
            'category_id' => 1,
            'isPublic' => true,
            'questions' => [
                [
                    'question' => 'Test question?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => 'Answer 1', 'is_correct' => true],
                        ['answer' => 'Answer 2', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $token);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        $quizId = $createResponse['id'];

        // Supprimer le quiz
        $this->makeAuthenticatedRequest('DELETE', "/api/quiz/{$quizId}", [], $token);
        $this->assertJsonResponse(Response::HTTP_NO_CONTENT);

        // Vérifier que le quiz n'existe plus
        $this->makeAuthenticatedRequest('GET', "/api/quiz/{$quizId}", [], $token);
        $this->assertNotFound();
    }

    public function testGetQuizAverageRating(): void
    {
        // Utiliser le quiz créé par les fixtures
        $this->client->request('GET', '/api/quiz/1/average-rating');
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('averageRating', $data);
        $this->assertArrayHasKey('totalRatings', $data);
    }

    public function testGetQuizPublicLeaderboard(): void
    {
        $this->client->request('GET', '/api/quiz/1/public-leaderboard');
        
        $data = $this->assertJsonResponse();
        $this->assertIsArray($data);
    }

    public function testGetManagementListWithPermission(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('GET', '/api/quiz/management/list', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertIsArray($data);
    }

    public function testCannotAccessOtherUserQuiz(): void
    {
        $token = $this->getUserToken();
        
        // Créer un quiz avec l'admin
        $adminToken = $this->getAdminToken();
        $quizData = [
            'title' => 'Admin Quiz',
            'description' => 'Quiz created by admin',
            'category_id' => 1,
            'isPublic' => false, // Quiz privé
            'questions' => [
                [
                    'question' => 'Admin question?',
                    'difficulty' => 'easy',
                    'type_question' => 'QCM',
                    'answers' => [
                        ['answer' => 'Answer 1', 'is_correct' => true],
                        ['answer' => 'Answer 2', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $this->makeAuthenticatedRequest('POST', '/api/quiz/create', $quizData, $adminToken);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        $quizId = $createResponse['id'];

        // L'utilisateur régulier ne devrait pas pouvoir accéder au quiz privé de l'admin
        $this->makeAuthenticatedRequest('GET', "/api/quiz/{$quizId}/edit", [], $token);
        $this->assertForbidden();
    }
}
