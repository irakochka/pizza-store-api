<?php

declare(strict_types=1);

namespace App\Order\Domain\Enum;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case InProgress = 'in_progress';
    case Delivering = 'delivering';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
