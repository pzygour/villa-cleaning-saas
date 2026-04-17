<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\GuestItemRuleRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlGuestItemRuleRepository implements GuestItemRuleRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO guest_item_rules (id, property_id, item_id, trigger_type, quantity_per_guest, created_at, updated_at)
        VALUES (:id,:property_id,:item_id,:trigger_type,:quantity_per_guest,NOW(),NOW())');
        $stmt->execute(['id' => $id] + $data);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE guest_item_rules SET property_id=:property_id,item_id=:item_id,trigger_type=:trigger_type,quantity_per_guest=:quantity_per_guest,updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id] + $data);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM guest_item_rules WHERE id=:id')->execute(['id' => $id]);
    }

    public function all(?string $triggerType = null): array
    {
        if ($triggerType === null) {
            return $this->connection->query('SELECT * FROM guest_item_rules ORDER BY trigger_type, property_id')->fetchAll();
        }

        $stmt = $this->connection->prepare('SELECT * FROM guest_item_rules WHERE trigger_type=:trigger_type ORDER BY property_id');
        $stmt->execute(['trigger_type' => $triggerType]);

        return $stmt->fetchAll();
    }

    public function resolveForPropertyAndTrigger(string $propertyId, string $triggerType): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM guest_item_rules WHERE trigger_type=:trigger_type AND (property_id = :property_id OR property_id IS NULL) ORDER BY property_id IS NULL');
        $stmt->execute(['trigger_type' => $triggerType, 'property_id' => $propertyId]);
        $rows = $stmt->fetchAll();

        $resolved = [];
        foreach ($rows as $row) {
            $resolved[$row['item_id']] = $row;
        }

        return array_values($resolved);
    }
}
