<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use PDO;

abstract class AbstractPdoRepository
{
    public function __construct(protected PDO $connection)
    {
    }
}
