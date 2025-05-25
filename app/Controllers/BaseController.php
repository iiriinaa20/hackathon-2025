<?php

declare(strict_types=1);

namespace App\Controllers;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;

abstract class BaseController
{
    protected $errors = [];
    public function __construct(
        protected Twig $view,
    ) {}

    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->view->render($response, $template, $data);
    }

    protected function getAvailableCategories(): array
    {
        $json = $_ENV['CATEGORY_BUDGETS'] ?? getenv('CATEGORY_BUDGETS') ?: '{}';
        $categories = json_decode($json, true);
        return is_array($categories) ? array_keys($categories) : [];
    }

    protected function redirectSessionLost(?User $user, Response $response)
    {
        if (!$user) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
    }

    protected function manageErrors(): void
    {
        $this->errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    protected function getErrors(): array
    {
        return $this->errors;
    }

}
