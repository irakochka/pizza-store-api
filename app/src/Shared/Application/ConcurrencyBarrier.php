<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface ConcurrencyBarrier
{
    public function wait(string $point): void;
}
