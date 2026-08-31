<?php

declare(strict_types=1);

namespace App\Tests\Support;

trait AuthenticatesUsers
{
    private function adminAuthorizationHeader(): array
    {
        return $this->authorizationHeader('admin@example.com', 'admin123');
    }

    private function userAuthorizationHeader(): array
    {
        return $this->authorizationHeader('user@example.com', 'user123');
    }

    private function authorizationHeader(string $email, string $password): array
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('accessToken', $data);

        return $this->bearerTokenHeader($data['accessToken']);
    }

    private function bearerTokenHeader(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }
}
