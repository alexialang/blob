<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MultiplayerConfigService
{
    private array $config;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->config = [
            'room' => [
                'max_players' => $parameterBag->get('app.multiplayer.room.max_players') ?? 8,
                'min_players_to_start' => $parameterBag->get('app.multiplayer.room.min_players_to_start') ?? 2,
                'max_rooms_per_user' => $parameterBag->get('app.multiplayer.room.max_rooms_per_user') ?? 5,
                'room_timeout' => $parameterBag->get('app.multiplayer.room.room_timeout') ?? 3600,
            ],
            'game' => [
                'question_duration' => $parameterBag->get('app.multiplayer.game.question_duration') ?? 30,
                'feedback_duration' => $parameterBag->get('app.multiplayer.game.feedback_duration') ?? 3,
                'transition_duration' => $parameterBag->get('app.multiplayer.game.transition_duration') ?? 3,
                'max_questions_per_game' => $parameterBag->get('app.multiplayer.game.max_questions_per_game') ?? 50,
            ],
            'sync' => [
                'heartbeat_interval' => $parameterBag->get('app.multiplayer.sync.heartbeat_interval') ?? 2,
                'max_sync_delay' => $parameterBag->get('app.multiplayer.sync.max_sync_delay') ?? 5,
                'retry_attempts' => $parameterBag->get('app.multiplayer.sync.retry_attempts') ?? 3,
                'retry_delay' => $parameterBag->get('app.multiplayer.sync.retry_delay') ?? 1000,
            ],
            'scoring' => [
                'correct_answer_base' => $parameterBag->get('app.multiplayer.scoring.correct_answer_base') ?? 10,
                'time_bonus_max' => $parameterBag->get('app.multiplayer.scoring.time_bonus_max') ?? 5,
                'time_penalty_factor' => $parameterBag->get('app.multiplayer.scoring.time_penalty_factor') ?? 3,
                'streak_bonus' => $parameterBag->get('app.multiplayer.scoring.streak_bonus') ?? 2,
            ],
            'security' => [
                'max_answers_per_question' => $parameterBag->get('app.multiplayer.security.max_answers_per_question') ?? 1,
                'prevent_answer_duplication' => $parameterBag->get('app.multiplayer.security.prevent_answer_duplication') ?? true,
                'validate_question_access' => $parameterBag->get('app.multiplayer.security.validate_question_access') ?? true,
                'rate_limit_answers' => $parameterBag->get('app.multiplayer.security.rate_limit_answers') ?? true,
                'max_answers_per_minute' => $parameterBag->get('app.multiplayer.security.max_answers_per_minute') ?? 60,
            ],
            'mercure' => [
                'hub_url' => $_ENV['MERCURE_URL'] ?? 'http://localhost:3000/.well-known/mercure',
                'public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure',
                'jwt_secret' => $_ENV['MERCURE_JWT_SECRET'] ?? 'b3zxO05Kh6sZsymfjQjAdx0SOwyJjPuF',
                'topics' => [
                    'rooms-updated',
                    'game-events',
                    'user-invitations',
                ],
            ],
            'logging' => [
                'log_game_events' => $parameterBag->get('app.multiplayer.logging.log_game_events') ?? true,
                'log_player_actions' => $parameterBag->get('app.multiplayer.logging.log_player_actions') ?? true,
                'log_sync_events' => $parameterBag->get('app.multiplayer.logging.log_sync_events') ?? true,
                'log_performance' => $parameterBag->get('app.multiplayer.logging.log_performance') ?? true,
                'log_level' => $parameterBag->get('app.multiplayer.logging.log_level') ?? 'info',
            ],
            'performance' => [
                'enable_caching' => $parameterBag->get('app.multiplayer.performance.enable_caching') ?? true,
                'cache_ttl' => $parameterBag->get('app.multiplayer.performance.cache_ttl') ?? 300,
                'enable_compression' => $parameterBag->get('app.multiplayer.performance.enable_compression') ?? true,
                'max_concurrent_games' => $parameterBag->get('app.multiplayer.performance.max_concurrent_games') ?? 100,
                'max_concurrent_players' => $parameterBag->get('app.multiplayer.performance.max_concurrent_players') ?? 1000,
            ],
            'monitoring' => [
                'enable_metrics' => $parameterBag->get('app.multiplayer.monitoring.enable_metrics') ?? true,
                'collect_game_stats' => $parameterBag->get('app.multiplayer.monitoring.collect_game_stats') ?? true,
                'track_player_performance' => $parameterBag->get('app.multiplayer.monitoring.track_player_performance') ?? true,
                'alert_on_errors' => $parameterBag->get('app.multiplayer.monitoring.alert_on_errors') ?? true,
                'health_check_interval' => $parameterBag->get('app.multiplayer.monitoring.health_check_interval') ?? 30,
            ],
        ];
    }

    /**
     * Récupère un paramètre de configuration
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Récupère la configuration complète
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * Récupère la configuration des salles
     */
    public function getRoomConfig(): array
    {
        return $this->config['room'];
    }

    /**
     * Récupère la configuration du gameplay
     */
    public function getGameConfig(): array
    {
        return $this->config['game'];
    }

    /**
     * Récupère la configuration de synchronisation
     */
    public function getSyncConfig(): array
    {
        return $this->config['sync'];
    }

    /**
     * Récupère la configuration du scoring
     */
    public function getScoringConfig(): array
    {
        return $this->config['scoring'];
    }

    /**
     * Récupère la configuration de sécurité
     */
    public function getSecurityConfig(): array
    {
        return $this->config['security'];
    }

    /**
     * Récupère la configuration Mercure
     */
    public function getMercureConfig(): array
    {
        return $this->config['mercure'];
    }

    /**
     * Récupère la configuration des logs
     */
    public function getLoggingConfig(): array
    {
        return $this->config['logging'];
    }

    /**
     * Récupère la configuration des performances
     */
    public function getPerformanceConfig(): array
    {
        return $this->config['performance'];
    }

    /**
     * Récupère la configuration du monitoring
     */
    public function getMonitoringConfig(): array
    {
        return $this->config['monitoring'];
    }

    /**
     * Vérifie si une fonctionnalité est activée
     */
    public function isEnabled(string $feature): bool
    {
        return $this->get($feature, false);
    }

    /**
     * Récupère l'URL du hub Mercure
     */
    public function getMercureHubUrl(): string
    {
        return $this->config['mercure']['hub_url'];
    }

    /**
     * Récupère l'URL publique Mercure
     */
    public function getMercurePublicUrl(): string
    {
        return $this->config['mercure']['public_url'];
    }

    /**
     * Récupère le secret JWT Mercure
     */
    public function getMercureJwtSecret(): string
    {
        return $this->config['mercure']['jwt_secret'];
    }

    /**
     * Récupère la durée des questions
     */
    public function getQuestionDuration(): int
    {
        return $this->config['game']['question_duration'];
    }

    /**
     * Récupère le nombre maximum de joueurs par salle
     */
    public function getMaxPlayersPerRoom(): int
    {
        return $this->config['room']['max_players'];
    }

    /**
     * Récupère le nombre minimum de joueurs pour démarrer
     */
    public function getMinPlayersToStart(): int
    {
        return $this->config['room']['min_players_to_start'];
    }

    /**
     * Récupère le score de base pour une bonne réponse
     */
    public function getCorrectAnswerBaseScore(): int
    {
        return $this->config['scoring']['correct_answer_base'];
    }

    /**
     * Récupère le bonus de temps maximum
     */
    public function getTimeBonusMax(): int
    {
        return $this->config['scoring']['time_bonus_max'];
    }

    /**
     * Récupère le facteur de pénalité de temps
     */
    public function getTimePenaltyFactor(): int
    {
        return $this->config['scoring']['time_penalty_factor'];
    }

    /**
     * Récupère l'intervalle de heartbeat
     */
    public function getHeartbeatInterval(): int
    {
        return $this->config['sync']['heartbeat_interval'];
    }

    /**
     * Récupère le nombre de tentatives de retry
     */
    public function getRetryAttempts(): int
    {
        return $this->config['sync']['retry_attempts'];
    }

    /**
     * Récupère le délai de retry
     */
    public function getRetryDelay(): int
    {
        return $this->config['sync']['retry_delay'];
    }
}

