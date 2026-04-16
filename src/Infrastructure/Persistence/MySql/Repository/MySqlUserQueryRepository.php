<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\UserQueryRepositoryInterface;
use PDO;

final class MySqlUserQueryRepository implements UserQueryRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function activeCleaners(): array
    {
        $stmt = $this->connection->prepare('SELECT id, name, email FROM users WHERE role = "cleaner" AND is_active = 1 ORDER BY name');
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
