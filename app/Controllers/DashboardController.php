<?php

declare(strict_types=1);

namespace App\Controllers;

use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Service\AlertGenerator;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly MonthlySummaryService $summaryService,
        private readonly AlertGenerator $alertGenerator,
        private readonly UserRepositoryInterface $users
    ) {
        parent::__construct($view);
        $json = $_ENV['CATEGORY_BUDGETS'] ?? getenv('CATEGORY_BUDGETS') ?: '{}';
        $categories = json_decode($json, true);
        $categories = is_array($categories) ? $categories : [];
        $this->alertGenerator->setCategoryBudgets($categories);
    }

    protected function getUser(): ?User
    {
        $userId = $_SESSION['user_id'] ?? null;
        return $this->users->find($userId);
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $query = $request->getQueryParams();
        $year = (int)($query['year'] ?? date('Y'));
        $month = (int)($query['month'] ?? date('n'));

        $years = $this->summaryService->listExpenditureYears($user);
        if (!in_array($year, $years)) {
            $years[] = $year; // always show selected year
        }
        rsort($years);
        $total = $this->summaryService->computeTotalExpenditure($user, $year, $month);
        $totals = $this->summaryService->computePerCategoryTotals($user, $year, $month);
        $averages = $this->summaryService->computePerCategoryAverages($user, $year, $month);
        $maxAverage = empty($averages) ? 1 : max($averages); // avoid division by 0

        $alerts = ($year === (int)date('Y') && $month === (int)date('n'))
            ? $this->alertGenerator->generate($user, $year, $month)
            : [];

        // var_dump($alerts, $year, $month, date('Y'), date('n'));
        // die;
        return $this->render($response, 'dashboard.twig', [
            'year' => $year,
            'month' => $month,
            'years' => $years,
            'totalForMonth' => $total,
            'totalsForCategories' => $totals,
            'averagesForCategories' => $averages,
            'maxAverage' => $maxAverage,
            'alerts' => $alerts,
        ]);
    }
}
