<?php

namespace App\Tests\Unit\Service;

use App\Repository\GlobalStatisticsRepository;
use App\Service\GlobalStatisticsService;
use PHPUnit\Framework\TestCase;

class GlobalStatisticsServiceTest extends TestCase
{
    private GlobalStatisticsService $service;
    private GlobalStatisticsRepository $globalStatisticsRepository;

    protected function setUp(): void
    {
        $this->globalStatisticsRepository = $this->createMock(GlobalStatisticsRepository::class);

        $this->service = new GlobalStatisticsService(
            $this->globalStatisticsRepository
        );
    }

    public function testGetGlobalStatistics(): void
    {
        $teamScores = [
            ['quiz_id' => 1, 'team_name' => 'Team A', 'score' => 85],
            ['quiz_id' => 2, 'team_name' => 'Team B', 'score' => 92]
        ];
        
        $groupScores = [
            ['quiz_id' => 1, 'group_name' => 'Group 1', 'score' => 78],
            ['quiz_id' => 2, 'group_name' => 'Group 2', 'score' => 88]
        ];

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getTeamScoresByQuiz')
            ->willReturn($teamScores);

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getGroupScoresByQuiz')
            ->willReturn($groupScores);

        $result = $this->service->getGlobalStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('teamScores', $result);
        $this->assertArrayHasKey('groupScores', $result);
        $this->assertSame($teamScores, $result['teamScores']);
        $this->assertSame($groupScores, $result['groupScores']);
    }

    public function testGetGlobalStatisticsEmpty(): void
    {
        $this->globalStatisticsRepository->expects($this->once())
            ->method('getTeamScoresByQuiz')
            ->willReturn([]);

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getGroupScoresByQuiz')
            ->willReturn([]);

        $result = $this->service->getGlobalStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('teamScores', $result);
        $this->assertArrayHasKey('groupScores', $result);
        $this->assertEmpty($result['teamScores']);
        $this->assertEmpty($result['groupScores']);
    }

    public function testGetCompanyStatistics(): void
    {
        $companyId = 123;
        $teamScores = [
            ['quiz_id' => 1, 'team_name' => 'Company Team A', 'score' => 90],
            ['quiz_id' => 2, 'team_name' => 'Company Team B', 'score' => 85]
        ];
        
        $groupScores = [
            ['quiz_id' => 1, 'group_name' => 'Company Group 1', 'score' => 82],
            ['quiz_id' => 2, 'group_name' => 'Company Group 2', 'score' => 87]
        ];

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getTeamScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn($teamScores);

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getGroupScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn($groupScores);

        $result = $this->service->getCompanyStatistics($companyId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('teamScores', $result);
        $this->assertArrayHasKey('groupScores', $result);
        $this->assertSame($teamScores, $result['teamScores']);
        $this->assertSame($groupScores, $result['groupScores']);
    }

    public function testGetCompanyStatisticsEmpty(): void
    {
        $companyId = 456;

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getTeamScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getGroupScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $result = $this->service->getCompanyStatistics($companyId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('teamScores', $result);
        $this->assertArrayHasKey('groupScores', $result);
        $this->assertEmpty($result['teamScores']);
        $this->assertEmpty($result['groupScores']);
    }

    public function testGetCompanyStatisticsWithZeroId(): void
    {
        $companyId = 0;

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getTeamScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getGroupScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $result = $this->service->getCompanyStatistics($companyId);

        $this->assertIsArray($result);
        $this->assertEmpty($result['teamScores']);
        $this->assertEmpty($result['groupScores']);
    }

    public function testGetCompanyStatisticsWithNegativeId(): void
    {
        $companyId = -1;

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getTeamScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $this->globalStatisticsRepository->expects($this->once())
            ->method('getGroupScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $result = $this->service->getCompanyStatistics($companyId);

        $this->assertIsArray($result);
        $this->assertEmpty($result['teamScores']);
        $this->assertEmpty($result['groupScores']);
    }
}

