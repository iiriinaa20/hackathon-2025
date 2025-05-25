<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Entity\User;

class AlertGenerator
{

    private array $categoryBudgets = [];
    // private array $categoryBudgets = [
    //     'Groceries' => 300.00,
    //     'Utilities' => 200.00,
    //     'Transport' => 500.00,
    //     // ...
    // ];
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function setCategoryBudgets(array $budgets): void
    {
        $this->categoryBudgets = $budgets;
    }

    public function generate(User $user, int $year, int $month): array
    {
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month,
        ];

        $totals = $this->expenses->sumAmountsByCategory($criteria);
        $alerts = [];

        // echo "<pre>";
        // var_dump($totals);
        // var_dump($this->categoryBudgets);

        // die;
        foreach ($totals as $item) {
            $category = $item['category'];
            $total = $item['total'] / 100;

            if (isset($this->categoryBudgets[$category]) && $total > $this->categoryBudgets[$category]) {
                $diff = $total - $this->categoryBudgets[$category];
                $alerts[] = "{$category} budget exceeded by " . number_format($diff, 2) . " €";
            }
        }

        return $alerts;
    }
}
