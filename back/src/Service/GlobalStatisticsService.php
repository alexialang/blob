<?php

namespace App\Service;

use App\Repository\GlobalStatisticsRepository;

class GlobalStatisticsService
{
    public function __construct(
        private readonly GlobalStatisticsRepository $globalStatisticsRepository,
    ) {
    }

    public function getGlobalStatistics(): array
    {
        $teamScores = $this->globalStatisticsRepository->getTeamScoresByQuiz();
        $groupScores = $this->globalStatisticsRepository->getGroupScoresByQuiz();

        return [
            'teamScores' => $teamScores,
            'groupScores' => $groupScores,
        ];
    }

    public function getCompanyStatistics(int $companyId): array
    {
        $teamScores = $this->globalStatisticsRepository->getTeamScoresByQuizForCompany($companyId);
        $groupScores = $this->globalStatisticsRepository->getGroupScoresByQuizForCompany($companyId);

        return [
            'teamScores' => $teamScores,
            'groupScores' => $groupScores,
        ];
    }
}
