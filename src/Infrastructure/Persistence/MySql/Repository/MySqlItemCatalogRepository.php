<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\ItemCatalogRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlItemCatalogRepository implements ItemCatalogRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO item_catalog (id, item_type, code, name, unit, is_active, created_at, updated_at)
        VALUES (:id,:item_type,:code,:name,:unit,:is_active,NOW(),NOW())');
        $stmt->execute([
            'id' => $id,
            'item_type' => $data['item_type'],
            'code' => $data['code'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE item_catalog SET item_type=:item_type, code=:code, name=:name, unit=:unit, is_active=:is_active, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'item_type' => $data['item_type'],
            'code' => $data['code'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM item_catalog WHERE id=:id')->execute(['id' => $id]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM item_catalog WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function all(?string $itemType = null): array
    {
        if ($itemType === null) {
            return $this->connection->query('SELECT * FROM item_catalog ORDER BY item_type, name')->fetchAll();
        }

        $stmt = $this->connection->prepare('SELECT * FROM item_catalog WHERE item_type=:item_type ORDER BY name');
        $stmt->execute(['item_type' => $itemType]);

        return $stmt->fetchAll();
    }
}
