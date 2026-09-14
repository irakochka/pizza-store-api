<?php

declare(strict_types=1);

namespace App\Cart\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCartItemRequest
{
    public function __construct(
        #[Assert\Range(min: 0, max: 100)]
        public int $quantity,
    ) {
    }
}
