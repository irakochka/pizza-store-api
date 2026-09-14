<?php

declare(strict_types=1);

ob_start();

$_SERVER['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = '1';

require dirname(__DIR__) . '/bootstrap.php';

/** @var list<string> $argv */
$argv = $_SERVER['argv'];
$method = $argv[1];
$uri = $argv[2];
$token = $argv[3] ?? '';
$body = $argv[4] ?? null;
$barrierFile = $argv[5] ?? null;

$kernel = new App\Kernel('test', true);
$kernel->boot();

$client = new Symfony\Bundle\FrameworkBundle\KernelBrowser($kernel);

$server = [];

if ($token !== '') {
    $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
}

if ($barrierFile !== null && $barrierFile !== '') {
    while (file_exists($barrierFile)) {
        usleep(1_000);
    }
}

if ($body !== null && $body !== '') {
    $server['CONTENT_TYPE'] = 'application/json';
    $server['HTTP_ACCEPT'] = 'application/json';

    $client->request($method, $uri, [], [], $server, $body);
} else {
    $client->request($method, $uri, [], [], $server);
}

$response = $client->getResponse();

ob_end_clean();

echo json_encode([
    'status' => $response->getStatusCode(),
    'body' => $response->getContent(),
], JSON_THROW_ON_ERROR);
