<?php

declare(strict_types=1);

namespace App\Shared\Application;

final readonly class NoopConcurrencyBarrier implements ConcurrencyBarrier
{
    public function wait(string $point): void
    {
    }
}
