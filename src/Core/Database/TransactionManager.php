<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use Throwable;

final class TransactionManager
{
    public function __construct(private PDO $connection)
    {
    }

    /**
     * @template T
     * @param callable():T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $operation();
            $this->connection->commit();

            return $result;
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }
}
