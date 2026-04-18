<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\LaundryHandoverRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlLaundryHandoverRepository implements LaundryHandoverRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function createHandover(array $header, array $items): string
    {
        $handoverId = Uuid::v4();

        $stmt = $this->connection->prepare('INSERT INTO laundry_handovers (id, property_id, from_location_id, to_location_id, handover_date, expected_return_date, returned_date, status, note, created_by_user_id, created_at, updated_at)
            VALUES (:id,:property_id,:from_location_id,:to_location_id,NOW(),:expected_return_date,NULL,:status,:note,:created_by_user_id,NOW(),NOW())');
        $stmt->execute([
            'id' => $handoverId,
            'property_id' => $header['property_id'],
            'from_location_id' => $header['from_location_id'],
            'to_location_id' => $header['to_location_id'],
            'expected_return_date' => $header['expected_return_date'],
            'status' => $header['status'],
            'note' => $header['note'],
            'created_by_user_id' => $header['created_by_user_id'],
        ]);

        $itemStmt = $this->connection->prepare('INSERT INTO laundry_handover_items (id, laundry_handover_id, item_id, quantity_sent, quantity_returned, status, created_at, updated_at)
            VALUES (:id,:laundry_handover_id,:item_id,:quantity_sent,0,:status,NOW(),NOW())');

        foreach ($items as $item) {
            $itemStmt->execute([
                'id' => Uuid::v4(),
                'laundry_handover_id' => $handoverId,
                'item_id' => $item['item_id'],
                'quantity_sent' => $item['quantity_sent'],
                'status' => $item['status'],
            ]);
        }

        return $handoverId;
    }

    public function findHandoverById(string $handoverId): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM laundry_handovers WHERE id=:id LIMIT 1 FOR UPDATE');
        $stmt->execute(['id' => $handoverId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function handoverItems(string $handoverId): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM laundry_handover_items WHERE laundry_handover_id=:laundry_handover_id FOR UPDATE');
        $stmt->execute(['laundry_handover_id' => $handoverId]);

        return $stmt->fetchAll();
    }

    public function updateHandoverItemReturn(string $handoverId, string $itemId, float $quantityReturned, string $status): void
    {
        $stmt = $this->connection->prepare('UPDATE laundry_handover_items
            SET quantity_returned=:quantity_returned, status=:status, updated_at=NOW()
            WHERE laundry_handover_id=:laundry_handover_id AND item_id=:item_id');
        $stmt->execute([
            'laundry_handover_id' => $handoverId,
            'item_id' => $itemId,
            'quantity_returned' => $quantityReturned,
            'status' => $status,
        ]);
    }

    public function updateHandoverStatus(string $handoverId, string $status, ?string $returnedDate): void
    {
        $stmt = $this->connection->prepare('UPDATE laundry_handovers
            SET status=:status, returned_date=:returned_date, updated_at=NOW()
            WHERE id=:id');
        $stmt->execute([
            'id' => $handoverId,
            'status' => $status,
            'returned_date' => $returnedDate,
        ]);
    }
}
