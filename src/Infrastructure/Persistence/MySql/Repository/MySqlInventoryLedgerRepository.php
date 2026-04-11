<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\InventoryLedgerRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlInventoryLedgerRepository implements InventoryLedgerRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function createTransaction(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO inventory_transactions (id, item_id, location_id, transaction_type, quantity, reference_type, reference_id, transaction_date, note, created_by_user_id, created_at)
        VALUES (:id,:item_id,:location_id,:transaction_type,:quantity,:reference_type,:reference_id,NOW(),:note,:created_by_user_id,NOW())');
        $stmt->execute([
            'id' => $id,
            'item_id' => $data['item_id'],
            'location_id' => $data['location_id'],
            'transaction_type' => $data['transaction_type'],
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'note' => $data['note'],
            'created_by_user_id' => $data['created_by_user_id'],
        ]);

        return $id;
    }

    public function findBalance(string $locationId, string $itemId): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM inventory_balances WHERE location_id=:location_id AND item_id=:item_id');
        $stmt->execute(['location_id' => $locationId, 'item_id' => $itemId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function upsertBalance(string $locationId, string $itemId, float $onHand, float $reserved): void
    {
        $existing = $this->findBalance($locationId, $itemId);

        if ($existing === null) {
            $stmt = $this->connection->prepare('INSERT INTO inventory_balances (id, location_id, item_id, on_hand_quantity, reserved_quantity, updated_at)
            VALUES (:id,:location_id,:item_id,:on_hand_quantity,:reserved_quantity,NOW())');
            $stmt->execute([
                'id' => Uuid::v4(),
                'location_id' => $locationId,
                'item_id' => $itemId,
                'on_hand_quantity' => $onHand,
                'reserved_quantity' => $reserved,
            ]);

            return;
        }

        $stmt = $this->connection->prepare('UPDATE inventory_balances SET on_hand_quantity=:on_hand_quantity, reserved_quantity=:reserved_quantity, updated_at=NOW() WHERE location_id=:location_id AND item_id=:item_id');
        $stmt->execute([
            'location_id' => $locationId,
            'item_id' => $itemId,
            'on_hand_quantity' => $onHand,
            'reserved_quantity' => $reserved,
        ]);
    }
}
