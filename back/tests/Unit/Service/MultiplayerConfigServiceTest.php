<?php

namespace App\Tests\Unit\Service;

use App\Service\MultiplayerConfigService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MultiplayerConfigServiceTest extends TestCase
{
    private MultiplayerConfigService $service;
    private ParameterBagInterface $parameterBag;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        
        // Mock all parameter bag calls to return default values
        $this->parameterBag->method('get')->willReturnMap([
            ['app.multiplayer.room.max_players', 8],
            ['app.multiplayer.room.min_players_to_start', 2],
            ['app.multiplayer.room.max_rooms_per_user', 5],
            ['app.multiplayer.room.room_timeout', 3600],
            ['app.multiplayer.game.question_duration', 30],
            ['app.multiplayer.game.feedback_duration', 3],
            ['app.multiplayer.game.transition_duration', 3],
            ['app.multiplayer.game.max_questions_per_game', 50],
            ['app.multiplayer.sync.heartbeat_interval', 2],
            ['app.multiplayer.sync.max_sync_delay', 5],
            ['app.multiplayer.sync.retry_attempts', 3],
            ['app.multiplayer.sync.retry_delay', 1000],
            ['app.multiplayer.scoring.correct_answer_base', 10],
            ['app.multiplayer.scoring.time_bonus_max', 5],
            ['app.multiplayer.scoring.time_penalty_factor', 3],
            ['app.multiplayer.scoring.streak_bonus', 2],
            ['app.multiplayer.security.max_answers_per_question', 1],
            ['app.multiplayer.security.prevent_answer_duplication', true],
            ['app.multiplayer.security.validate_question_access', true],
            ['app.multiplayer.security.rate_limit_answers', true],
            ['app.multiplayer.security.max_answers_per_minute', 60],
        ]);

        $this->service = new MultiplayerConfigService($this->parameterBag);
    }

    // ===== Tests pour get() =====
    
    public function testGetExistingKey(): void
    {
        $result = $this->service->get('room.max_players');
        $this->assertEquals(8, $result);
    }

    public function testGetNonExistingKey(): void
    {
        $result = $this->service->get('non.existing.key');
        $this->assertNull($result);
    }

    public function testGetWithDefault(): void
    {
        $result = $this->service->get('non.existing.key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testGetNestedKey(): void
    {
        $result = $this->service->get('game.question_duration');
        $this->assertEquals(30, $result);
    }

    // ===== Tests pour getAll() =====
    
    public function testGetAll(): void
    {
        $result = $this->service->getAll();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('room', $result);
        $this->assertArrayHasKey('game', $result);
        $this->assertArrayHasKey('sync', $result);
        $this->assertArrayHasKey('scoring', $result);
        $this->assertArrayHasKey('security', $result);
        $this->assertArrayHasKey('mercure', $result);
    }

    // ===== Tests pour getRoomConfig() =====
    
    public function testGetRoomConfig(): void
    {
        $result = $this->service->getRoomConfig();
        
        $this->assertIsArray($result);
        $this->assertEquals(8, $result['max_players']);
        $this->assertEquals(2, $result['min_players_to_start']);
        $this->assertEquals(5, $result['max_rooms_per_user']);
        $this->assertEquals(3600, $result['room_timeout']);
    }

    // ===== Tests pour getGameConfig() =====
    
    public function testGetGameConfig(): void
    {
        $result = $this->service->getGameConfig();
        
        $this->assertIsArray($result);
        $this->assertEquals(30, $result['question_duration']);
        $this->assertEquals(3, $result['feedback_duration']);
        $this->assertEquals(3, $result['transition_duration']);
        $this->assertEquals(50, $result['max_questions_per_game']);
    }

    // ===== Tests pour getSyncConfig() =====
    
    public function testGetSyncConfig(): void
    {
        $result = $this->service->getSyncConfig();
        
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['heartbeat_interval']);
        $this->assertEquals(5, $result['max_sync_delay']);
        $this->assertEquals(3, $result['retry_attempts']);
        $this->assertEquals(1000, $result['retry_delay']);
    }

    // ===== Tests pour getScoringConfig() =====
    
    public function testGetScoringConfig(): void
    {
        $result = $this->service->getScoringConfig();
        
        $this->assertIsArray($result);
        $this->assertEquals(10, $result['correct_answer_base']);
        $this->assertEquals(5, $result['time_bonus_max']);
        $this->assertEquals(3, $result['time_penalty_factor']);
        $this->assertEquals(2, $result['streak_bonus']);
    }

    // ===== Tests pour getSecurityConfig() =====
    
    public function testGetSecurityConfig(): void
    {
        $result = $this->service->getSecurityConfig();
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['max_answers_per_question']);
        $this->assertTrue($result['prevent_answer_duplication']);
        $this->assertTrue($result['validate_question_access']);
        $this->assertTrue($result['rate_limit_answers']);
        $this->assertEquals(60, $result['max_answers_per_minute']);
    }

    // ===== Tests pour getMercureConfig() =====
    
    public function testGetMercureConfig(): void
    {
        $result = $this->service->getMercureConfig();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('hub_url', $result);
        $this->assertArrayHasKey('public_url', $result);
        $this->assertArrayHasKey('jwt_secret', $result);
        $this->assertArrayHasKey('topics', $result);
    }

    // ===== Tests pour getLoggingConfig() =====
    
    public function testGetLoggingConfig(): void
    {
        $result = $this->service->getLoggingConfig();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('log_game_events', $result);
        $this->assertArrayHasKey('log_player_actions', $result);
        $this->assertArrayHasKey('log_sync_events', $result);
        $this->assertArrayHasKey('log_performance', $result);
        $this->assertArrayHasKey('log_level', $result);
    }

    // ===== Tests pour getPerformanceConfig() =====
    
    public function testGetPerformanceConfig(): void
    {
        $result = $this->service->getPerformanceConfig();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('enable_caching', $result);
        $this->assertArrayHasKey('cache_ttl', $result);
        $this->assertArrayHasKey('enable_compression', $result);
        $this->assertArrayHasKey('max_concurrent_games', $result);
        $this->assertArrayHasKey('max_concurrent_players', $result);
    }

    // ===== Tests pour getMonitoringConfig() =====
    
    public function testGetMonitoringConfig(): void
    {
        $result = $this->service->getMonitoringConfig();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('enable_metrics', $result);
        $this->assertArrayHasKey('collect_game_stats', $result);
        $this->assertArrayHasKey('track_player_performance', $result);
        $this->assertArrayHasKey('alert_on_errors', $result);
        $this->assertArrayHasKey('health_check_interval', $result);
    }

    // ===== Tests pour isEnabled() =====
    
    public function testIsEnabledTrue(): void
    {
        $result = $this->service->isEnabled('security.prevent_answer_duplication');
        $this->assertTrue($result);
    }

    public function testIsEnabledFalse(): void
    {
        $result = $this->service->isEnabled('non.existing.feature');
        $this->assertFalse($result);
    }

    // ===== Tests pour les getters spécifiques =====
    
    public function testGetMercureHubUrl(): void
    {
        $result = $this->service->getMercureHubUrl();
        $this->assertIsString($result);
        $this->assertStringContainsString('mercure', $result);
    }

    public function testGetMercurePublicUrl(): void
    {
        $result = $this->service->getMercurePublicUrl();
        $this->assertIsString($result);
        $this->assertStringContainsString('mercure', $result);
    }

    public function testGetMercureJwtSecret(): void
    {
        $result = $this->service->getMercureJwtSecret();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetQuestionDuration(): void
    {
        $result = $this->service->getQuestionDuration();
        $this->assertEquals(30, $result);
    }

    public function testGetMaxPlayersPerRoom(): void
    {
        $result = $this->service->getMaxPlayersPerRoom();
        $this->assertEquals(8, $result);
    }

    public function testGetMinPlayersToStart(): void
    {
        $result = $this->service->getMinPlayersToStart();
        $this->assertEquals(2, $result);
    }

    public function testGetCorrectAnswerBaseScore(): void
    {
        $result = $this->service->getCorrectAnswerBaseScore();
        $this->assertEquals(10, $result);
    }

    public function testGetTimeBonusMax(): void
    {
        $result = $this->service->getTimeBonusMax();
        $this->assertEquals(5, $result);
    }

    public function testGetTimePenaltyFactor(): void
    {
        $result = $this->service->getTimePenaltyFactor();
        $this->assertEquals(3, $result);
    }

    public function testGetHeartbeatInterval(): void
    {
        $result = $this->service->getHeartbeatInterval();
        $this->assertEquals(2, $result);
    }

    public function testGetRetryAttempts(): void
    {
        $result = $this->service->getRetryAttempts();
        $this->assertEquals(3, $result);
    }

    public function testGetRetryDelay(): void
    {
        $result = $this->service->getRetryDelay();
        $this->assertEquals(1000, $result);
    }
}
