<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\BathroomTypeRepositoryInterface;
use PDO;

final class MySqlBathroomTypeRepository implements BathroomTypeRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function allActive(): array
    {
        return $this->connection->query('SELECT * FROM bathroom_types WHERE is_active = 1 ORDER BY name')->fetchAll();
    }
}
