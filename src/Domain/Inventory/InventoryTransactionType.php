<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

enum InventoryTransactionType: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';
    case LAUNDRY_OUT = 'laundry_out';
    case LAUNDRY_IN = 'laundry_in';
}
