<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserRequest
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 30)]
    public string $phone;

    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 4096)]
    public string $password;

    public function __construct(
        string $name,
        string $phone,
        string $email,
        string $password,
    ) {
        $this->name = trim($name);
        $this->phone = trim($phone);
        $this->email = mb_strtolower(trim($email));
        $this->password = $password;
    }
}
