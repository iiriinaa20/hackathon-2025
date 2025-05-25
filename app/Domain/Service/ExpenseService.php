<?php

declare(strict_types=1);

namespace App\Domain\Service;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\UploadedFileInterface;
use DateTimeImmutable;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\Entity\Expense;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
        private readonly LoggerInterface $logger,
    ) {}

    public function find(int $id): ?Expense
    {
        return $this->expenses->find($id);
    }

    public function delete(int $id): void
    {
        $this->expenses->delete($id);
    }
    public function deleteAll(): void
    {
        $this->expenses->deleteAll();
    }

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month,
        ];

        $expenses = $this->expenses->findBy(
            $criteria,
            $pageNumber,
            $pageSize
        );

        $total = $this->expenses->countBy($criteria);

        return [
            'total' => $total,
            'expenses' => $expenses,
        ];
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        $expense = new Expense(null, $user->id, $date, $category, (int)round($amount * 100), $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {

        $expense->amountCents = (int)round($amount * 100);
        $expense->description = $description;
        $expense->date = $date;
        $expense->category = $category;

        $this->expenses->save($expense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile, array $categories): int
    {
        if (!$csvFile || $csvFile->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('CSV file not uploaded or has errors');
        }

        $importedRows = 0;
        $skippedRows = [];

        $stream = $csvFile->getStream()->getMetadata('uri');
        $handle = fopen($stream, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open CSV file');
        }

        $this->expenses->beginTransaction();

        try {
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                if (!$this->isValidRow($data, $categories, $skippedRows)) {
                    continue;
                }

                [$date, $amountCents, $description, $category] = $this->parseRow($data);

                if ($this->isDuplicate($user, $date, $amountCents, $description, $category)) {
                    $skippedRows[] = "Duplicate: " . implode(',', $data);
                    continue;
                }

                $expense = new Expense(null, $user->id, $date, $category, $amountCents, $description);
                $this->expenses->save($expense);
                $importedRows++;
            }

            fclose($handle);

            foreach ($skippedRows as $msg) {
                $this->logger->warning("Skipped row: $msg");
            }

            $this->logger->info("Finished importing. Successfully imported: $importedRows row(s)");

            $this->expenses->commit();
        } catch (\Throwable $e) {
            fclose($handle);
            $this->expenses->rollback();
            $this->logger->error("Rolled back due to error: " . $e->getMessage());
            throw new \RuntimeException('CSV import failed', 0, $e);
        }

        return $importedRows;
    }

    private function isValidRow(array $data, array $categories, array &$skippedRows): bool
    {
        if (count($data) < 4) {
            $skippedRows[] = "Invalid row: not enough columns";
            return false;
        }

        [$rawDate, $rawDescription, $rawAmount, $rawCategory] = $data;

        try {
            new DateTimeImmutable($rawDate);
        } catch (\Exception) {
            $skippedRows[] = "Invalid date format: $rawDate";
            return false;
        }

        if (trim($rawDescription) === '' || trim($rawCategory) === '' || floatval($rawAmount) <= 0) {
            $skippedRows[] = "Invalid data: " . implode(',', $data);
            return false;
        }

        if (!in_array(trim($rawCategory), $categories)) {
            $skippedRows[] = "Unknown category: $rawCategory";
            return false;
        }

        return true;
    }

    private function parseRow(array $data): array
    {
        $date = new DateTimeImmutable($data[0]);
        $amountCents = (int)round(floatval($data[1]) * 100);
        $description = trim($data[2]);
        $category = trim($data[3]);

        return [$date, $amountCents, $description, $category];
    }

    private function isDuplicate(User $user, DateTimeImmutable $date, int $amountCents, string $description, string $category): bool
    {
        $existing = $this->expenses->findBy([
            'userId' => $user->id,
            'date' => $date->format('Y-m-d'),
            'description' => $description,
            'amountCents' => $amountCents,
            'category' => $category,
        ], 0, 1);

        return !empty($existing);
    }

    public function listExpenditureYears(User $user, int $currentYear): array
    {
        $years = $this->expenses->listExpenditureYears($user);
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }
        rsort($years);
        return $years;
    }
}
