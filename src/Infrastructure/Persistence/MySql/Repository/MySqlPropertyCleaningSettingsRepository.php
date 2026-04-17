<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\PropertyCleaningSettingsRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlPropertyCleaningSettingsRepository implements PropertyCleaningSettingsRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function upsert(string $propertyId, array $settings): void
    {
        $existing = $this->byProperty($propertyId);

        if ($existing === null) {
            $stmt = $this->connection->prepare('INSERT INTO property_cleaning_settings (id, property_id, mid_clean_every_days, min_nights_for_mid_clean, skip_mid_clean_last_n_days, merge_same_day_turnover, created_at, updated_at)
            VALUES (:id,:property_id,:mid_clean_every_days,:min_nights_for_mid_clean,:skip_mid_clean_last_n_days,:merge_same_day_turnover,NOW(),NOW())');
            $stmt->execute([
                'id' => Uuid::v4(),
                'property_id' => $propertyId,
                'mid_clean_every_days' => $settings['mid_clean_every_days'],
                'min_nights_for_mid_clean' => $settings['min_nights_for_mid_clean'],
                'skip_mid_clean_last_n_days' => $settings['skip_mid_clean_last_n_days'],
                'merge_same_day_turnover' => $settings['merge_same_day_turnover'] ? 1 : 0,
            ]);

            return;
        }

        $stmt = $this->connection->prepare('UPDATE property_cleaning_settings SET mid_clean_every_days=:mid_clean_every_days,min_nights_for_mid_clean=:min_nights_for_mid_clean,skip_mid_clean_last_n_days=:skip_mid_clean_last_n_days,merge_same_day_turnover=:merge_same_day_turnover,updated_at=NOW() WHERE property_id=:property_id');
        $stmt->execute([
            'property_id' => $propertyId,
            'mid_clean_every_days' => $settings['mid_clean_every_days'],
            'min_nights_for_mid_clean' => $settings['min_nights_for_mid_clean'],
            'skip_mid_clean_last_n_days' => $settings['skip_mid_clean_last_n_days'],
            'merge_same_day_turnover' => $settings['merge_same_day_turnover'] ? 1 : 0,
        ]);
    }

    public function byProperty(string $propertyId): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM property_cleaning_settings WHERE property_id=:property_id');
        $stmt->execute(['property_id' => $propertyId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
