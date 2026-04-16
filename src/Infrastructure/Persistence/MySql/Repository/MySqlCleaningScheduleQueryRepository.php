<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\CleaningScheduleQueryRepositoryInterface;
use PDO;

final class MySqlCleaningScheduleQueryRepository implements CleaningScheduleQueryRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function eventsByPropertyAndRange(string $propertyId, string $fromDate, string $toDate): array
    {
        $sql = 'SELECT ce.*, p.name AS property_name, p.code AS property_code,
                    COALESCE(SUM(cer.required_quantity),0) AS requirement_total_quantity,
                    COUNT(DISTINCT cer.item_id) AS requirement_item_count
                FROM cleaning_events ce
                INNER JOIN properties p ON p.id = ce.property_id
                LEFT JOIN cleaning_event_requirements cer ON cer.cleaning_event_id = ce.id
                WHERE ce.property_id = :property_id AND ce.event_date BETWEEN :from_date AND :to_date
                GROUP BY ce.id
                ORDER BY ce.event_date, ce.event_type';

        return $this->hydrateWithAssignments($sql, [
            'property_id' => $propertyId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    public function eventsAllPropertiesByRange(string $fromDate, string $toDate): array
    {
        $sql = 'SELECT ce.*, p.name AS property_name, p.code AS property_code,
                    COALESCE(SUM(cer.required_quantity),0) AS requirement_total_quantity,
                    COUNT(DISTINCT cer.item_id) AS requirement_item_count
                FROM cleaning_events ce
                INNER JOIN properties p ON p.id = ce.property_id
                LEFT JOIN cleaning_event_requirements cer ON cer.cleaning_event_id = ce.id
                WHERE ce.event_date BETWEEN :from_date AND :to_date
                GROUP BY ce.id
                ORDER BY ce.event_date, p.name, ce.event_type';

        return $this->hydrateWithAssignments($sql, [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    public function eventsByCleanerAndRange(string $userId, string $fromDate, string $toDate): array
    {
        $sql = 'SELECT ce.*, p.name AS property_name, p.code AS property_code,
                    COALESCE(SUM(cer.required_quantity),0) AS requirement_total_quantity,
                    COUNT(DISTINCT cer.item_id) AS requirement_item_count
                FROM cleaning_events ce
                INNER JOIN properties p ON p.id = ce.property_id
                INNER JOIN cleaning_event_assignments cea ON cea.cleaning_event_id = ce.id AND cea.user_id = :user_id
                LEFT JOIN cleaning_event_requirements cer ON cer.cleaning_event_id = ce.id
                WHERE ce.event_date BETWEEN :from_date AND :to_date
                GROUP BY ce.id
                ORDER BY ce.event_date, ce.event_type';

        return $this->hydrateWithAssignments($sql, [
            'user_id' => $userId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    public function eventsByDay(string $date): array
    {
        return $this->eventsAllPropertiesByRange($date, $date);
    }

    private function hydrateWithAssignments(string $sql, array $params): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        if ($events === []) {
            return [];
        }

        $eventIds = array_map(static fn(array $event): string => $event['id'], $events);
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $assignmentStmt = $this->connection->prepare("SELECT cea.cleaning_event_id, cea.user_id, cea.assignment_status, u.name AS cleaner_name FROM cleaning_event_assignments cea INNER JOIN users u ON u.id = cea.user_id WHERE cea.cleaning_event_id IN ($placeholders) ORDER BY u.name");
        $assignmentStmt->execute($eventIds);
        $assignmentRows = $assignmentStmt->fetchAll();

        $assignmentMap = [];
        foreach ($assignmentRows as $row) {
            $assignmentMap[$row['cleaning_event_id']][] = [
                'user_id' => $row['user_id'],
                'cleaner_name' => $row['cleaner_name'],
                'assignment_status' => $row['assignment_status'],
            ];
        }

        foreach ($events as &$event) {
            $event['assignments'] = $assignmentMap[$event['id']] ?? [];
            $event['requirement_total_quantity'] = (float) $event['requirement_total_quantity'];
            $event['requirement_item_count'] = (int) $event['requirement_item_count'];
        }

        return $events;
    }
}
