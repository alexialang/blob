<?php

namespace App\Service;

/**
 * Service de sanitisation des entrées utilisateur
 * Protège contre XSS, injection de données et caractères dangereux
 */
class InputSanitizerService
{
    /**
     * Sanitise une chaîne de caractères
     */
    public function sanitizeString(?string $input, int $maxLength = null): ?string
    {
        if ($input === null) {
            return null;
        }
        
        $cleaned = trim($input);
        
        if ($maxLength !== null && mb_strlen($cleaned) > $maxLength) {
            $cleaned = mb_substr($cleaned, 0, $maxLength);
        }
        
        $sanitized = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $sanitized;
    }
    
    /**
     * Sanitise un tableau de données
     */
    public function sanitizeArray(array $data, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $maxLength = $rules[$key]['maxLength'] ?? null;
                $sanitized[$key] = $this->sanitizeString($value, $maxLength);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $rules[$key] ?? []);
            } elseif (is_bool($value)) {
                $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = is_float($value) ? (float) $value : (int) $value;
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitise les données d'un quiz
     */
    public function sanitizeQuizData(array $data): array
    {
        $rules = [
            'title' => ['maxLength' => 100],
            'description' => ['maxLength' => 500],
            'isPublic' => [],
            'maxPlayers' => [],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'un utilisateur
     */
    public function sanitizeUserData(array $data): array
    {
        $rules = [
            'firstName' => ['maxLength' => 70],
            'lastName' => ['maxLength' => 70],
            'email' => ['maxLength' => 180],
            'pseudo' => ['maxLength' => 50],
            'avatar' => ['maxLength' => 255],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une entreprise
     */
    public function sanitizeCompanyData(array $data): array
    {
        $rules = [
            'name' => ['maxLength' => 255],
            'description' => ['maxLength' => 1000],
            'website' => ['maxLength' => 255],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une question
     */
    public function sanitizeQuestionData(array $data): array
    {
        $rules = [
            'question' => ['maxLength' => 255],
            'type_question' => ['maxLength' => 50],
            'difficulty' => ['maxLength' => 20],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une réponse
     */
    public function sanitizeAnswerData(array $data): array
    {
        $rules = [
            'answer' => ['maxLength' => 255],
            'order_correct' => ['maxLength' => 50],
            'pair_id' => ['maxLength' => 20],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'un groupe
     */
    public function sanitizeGroupData(array $data): array
    {
        $rules = [
            'name' => ['maxLength' => 100],
            'description' => ['maxLength' => 500],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'un badge
     */
    public function sanitizeBadgeData(array $data): array
    {
        $rules = [
            'name' => ['maxLength' => 100],
            'description' => ['maxLength' => 1000],
            'image' => ['maxLength' => 255],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une salle de jeu
     */
    public function sanitizeRoomData(array $data): array
    {
        $rules = [
            'name' => ['maxLength' => 255],
            'roomCode' => ['maxLength' => 255],
            'status' => ['maxLength' => 50],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une réponse utilisateur
     */
    public function sanitizeUserAnswerData(array $data): array
    {
        $rules = [
            'total_score' => [],
            'user_id' => [],
            'quiz_id' => [],
            'question_id' => [],
            'answer' => ['maxLength' => 1000],
            'score' => [],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une catégorie de quiz
     */
    public function sanitizeCategoryQuizData(array $data): array
    {
        $rules = [
            'name' => ['maxLength' => 100],
            'description' => ['maxLength' => 500],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'une permission utilisateur
     */
    public function sanitizeUserPermissionData(array $data): array
    {
        $rules = [
            'permission' => ['maxLength' => 100],
            'user_id' => [], // Nombre
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données d'un jeu multijoueur
     */
    public function sanitizeMultiplayerGameData(array $data): array
    {
        $rules = [
            'quizId' => [],
            'maxPlayers' => [],
            'isTeamMode' => [],
            'roomName' => ['maxLength' => 255],
            'teamName' => ['maxLength' => 100],
            'questionId' => [],
            'answer' => ['maxLength' => 1000],
            'timeSpent' => [],
            'invitedUserIds' => [],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données de réinitialisation de mot de passe
     */
    public function sanitizePasswordResetData(array $data): array
    {
        $rules = [
            'email' => ['maxLength' => 180],
            'password' => ['maxLength' => 255],
            'confirmPassword' => ['maxLength' => 255],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données de don
     */
    public function sanitizeDonationData(array $data): array
    {
        $rules = [
            'amount' => [],
            'donor_email' => ['maxLength' => 180],
            'donor_name' => ['maxLength' => 100],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Sanitise les données de jeu
     */
    public function sanitizeGameData(array $data): array
    {
        $rules = [
            'answer' => ['maxLength' => 1000],
            'question_id' => [],
            'time_spent' => [],
        ];
        
        return $this->sanitizeArray($data, $rules);
    }
    
    /**
     * Valide et sanitise un email
     */
    public function sanitizeEmail(string $email): ?string
    {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        

        return $this->sanitizeString($email, 180);
    }
    
    /**
     * Valide et sanitise un pseudo
     */
    public function sanitizePseudo(string $pseudo): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9\-\_\.]{3,50}$/', $pseudo)) {
            return null;
        }
        
        return $this->sanitizeString($pseudo, 50);
    }
}



