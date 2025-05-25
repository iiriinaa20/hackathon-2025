<?php

declare(strict_types=1);

namespace App\Controllers;

use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DateTimeImmutable;
use App\Domain\Service\ExpenseService;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;

class ExpenseController extends BaseController
{
    // private const PAGE_SIZE = 2;
    private const PAGE_SIZE = 20;
    private $categories = [];

    public function __construct(
        Twig $view,
        private LoggerInterface $logger,
        private readonly ExpenseService $expenseService,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct($view);
        $this->categories = $this->getAvailableCategories();
    }

    protected function getUser(): ?User
    {
        $userId = $_SESSION['user_id'] ?? null;
        return $this->userRepository->find($userId);
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('n'));

        $expenses = $this->expenseService->list($user, $year, $month, ($page - 1) * $pageSize, $pageSize);

        // echo "<pre>";
        // var_dump($expenses);
        // echo "</pre>";
        // die;
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'year'     => $year,
            'years' => $this->expenseService->listExpenditureYears($user, $year),
            'month'    => $month,
            'page'     => $page,
            'pageSize' => $pageSize,
            'flash'    => $flash
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // echo "<pre>";
        // var_dump($this->categories);
        // echo "</pre>";
        // die;
        $this->manageErrors();
        $this->logger->info('Create expense page requested', $this->getErrors());

        return $this->render($response, 'expenses/create.twig', [
            'categories' => $this->categories,
            'errors'     => $this->getErrors(),
            'old'        => $_SESSION['old'] ?? [],
        ]);
    }

    public function store(Request $request, Response $response): Response
    {

        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $data = $request->getParsedBody();

        $errors = [];
        $amount = floatval($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? '');

        $date = null;

        try {
            $date = new DateTimeImmutable($data['date'] ?? '');
        } catch (\Exception $e) {
            $errors['date'] = 'Invalid date';
        }
        $date ??= new DateTimeImmutable();

        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than zero';
        }

        if ($description === '') {
            $errors['description'] = 'Description is required';
        }

        if ($category === '') {
            $errors['category'] = 'Category is required';
        }

        if ($date > new DateTimeImmutable()) {
            $errors['date'] = 'Date must be in the past';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'date' => $data['date'] ?? '',
                'category' => $category,
                'amount' => $data['amount'] ?? '',
                'description' => $description,
            ];
            return $response->withHeader('Location', '/expenses/create')->withStatus(302);
        }

        $this->expenseService->create($user, $amount, $description, $date, $category);
        $_SESSION['flash'] = ['success' => 'Expense created successfully.'];
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        $this->manageErrors();
        $this->logger->info('Edit expense page requested', $this->getErrors());

        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $id = (int)($routeParams['id'] ?? 0);
        $expense = $this->expenseService->find($id);

        if (!$expense || $expense->userId !== $user->id) {
            return $response->withStatus(403);
        }

        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense,
            'categories' => $this->categories,
            'errors' => $this->getErrors(),
            'old' => $_SESSION['old'] ?? [],
        ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {

        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $id = (int)($routeParams['id'] ?? 0);
        $expense = $this->expenseService->find($id);

        if (!$expense || $expense->userId !== $user->id) {
            return $response->withStatus(403);
        }

        $data = $request->getParsedBody();
        $errors = [];
        $amount = floatval($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? '');

        $date = null;

        try {
            $date = new DateTimeImmutable($data['date'] ?? '');
        } catch (\Exception $e) {
            $errors['date'] = 'Invalid date';
        }
        $date ??=  new DateTimeImmutable();


        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than zero';
        }

        if ($description === '') {
            $errors['description'] = 'Description is required';
        }

        if ($category === '') {
            $errors['category'] = 'Category is required';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'date' => $data['date'] ?? '',
                'category' => $category,
                'amount' => $data['amount'] ?? '',
                'description' => $description,
            ];
            return $response->withHeader('Location', '/expenses/' . $id . '/edit')->withStatus(302);
        }

        $this->expenseService->update($expense, $amount, $description, $date, $category);
        $_SESSION['flash'] = ['success' => 'Expense updated successfully.'];

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $id = (int)($routeParams['id'] ?? 0);
        $expense = $this->expenseService->find($id);

        if (!$expense || $expense->userId !== $user->id) {
            return $response->withStatus(403);
        }

        $this->expenseService->delete($id);
        $_SESSION['flash'] = ['success' => 'Expense deleted successfully.'];

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
    
    public function destroyAll(Request $request, Response $response, array $routeParams): Response
    {
        $this->expenseService->deleteAll();
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function import(Request $request, Response $response): Response
    {
        $user = $this->getUser();
        $this->redirectSessionLost($user, $response);

        $this->expenseService->importFromCsv($user, $request->getUploadedFiles()['csv'] ?? null, $this->categories);
        $_SESSION['flash'] = ['success' => 'Expenses imported successfully.'];
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
}
