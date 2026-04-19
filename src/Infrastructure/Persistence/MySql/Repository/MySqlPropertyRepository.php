<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\PropertyRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlPropertyRepository implements PropertyRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $sql = 'INSERT INTO properties (id, code, name, location_label, property_type, operational_notes, is_active, created_at, updated_at)
                VALUES (:id, :code, :name, :location_label, :property_type, :operational_notes, :is_active, NOW(), NOW())';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'code' => $data['code'],
            'name' => $data['name'],
            'location_label' => $data['location_label'],
            'property_type' => $data['property_type'],
            'operational_notes' => $data['operational_notes'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE properties SET code=:code,name=:name,location_label=:location_label,property_type=:property_type,operational_notes=:operational_notes,is_active=:is_active,updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'code' => $data['code'],
            'name' => $data['name'],
            'location_label' => $data['location_label'],
            'property_type' => $data['property_type'],
            'operational_notes' => $data['operational_notes'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM properties WHERE id=:id')->execute(['id' => $id]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM properties WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function all(): array
    {
        return $this->connection->query('SELECT * FROM properties ORDER BY name')->fetchAll();
    }
}
