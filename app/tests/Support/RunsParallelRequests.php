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

        foreach ($requests as $request) {
            $command = [
                PHP_BINARY,
                __DIR__ . '/parallel_request.php',
                $request['method'],
                $request['path'],
                $request['token'],
            ];

            if (array_key_exists('body', $request)) {
                $command[] = json_encode($request['body'], JSON_THROW_ON_ERROR);
            }

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
    }
}
