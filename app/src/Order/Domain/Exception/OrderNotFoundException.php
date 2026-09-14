<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use RuntimeException;

final class OrderNotFoundException extends RuntimeException
{
}
