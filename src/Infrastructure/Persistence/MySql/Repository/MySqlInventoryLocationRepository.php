<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\InventoryLocationRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlInventoryLocationRepository implements InventoryLocationRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO inventory_locations (id, code, name, location_type, property_id, is_active, created_at, updated_at)
        VALUES (:id,:code,:name,:location_type,:property_id,:is_active,NOW(),NOW())');
        $stmt->execute([
            'id' => $id,
            'code' => $data['code'],
            'name' => $data['name'],
            'location_type' => $data['location_type'],
            'property_id' => $data['property_id'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE inventory_locations SET code=:code,name=:name,location_type=:location_type,property_id=:property_id,is_active=:is_active,updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'code' => $data['code'],
            'name' => $data['name'],
            'location_type' => $data['location_type'],
            'property_id' => $data['property_id'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM inventory_locations WHERE id=:id')->execute(['id' => $id]);
    }

    public function all(?string $locationType = null): array
    {
        if ($locationType === null) {
            return $this->connection->query('SELECT * FROM inventory_locations ORDER BY name')->fetchAll();
        }

        $stmt = $this->connection->prepare('SELECT * FROM inventory_locations WHERE location_type=:location_type ORDER BY name');
        $stmt->execute(['location_type' => $locationType]);

        return $stmt->fetchAll();
    }
}
