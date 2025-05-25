<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use Exception;
use DateTimeImmutable;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;

class PdoUserRepository
extends PdoBaseRepo
implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
        parent::__construct($pdo);
    }

    private function createUserFromData(array $data):User
    {
        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }
    /**
     * @throws Exception
     */
    public function find(mixed $id): ?User
    {
        $query = 'SELECT * FROM users WHERE id = :id';
        $data = $this->prepare($query)
            ->execute(['id' => $id])
            ->fetch();

        return $data === false ? null : $this->createUserFromData($data);
    }

    public function findByUsername(string $username): ?User
    {
        $query = 'SELECT * FROM users WHERE username = :username';
        $data = $this->prepare($query)
            ->execute(['username' => $username])
            ->fetch();

        return $data === false ? null : $this->createUserFromData($data);
    }

    public function save(User $user): void
    {
        $query = 'INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, :created_at)
                  ON CONFLICT (username) DO UPDATE SET password_hash = :password_hash';

        $this->prepare($query)
            ->execute([
                'username' => $user->username,
                'password_hash' => $user->passwordHash,
                'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
            ]);
    }
}
