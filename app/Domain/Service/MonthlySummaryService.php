<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Entity\User;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function listExpenditureYears(User $user): array
    {
        return $this->expenses->listExpenditureYears($user);
    }

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month,
        ];
        return $this->expenses->sumAmounts($criteria) / 100;
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month,
        ];
        $rawTotals = $this->expenses->sumAmountsByCategory($criteria);

        return array_reduce($rawTotals, function ($carry, $row) {
            $carry[$row['category']] = $row['total'] / 100;
            return $carry;
        }, []);
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month,
        ];
        $rawAverages = $this->expenses->averageAmountsByCategory($criteria);

        return array_reduce($rawAverages, function ($carry, $row) {
            $carry[$row['category']] = $row['average'] / 100;
            return $carry;
        }, []);
    }
}
