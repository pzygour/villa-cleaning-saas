<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\BathroomTypeItemRuleRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlBathroomTypeItemRuleRepository implements BathroomTypeItemRuleRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO bathroom_type_item_rules (id, bathroom_type_id, item_id, trigger_type, quantity_per_bathroom, created_at, updated_at)
        VALUES (:id,:bathroom_type_id,:item_id,:trigger_type,:quantity_per_bathroom,NOW(),NOW())');
        $stmt->execute(['id' => $id] + $data);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE bathroom_type_item_rules SET bathroom_type_id=:bathroom_type_id,item_id=:item_id,trigger_type=:trigger_type,quantity_per_bathroom=:quantity_per_bathroom,updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id] + $data);
    }

    public function delete(string $id): void
    {
        $this->connection->prepare('DELETE FROM bathroom_type_item_rules WHERE id=:id')->execute(['id' => $id]);
    }

    public function all(?string $triggerType = null): array
    {
        if ($triggerType === null) {
            return $this->connection->query('SELECT * FROM bathroom_type_item_rules ORDER BY trigger_type')->fetchAll();
        }
        return $this->byTrigger($triggerType);
    }

    public function byTrigger(string $triggerType): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM bathroom_type_item_rules WHERE trigger_type=:trigger_type');
        $stmt->execute(['trigger_type' => $triggerType]);

        return $stmt->fetchAll();
    }
}
