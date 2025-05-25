<?php

declare(strict_types=1);

namespace App\Controllers;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;

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
