<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BookingRepositoryInterface;
use App\Application\Contracts\CleaningEventRepositoryInterface;
use App\Application\Contracts\PropertyCleaningSettingsRepositoryInterface;
use App\Application\DTO\GenerateCleaningEventsDTO;
use App\Core\Database\TransactionManager;
use DateInterval;
use DateTimeImmutable;

final class CleaningScheduleService
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
        private CleaningEventRepositoryInterface $events,
        private PropertyCleaningSettingsRepositoryInterface $settings,
        private TransactionManager $transactionManager
    ) {
    }

    public function regenerate(GenerateCleaningEventsDTO $dto): array
    {
        $propertySettings = $this->settings->byProperty($dto->propertyId) ?? [
            'mid_clean_every_days' => 4,
            'min_nights_for_mid_clean' => 5,
            'skip_mid_clean_last_n_days' => 3,
            'merge_same_day_turnover' => 1,
        ];

        $bookings = $this->bookings->listActiveByPropertyAndPeriod($dto->propertyId, $dto->fromDate, $dto->toDate);
        $from = new DateTimeImmutable($dto->fromDate);
        $to = new DateTimeImmutable($dto->toDate);

        $eventMap = [];
        foreach ($bookings as $booking) {
            $this->appendEvent($eventMap, $booking, 'arrival', new DateTimeImmutable($booking['arrival_date']), $from, $to);
            $this->appendEvent($eventMap, $booking, 'departure', new DateTimeImmutable($booking['departure_date']), $from, $to);

            $nights = (int) ((new DateTimeImmutable($booking['arrival_date']))->diff(new DateTimeImmutable($booking['departure_date']))->days);
            if ($nights < (int) $propertySettings['min_nights_for_mid_clean']) {
                continue;
            }

            $intervalDays = (int) $propertySettings['mid_clean_every_days'];
            $skipLastDays = (int) $propertySettings['skip_mid_clean_last_n_days'];
            $cursor = (new DateTimeImmutable($booking['arrival_date']))->add(new DateInterval('P' . $intervalDays . 'D'));
            $departure = new DateTimeImmutable($booking['departure_date']);
            $stopDate = $departure->sub(new DateInterval('P' . $skipLastDays . 'D'));

            while ($cursor < $stopDate) {
                $this->appendEvent($eventMap, $booking, 'mid_stay', $cursor, $from, $to);
                $cursor = $cursor->add(new DateInterval('P' . $intervalDays . 'D'));
            }
        }

        if ((int) $propertySettings['merge_same_day_turnover'] === 1) {
            $this->mergeArrivalDeparture($eventMap);
        }

        $events = array_values($eventMap);
        usort($events, static fn(array $a, array $b): int => strcmp($a['event_date'] . $a['event_type'], $b['event_date'] . $b['event_type']));

        $this->transactionManager->transactional(function () use ($dto, $events): void {
            $this->events->deleteGeneratedByPropertyAndPeriod($dto->propertyId, $dto->fromDate, $dto->toDate);
            $this->events->createMany($events);
        });

        return $events;
    }

    private function appendEvent(array &$eventMap, array $booking, string $type, DateTimeImmutable $date, DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        if ($date < $from || $date > $to) {
            return;
        }

        $key = $date->format('Y-m-d') . '|' . $type . '|' . $booking['id'];
        $eventMap[$key] = [
            'property_id' => $booking['property_id'],
            'booking_id' => $booking['id'],
            'event_date' => $date->format('Y-m-d'),
            'event_type' => $type,
            'status' => 'planned',
            'operational_notes' => null,
            'generated_by' => 'system',
        ];
    }

    private function mergeArrivalDeparture(array &$eventMap): void
    {
        $arrivals = [];
        $departures = [];
        foreach ($eventMap as $key => $event) {
            if ($event['event_type'] === 'arrival') {
                $arrivals[$event['event_date']][] = $key;
            }
            if ($event['event_type'] === 'departure') {
                $departures[$event['event_date']][] = $key;
            }
        }

        foreach ($arrivals as $date => $arrivalKeys) {
            if (!isset($departures[$date])) {
                continue;
            }

            $departureKeys = $departures[$date];
            // Turnover pairing strategy: pair arrivals and departures for the same property/date in insertion order.
            while ($arrivalKeys !== [] && $departureKeys !== []) {
                $arrivalKey = array_shift($arrivalKeys);
                $departureKey = array_shift($departureKeys);
                if ($arrivalKey === null || $departureKey === null) {
                    break;
                }

                $arrival = $eventMap[$arrivalKey];
                $departure = $eventMap[$departureKey];
                unset($eventMap[$arrivalKey], $eventMap[$departureKey]);

                $eventMap[$date . '|departure_arrival|' . $arrival['booking_id'] . '|' . $departure['booking_id']] = [
                    'property_id' => $arrival['property_id'],
                    'booking_id' => null,
                    'event_date' => $date,
                    'event_type' => 'departure_arrival',
                    'status' => 'planned',
                    'operational_notes' => null,
                    'generated_by' => 'system',
                ];
            }
        }
    }
}
