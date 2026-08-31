<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Response;

final readonly class AuthTokenResponse
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
    ) {
    }
}
