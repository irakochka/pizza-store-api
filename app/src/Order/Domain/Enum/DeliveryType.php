<?php

declare(strict_types=1);

namespace App\Order\Domain\Enum;

enum DeliveryType: string
{
    case Pickup = 'pickup';
    case Courier = 'courier';
}
