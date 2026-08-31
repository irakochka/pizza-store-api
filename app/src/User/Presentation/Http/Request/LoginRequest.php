<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(max: 4096)]
        public string $password,
    ) {
    }

    public function normalizedEmail(): string
    {
        return mb_strtolower(trim($this->email));
    }
}
