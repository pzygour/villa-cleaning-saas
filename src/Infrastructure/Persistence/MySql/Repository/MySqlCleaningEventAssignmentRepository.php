<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\CleaningEventAssignmentRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlCleaningEventAssignmentRepository implements CleaningEventAssignmentRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function replaceAssignments(string $cleaningEventId, array $userIds): void
    {
        $this->connection->prepare('DELETE FROM cleaning_event_assignments WHERE cleaning_event_id=:cleaning_event_id')->execute([
            'cleaning_event_id' => $cleaningEventId,
        ]);

        $stmt = $this->connection->prepare('INSERT INTO cleaning_event_assignments (id, cleaning_event_id, user_id, assigned_at, assignment_status, created_at, updated_at)
        VALUES (:id,:cleaning_event_id,:user_id,NOW(),"assigned",NOW(),NOW())');

        foreach ($userIds as $userId) {
            $stmt->execute([
                'id' => Uuid::v4(),
                'cleaning_event_id' => $cleaningEventId,
                'user_id' => $userId,
            ]);
        }
    }

    public function updateStatus(string $cleaningEventId, string $userId, string $status): void
    {
        $stmt = $this->connection->prepare('UPDATE cleaning_event_assignments SET assignment_status=:assignment_status, updated_at=NOW() WHERE cleaning_event_id=:cleaning_event_id AND user_id=:user_id');
        $stmt->execute([
            'cleaning_event_id' => $cleaningEventId,
            'user_id' => $userId,
            'assignment_status' => $status,
        ]);
    }

    public function byEvent(string $cleaningEventId): array
    {
        $stmt = $this->connection->prepare('SELECT cea.*, u.name AS cleaner_name FROM cleaning_event_assignments cea INNER JOIN users u ON u.id = cea.user_id WHERE cea.cleaning_event_id=:cleaning_event_id ORDER BY u.name');
        $stmt->execute(['cleaning_event_id' => $cleaningEventId]);

        return $stmt->fetchAll();
    }
}
