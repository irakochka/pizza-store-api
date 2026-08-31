<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginRequest
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(max: 4096)]
    public string $password;

    public function __construct(
        string $email,
        string $password,
    ) {
        $this->email = mb_strtolower(trim($email));
        $this->password = $password;
    }
}
