<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Request;

use App\Order\Domain\Enum\OrderStatus;

final readonly class UpdateOrderStatusRequest
{
    public function __construct(
        public OrderStatus $status,
    ) {
    }
}
