<?php

declare(strict_types=1);

namespace App\Cart\Domain\ValueObject;

final readonly class CartLimits
{
    public const MAX_PIZZAS = 10;
    public const MAX_DRINKS = 20;
    public const MAX_ORDER_POSITIONS = 20;
}
