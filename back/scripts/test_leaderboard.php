<?php

echo "🎯 Test du calcul du leaderboard\n";
echo "================================\n\n";

// Simuler les données de jeu
$gameCode = 'test_game_123';
$gameAnswers = [
    'game_'.$gameCode.'_user_1_q_1' => [
        'userId' => 1,
        'questionId' => 1,
        'isCorrect' => true,
        'points' => 9,
        'timestamp' => time(),
    ],
    'game_'.$gameCode.'_user_2_q_1' => [
        'userId' => 2,
        'questionId' => 1,
        'isCorrect' => false,
        'points' => 0,
        'timestamp' => time(),
    ],
];

echo "📊 Données de jeu simulées:\n";
foreach ($gameAnswers as $key => $answer) {
    echo "- $key: ".json_encode($answer)."\n";
}

// Simuler le calcul du score
foreach ([1, 2] as $userId) {
    $totalScore = 0;
    $correctAnswers = 0;

    foreach ($gameAnswers as $key => $answer) {
        if (0 === strpos($key, 'game_'.$gameCode.'_user_'.$userId)) {
            if (isset($answer['points']) && is_numeric($answer['points'])) {
                $totalScore += (int) $answer['points'];
            }
            if (isset($answer['isCorrect']) && $answer['isCorrect']) {
                ++$correctAnswers;
            }
        }
    }

    $totalQuestions = 2; // Simuler 2 questions
    $normalizedScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;

    echo "\n👤 Utilisateur $userId:\n";
    echo "  - Score brut: $totalScore\n";
    echo "  - Réponses correctes: $correctAnswers\n";
    echo "  - Total questions: $totalQuestions\n";
    echo "  - Score normalisé: $normalizedScore/100\n";
}

echo "\n✅ Test terminé!\n";
