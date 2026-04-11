<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\CleaningEventRequirementRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlCleaningEventRequirementRepository implements CleaningEventRequirementRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function replaceForEvent(string $cleaningEventId, array $lines): void
    {
        $this->connection->prepare('DELETE FROM cleaning_event_requirements WHERE cleaning_event_id=:cleaning_event_id')->execute([
            'cleaning_event_id' => $cleaningEventId,
        ]);

        $stmt = $this->connection->prepare('INSERT INTO cleaning_event_requirements (id, cleaning_event_id, item_id, required_quantity, reserved_quantity, picked_quantity, created_at, updated_at)
        VALUES (:id,:cleaning_event_id,:item_id,:required_quantity,0,0,NOW(),NOW())');

        foreach ($lines as $line) {
            $stmt->execute([
                'id' => Uuid::v4(),
                'cleaning_event_id' => $cleaningEventId,
                'item_id' => $line['item_id'],
                'required_quantity' => $line['required_quantity'],
            ]);
        }
    }
}
