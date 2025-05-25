<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use Exception;
use DateTimeImmutable;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\Entity\Expense;

class PdoExpenseRepository
extends PdoBaseRepo
implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
        parent::__construct($pdo);
    }

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $data = $this->prepare($query)
            ->execute(['id' => $id])
            ->fetch();

        return $data === false ? null : $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        if ($expense->id === null) {
            $query = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) 
                      VALUES (:user_id, :date, :category, :amount_cents, :description)';
            $this->prepare($query)->execute([
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);
        } else {
            $query = 'UPDATE expenses 
                      SET user_id = :user_id, date = :date, category = :category, 
                          amount_cents = :amount_cents, description = :description 
                      WHERE id = :id';
            $this->prepare($query)->execute([
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
                'id' => $expense->id,
            ]);
        }
    }

    public function delete(int $id): void
    {
        $this->prepare('DELETE FROM expenses WHERE id = :id')->execute(['id' => $id]);
    }
    public function deleteAll(): void
    {
        $this->prepare('DELETE FROM expenses WHERE 1=1')->execute([]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        [$where, $params] = $this->buildSqlFilter($criteria);
        $query = "SELECT * FROM expenses WHERE $where ORDER BY date DESC LIMIT :offset, :limit";

        $params['offset'] = $from;
        $params['limit'] = $limit;

        $expenses = $this->prepare($query)
            ->execute($params)
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => $this->createExpenseFromData($data), $expenses);
    }

    public function countBy(array $criteria): int
    {
        [$where, $params] = $this->buildSqlFilter($criteria);
        $query = "SELECT COUNT(*) FROM expenses WHERE $where";

        return (int)$this->prepare($query)
            ->execute($params)
            ->fetchColumn();
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        [$where, $params] = $this->buildSqlFilter($criteria);
        $query = "SELECT category, SUM(amount_cents) AS total FROM expenses WHERE $where GROUP BY category";

        $results = $this->prepare($query)
            ->execute($params)
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => [
            'category' => $data['category'],
            'total' => (int)$data['total']
        ], $results);
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        [$where, $params] = $this->buildSqlFilter($criteria);
        $query = "SELECT category, AVG(amount_cents) AS average FROM expenses WHERE $where GROUP BY category";

        $results = $this->prepare($query)
            ->execute($params)
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => [
            'category' => $data['category'],
            'average' => (float)$data['average']
        ], $results);
    }

    public function sumAmounts(array $criteria): float
    {
        [$where, $params] = $this->buildSqlFilter($criteria);
        $query = "SELECT SUM(amount_cents) AS total FROM expenses WHERE $where";

        return (float)$this->prepare($query)
            ->execute($params)
            ->fetchColumn();
    }

    public function listExpenditureYears(User $user): array
    {
        $query = 'SELECT DISTINCT strftime("%Y", date) AS year FROM expenses WHERE user_id = :user_id ORDER BY year DESC';
        return $this->prepare($query)
            ->execute(['user_id' => $user->id])
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description']
        );
    }

    private function buildSqlFilter(array $criteria): array
    {
        $whereParts = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'userId':
                    $whereParts[] = 'user_id = :user_id';
                    $params['user_id'] = $value;
                    break;

                case 'year':
                    $whereParts[] = 'strftime("%Y", date) = :year';
                    $params['year'] = (string)$value;
                    break;

                case 'month':
                    $whereParts[] = 'strftime("%m", date) = :month';
                    $params['month'] = str_pad((string)$value, 2, '0', STR_PAD_LEFT);
                    break;

                default:
                    throw new \InvalidArgumentException("Unknown criteria key: $key");
            }
        }

        $where = implode(' AND ', $whereParts) ?: '1';
        return [$where, $params];
    }
}
