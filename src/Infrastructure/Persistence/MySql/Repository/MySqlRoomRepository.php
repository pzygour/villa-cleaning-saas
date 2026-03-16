<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\RoomRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlRoomRepository implements RoomRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO rooms (id, property_id, name, room_type, sort_order, is_active, created_at, updated_at) VALUES (:id,:property_id,:name,:room_type,:sort_order,:is_active,NOW(),NOW())');
        $stmt->execute([
            'id' => $id,
            'property_id' => $data['property_id'],
            'name' => $data['name'],
            'room_type' => $data['room_type'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE rooms SET name=:name, room_type=:room_type, sort_order=:sort_order, is_active=:is_active, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'room_type' => $data['room_type'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM rooms WHERE id=:id')->execute(['id' => $id]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM rooms WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listByProperty(string $propertyId): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM rooms WHERE property_id=:property_id ORDER BY sort_order, name');
        $stmt->execute(['property_id' => $propertyId]);

        return $stmt->fetchAll();
    }

    public function replaceBeds(string $roomId, array $beds): void
    {
        $this->connection->prepare('DELETE FROM room_beds WHERE room_id=:room_id')->execute(['room_id' => $roomId]);
        $stmt = $this->connection->prepare('INSERT INTO room_beds (id, room_id, bed_type_id, quantity, created_at, updated_at) VALUES (:id,:room_id,:bed_type_id,:quantity,NOW(),NOW())');
        foreach ($beds as $bed) {
            $stmt->execute([
                'id' => Uuid::v4(),
                'room_id' => $roomId,
                'bed_type_id' => $bed['bed_type_id'],
                'quantity' => $bed['quantity'],
            ]);
        }
    }

    public function replaceBathrooms(string $roomId, array $bathrooms): void
    {
        $this->connection->prepare('DELETE FROM room_bathrooms WHERE room_id=:room_id')->execute(['room_id' => $roomId]);
        $stmt = $this->connection->prepare('INSERT INTO room_bathrooms (id, room_id, bathroom_type_id, quantity, created_at, updated_at) VALUES (:id,:room_id,:bathroom_type_id,:quantity,NOW(),NOW())');
        foreach ($bathrooms as $bathroom) {
            $stmt->execute([
                'id' => Uuid::v4(),
                'room_id' => $roomId,
                'bathroom_type_id' => $bathroom['bathroom_type_id'],
                'quantity' => $bathroom['quantity'],
            ]);
        }
    }
}
