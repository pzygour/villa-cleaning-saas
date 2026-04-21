<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\BedTypeItemRuleRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlBedTypeItemRuleRepository implements BedTypeItemRuleRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO bed_type_item_rules (id, bed_type_id, item_id, trigger_type, quantity_per_bed, created_at, updated_at)
        VALUES (:id,:bed_type_id,:item_id,:trigger_type,:quantity_per_bed,NOW(),NOW())');
        $stmt->execute(['id' => $id] + $data);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE bed_type_item_rules SET bed_type_id=:bed_type_id,item_id=:item_id,trigger_type=:trigger_type,quantity_per_bed=:quantity_per_bed,updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id] + $data);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM bed_type_item_rules WHERE id=:id')->execute(['id' => $id]);
    }

    public function all(?string $triggerType = null): array
    {
        if ($triggerType === null) {
            return $this->connection->query('SELECT * FROM bed_type_item_rules ORDER BY trigger_type')->fetchAll();
        }
        return $this->byTrigger($triggerType);
    }

    public function byTrigger(string $triggerType): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM bed_type_item_rules WHERE trigger_type=:trigger_type');
        $stmt->execute(['trigger_type' => $triggerType]);

        return $stmt->fetchAll();
    }
}
