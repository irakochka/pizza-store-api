<?php

declare(strict_types=1);

namespace App\Tests\Support;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

trait RunsParallelRequests
{
    /**
     * @param list<array{method: string, path: string, token: string, body?: array<string, mixed>}> $requests
     *
     * @return list<array{status: int, body: string}>
     */
    protected function runParallelRequests(array $requests): array
    {
        $processes = [];
        $barrierFile = tempnam(sys_get_temp_dir(), 'parallel_request_barrier_');

        self::assertIsString($barrierFile);

        try {
            foreach ($requests as $request) {
                $body = array_key_exists('body', $request)
                    ? json_encode($request['body'], JSON_THROW_ON_ERROR)
                    : '';

                $command = [
                    PHP_BINARY,
                    __DIR__ . '/parallel_request.php',
                    $request['method'],
                    $request['path'],
                    $request['token'],
                    $body,
                    $barrierFile,
                ];

                $descriptorSpec = [
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];

                $process = proc_open($command, $descriptorSpec, $pipes);

                self::assertIsResource($process);

                $processes[] = [
                    'process' => $process,
                    'pipes' => $pipes,
                ];
            }

            unlink($barrierFile);

            $responses = [];

            foreach ($processes as $processData) {
                $stdout = stream_get_contents($processData['pipes'][1]);
                $stderr = stream_get_contents($processData['pipes'][2]);

                fclose($processData['pipes'][1]);
                fclose($processData['pipes'][2]);

                $exitCode = proc_close($processData['process']);

                self::assertSame(0, $exitCode, $stderr);

                $decoded = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);

                $responses[] = [
                    'status' => $decoded['status'],
                    'body' => $decoded['body'],
                ];
            }

            return $responses;
        } finally {
            if (file_exists($barrierFile)) {
                unlink($barrierFile);
            }
        }
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    protected function withConcurrencyBarrier(string $point, int $parties, callable $callback): mixed
    {
        $directory = sys_get_temp_dir() . '/concurrency_barrier_' . bin2hex(random_bytes(8));

        putenv('CONCURRENCY_BARRIER_POINT=' . $point);
        putenv('CONCURRENCY_BARRIER_PARTIES=' . $parties);
        putenv('CONCURRENCY_BARRIER_DIR=' . $directory);

        try {
            return $callback();
        } finally {
            putenv('CONCURRENCY_BARRIER_POINT');
            putenv('CONCURRENCY_BARRIER_PARTIES');
            putenv('CONCURRENCY_BARRIER_DIR');

            foreach (glob($directory . '/*.ready') ?: [] as $file) {
                unlink($file);
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
