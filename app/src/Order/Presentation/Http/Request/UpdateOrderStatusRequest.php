<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Request;

use App\Order\Domain\Enum\OrderStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateOrderStatusRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Choice(choices: [
            'created',
            'paid',
            'in_progress',
            'delivering',
            'completed',
            'cancelled',
        ])]
        public string $status,
    ) {
    }

    public function status(): OrderStatus
    {
        return OrderStatus::from($this->status);
    }
}
