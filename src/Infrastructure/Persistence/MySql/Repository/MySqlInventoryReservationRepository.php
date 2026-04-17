<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\InventoryReservationRepositoryInterface;
use PDO;

final class MySqlInventoryReservationRepository implements InventoryReservationRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function requirementLinesForEvent(string $eventId): array
    {
        $stmt = $this->connection->prepare('SELECT item_id, required_quantity, reserved_quantity FROM cleaning_event_requirements WHERE cleaning_event_id=:cleaning_event_id');
        $stmt->execute(['cleaning_event_id' => $eventId]);

        return $stmt->fetchAll();
    }

    public function updateReservedQuantity(string $eventId, string $itemId, float $reservedQuantity): void
    {
        $stmt = $this->connection->prepare('UPDATE cleaning_event_requirements SET reserved_quantity=:reserved_quantity, updated_at=NOW() WHERE cleaning_event_id=:cleaning_event_id AND item_id=:item_id');
        $stmt->execute([
            'cleaning_event_id' => $eventId,
            'item_id' => $itemId,
            'reserved_quantity' => $reservedQuantity,
        ]);
    }
}
