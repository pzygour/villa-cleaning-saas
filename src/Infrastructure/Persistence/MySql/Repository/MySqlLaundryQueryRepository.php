<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\LaundryQueryRepositoryInterface;
use PDO;

final class MySqlLaundryQueryRepository implements LaundryQueryRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function openHandovers(): array
    {
        $stmt = $this->connection->query("SELECT lh.*, p.name AS property_name, fl.name AS from_location_name, tl.name AS to_location_name
            FROM laundry_handovers lh
            LEFT JOIN properties p ON p.id = lh.property_id
            INNER JOIN inventory_locations fl ON fl.id = lh.from_location_id
            INNER JOIN inventory_locations tl ON tl.id = lh.to_location_id
            WHERE lh.status IN ('pending', 'partially_returned')
            ORDER BY lh.handover_date DESC");

        return $stmt->fetchAll();
    }

    public function handoversByPropertyDateRange(string $propertyId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT lh.*, p.name AS property_name, fl.name AS from_location_name, tl.name AS to_location_name
            FROM laundry_handovers lh
            LEFT JOIN properties p ON p.id = lh.property_id
            INNER JOIN inventory_locations fl ON fl.id = lh.from_location_id
            INNER JOIN inventory_locations tl ON tl.id = lh.to_location_id
            WHERE lh.property_id=:property_id
              AND DATE(lh.handover_date) BETWEEN :from_date AND :to_date
            ORDER BY lh.handover_date DESC');
        $stmt->execute([
            'property_id' => $propertyId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        return $stmt->fetchAll();
    }

    public function handoverDetail(string $handoverId): array
    {
        $headerStmt = $this->connection->prepare('SELECT lh.*, p.name AS property_name, fl.name AS from_location_name, tl.name AS to_location_name
            FROM laundry_handovers lh
            LEFT JOIN properties p ON p.id = lh.property_id
            INNER JOIN inventory_locations fl ON fl.id = lh.from_location_id
            INNER JOIN inventory_locations tl ON tl.id = lh.to_location_id
            WHERE lh.id=:id
            LIMIT 1');
        $headerStmt->execute(['id' => $handoverId]);
        $header = $headerStmt->fetch();

        if ($header === false) {
            return [];
        }

        $itemsStmt = $this->connection->prepare('SELECT lhi.*, ic.name AS item_name, ic.item_type, (lhi.quantity_sent - lhi.quantity_returned) AS pending_return_quantity
            FROM laundry_handover_items lhi
            INNER JOIN item_catalog ic ON ic.id = lhi.item_id
            WHERE lhi.laundry_handover_id=:laundry_handover_id
            ORDER BY ic.name');
        $itemsStmt->execute(['laundry_handover_id' => $handoverId]);

        return [
            'handover' => $header,
            'items' => $itemsStmt->fetchAll(),
        ];
    }

    public function pendingReturnQuantities(string $handoverId): array
    {
        $stmt = $this->connection->prepare('SELECT lhi.item_id, ic.name AS item_name, lhi.quantity_sent, lhi.quantity_returned,
            (lhi.quantity_sent - lhi.quantity_returned) AS pending_return_quantity, lhi.status
            FROM laundry_handover_items lhi
            INNER JOIN item_catalog ic ON ic.id = lhi.item_id
            WHERE lhi.laundry_handover_id=:laundry_handover_id
              AND lhi.quantity_returned < lhi.quantity_sent
            ORDER BY ic.name');
        $stmt->execute(['laundry_handover_id' => $handoverId]);

        return $stmt->fetchAll();
    }
}
