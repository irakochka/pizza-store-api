<?php

declare(strict_types=1);

namespace App\Cart\Presentation\Http\Request;

use function assert;
use function is_int;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCartItemRequest
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Range(min: 0, max: 100)]
        public mixed $quantity,
    ) {
    }

    public function quantity(): int
    {
        assert(is_int($this->quantity));

        return $this->quantity;
    }
}
