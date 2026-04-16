<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\BedTypeRepositoryInterface;
use PDO;

final class MySqlBedTypeRepository implements BedTypeRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function allActive(): array
    {
        return $this->connection->query('SELECT * FROM bed_types WHERE is_active = 1 ORDER BY name')->fetchAll();
    }
}
