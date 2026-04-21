<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\UserAuthRepositoryInterface;
use PDO;

final class MySqlUserAuthRepository implements UserAuthRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare('SELECT id, name, email, role, password_hash, is_active FROM users WHERE email=:email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(string $userId): ?array
    {
        $stmt = $this->connection->prepare('SELECT id, name, email, role, password_hash, is_active FROM users WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
