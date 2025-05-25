<?php

declare(strict_types=1);

namespace App\Controllers;

use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Service\AuthService;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        $this->manageErrors();
        $this->logger->info('Register page requested', $this->getErrors());

        return $this->render($response, 'auth/register.twig', [
            'errors' => $this->getErrors(),
        ]);
    }

    public function register(Request $request, Response $response): Response
    {
        $errors = [];
        $data = $request->getParsedBody();

        if (strlen($data['username']) < 4)
            $errors['username'] = 'Username should have at least 4 characters';
        if (strlen($data['password']) < 8)
            $errors['password'] = 'Password should have at least 8 characters';
        if (preg_match('/\d/', $data['password']) !== 1)
            $errors['password'] = 'Password should contain at least 1 number';
        if ($data['password'] !== $data['password_confirmation'])
            $errors['password'] = 'Passwords do not match';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $response->withHeader('Location', '/register')->withStatus(302);

            // return $this->render($response, 'auth/register.twig', [
            //     'errors' => $errors,
            // ]);
        }


        $this->authService->register($data['username'], $data['password']);
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        $this->manageErrors();
        $this->logger->info('Login page requested', $this->getErrors());

        return $this->render($response, 'auth/login.twig', [
            'errors' => $this->getErrors(),
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $errors = [];
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username))
            $errors['username'] = 'Username is required';
        if (empty($password))
            $errors['password'] = 'Password is required';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $response->withHeader('Location', '/login')->withStatus(302);
            // return $this->render($response, 'auth/login.twig', [
            //     'errors' => $errors,
            // ]);
        }
        if (!$this->authService->attempt($username, $password)) {
            $_SESSION['errors'] = ['submit' => 'Invalid username or password'];
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        // return $this->render($response, 'auth/login.twig', [
        //     'errors' => ['submit' => 'Invalid username or password'],
        // ]);

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
