<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\InventoryQueryRepositoryInterface;
use PDO;

final class MySqlInventoryQueryRepository implements InventoryQueryRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function balancesByLocation(string $locationId): array
    {
        $stmt = $this->connection->prepare('SELECT ib.*, ic.name AS item_name, ic.item_type FROM inventory_balances ib INNER JOIN item_catalog ic ON ic.id = ib.item_id WHERE ib.location_id=:location_id ORDER BY ic.item_type, ic.name');
        $stmt->execute(['location_id' => $locationId]);

        return $stmt->fetchAll();
    }

    public function balancesByItem(string $itemId): array
    {
        $stmt = $this->connection->prepare('SELECT ib.*, il.name AS location_name, il.location_type FROM inventory_balances ib INNER JOIN inventory_locations il ON il.id = ib.location_id WHERE ib.item_id=:item_id ORDER BY il.name');
        $stmt->execute(['item_id' => $itemId]);

        return $stmt->fetchAll();
    }

    public function movementsByLocation(string $locationId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT it.*, ic.name AS item_name, ic.item_type FROM inventory_transactions it INNER JOIN item_catalog ic ON ic.id = it.item_id WHERE it.location_id=:location_id AND DATE(it.transaction_date) BETWEEN :from_date AND :to_date ORDER BY it.transaction_date DESC');
        $stmt->execute(['location_id' => $locationId, 'from_date' => $fromDate, 'to_date' => $toDate]);

        return $stmt->fetchAll();
    }

    public function movementsByItem(string $itemId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT it.*, il.name AS location_name, il.location_type FROM inventory_transactions it INNER JOIN inventory_locations il ON il.id = it.location_id WHERE it.item_id=:item_id AND DATE(it.transaction_date) BETWEEN :from_date AND :to_date ORDER BY it.transaction_date DESC');
        $stmt->execute(['item_id' => $itemId, 'from_date' => $fromDate, 'to_date' => $toDate]);

        return $stmt->fetchAll();
    }

    public function eventAvailability(string $eventId, string $locationId): array
    {
        $stmt = $this->connection->prepare('SELECT cer.item_id, ic.name AS item_name, cer.required_quantity, cer.reserved_quantity,
                    COALESCE(ib.on_hand_quantity,0) AS on_hand_quantity,
                    COALESCE(ib.reserved_quantity,0) AS location_reserved_quantity,
                    (cer.required_quantity - cer.reserved_quantity) AS still_needed,
                    (COALESCE(ib.on_hand_quantity,0) - COALESCE(ib.reserved_quantity,0)) AS location_available,
                    GREATEST((cer.required_quantity - cer.reserved_quantity) - (COALESCE(ib.on_hand_quantity,0) - COALESCE(ib.reserved_quantity,0)), 0) AS shortage_quantity
                FROM cleaning_event_requirements cer
                INNER JOIN item_catalog ic ON ic.id = cer.item_id
                LEFT JOIN inventory_balances ib ON ib.item_id = cer.item_id AND ib.location_id = :location_id
                WHERE cer.cleaning_event_id=:cleaning_event_id
                ORDER BY ic.name');
        $stmt->execute(['cleaning_event_id' => $eventId, 'location_id' => $locationId]);

        return $stmt->fetchAll();
    }
}
