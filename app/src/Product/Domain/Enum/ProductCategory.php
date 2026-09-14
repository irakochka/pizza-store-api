<?php

declare(strict_types=1);

namespace App\Product\Domain\Enum;

enum ProductCategory: string
{
    case Pizza = 'pizza';
    case Drink = 'drink';
}
