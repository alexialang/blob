<?php

namespace App\Tests\Unit\Service;

use App\Repository\GlobalStatisticsRepository;
use App\Service\GlobalStatisticsService;
use PHPUnit\Framework\TestCase;

class GlobalStatisticsServiceBasicTest extends TestCase
{
    private GlobalStatisticsService $service;
    private GlobalStatisticsRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GlobalStatisticsRepository::class);
        $this->service = new GlobalStatisticsService($this->repository);
    }

    public function testGetGlobalStatistics(): void
    {
        $teamScores = [
            ['quiz_id' => 1, 'team' => 'Team A', 'score' => 85],
            ['quiz_id' => 2, 'team' => 'Team B', 'score' => 92]
        ];

        $groupScores = [
            ['quiz_id' => 1, 'group' => 'Group 1', 'score' => 78],
            ['quiz_id' => 2, 'group' => 'Group 2', 'score' => 89]
        ];

        $this->repository->expects($this->once())
            ->method('getTeamScoresByQuiz')
            ->willReturn($teamScores);

        $this->repository->expects($this->once())
            ->method('getGroupScoresByQuiz')
            ->willReturn($groupScores);

        $result = $this->service->getGlobalStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('teamScores', $result);
        $this->assertArrayHasKey('groupScores', $result);
        $this->assertSame($teamScores, $result['teamScores']);
        $this->assertSame($groupScores, $result['groupScores']);
    }

    public function testGetCompanyStatistics(): void
    {
        $companyId = 123;
        $teamScores = [
            ['quiz_id' => 1, 'team' => 'Company Team A', 'score' => 90]
        ];

        $groupScores = [
            ['quiz_id' => 1, 'group' => 'Company Group 1', 'score' => 85]
        ];

        $this->repository->expects($this->once())
            ->method('getTeamScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn($teamScores);

        $this->repository->expects($this->once())
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

    public function testGetGlobalStatisticsWithEmptyData(): void
    {
        $this->repository->expects($this->once())
            ->method('getTeamScoresByQuiz')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getGroupScoresByQuiz')
            ->willReturn([]);

        $result = $this->service->getGlobalStatistics();

        $this->assertIsArray($result);
        $this->assertEmpty($result['teamScores']);
        $this->assertEmpty($result['groupScores']);
    }

    public function testGetCompanyStatisticsWithEmptyData(): void
    {
        $companyId = 999;

        $this->repository->expects($this->once())
            ->method('getTeamScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getGroupScoresByQuizForCompany')
            ->with($companyId)
            ->willReturn([]);

        $result = $this->service->getCompanyStatistics($companyId);

        $this->assertIsArray($result);
        $this->assertEmpty($result['teamScores']);
        $this->assertEmpty($result['groupScores']);
    }
}
