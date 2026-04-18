<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface LaundryHandoverRepositoryInterface
{
    /**
     * @param array{property_id:?string,from_location_id:string,to_location_id:string,expected_return_date:?string,created_by_user_id:?string,note:?string,status:string} $header
     * @param array<int,array{item_id:string,quantity_sent:float,status:string}> $items
     */
    public function createHandover(array $header, array $items): string;

    public function findHandoverById(string $handoverId): ?array;

    public function handoverItems(string $handoverId): array;

    public function updateHandoverItemReturn(string $handoverId, string $itemId, float $quantityReturned, string $status): void;

    public function updateHandoverStatus(string $handoverId, string $status, ?string $returnedDate): void;
}
