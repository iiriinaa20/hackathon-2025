<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDOStatement;
use PDO;

class PdoBaseRepo
{
    public function __construct(
        private readonly PDO $pdo,
        private mixed $statement = null,
    ) {}

    public function prepare(string $query): PdoBaseRepo
    {
        $this->statement = $this->pdo->prepare($query);
        return $this;
    }

    public function execute(array $params): bool|PDOStatement
    {
        $this->statement->execute($params);
        return $this->statement;
    }

    public function fetch(): bool|array
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}
