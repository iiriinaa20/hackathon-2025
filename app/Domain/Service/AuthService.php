<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {
        if ($this->users->findByUsername($username) !== null) {
            throw new \Exception('Username already exists.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $user = new User(null, $username, $passwordHash, new \DateTimeImmutable());
        $this->users->save($user);
        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        if ($user === null || !password_verify($password, $user->passwordHash)) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->username;

        return true;
    }
}
