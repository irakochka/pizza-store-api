<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Application\ConcurrencyBarrier;
use RuntimeException;

final readonly class EnvFileConcurrencyBarrier implements ConcurrencyBarrier
{
    public function wait(string $point): void
    {
        $enabledPoint = getenv('CONCURRENCY_BARRIER_POINT');

        if ($enabledPoint !== $point) {
            return;
        }

        $directory = getenv('CONCURRENCY_BARRIER_DIR');
        $parties = (int) getenv('CONCURRENCY_BARRIER_PARTIES');

        if ($directory === false || $directory === '' || $parties < 2) {
            return;
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $marker = $directory . '/' . getmypid() . '.ready';

        touch($marker);

        $deadline = microtime(true) + 5.0;

        while (true) {
            $markers = glob($directory . '/*.ready');

            if ($markers !== false && count($markers) >= $parties) {
                return;
            }

            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Concurrency barrier timeout.');
            }

            usleep(1_000);
        }
    }
}
