<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Response;

use App\User\Domain\Entity\User;

final readonly class UserResponse
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $phone,
        public string $email,
        public array $roles,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        $id = $user->getId();

        assert($id !== null);

        return new self(
            $id,
            $user->getName(),
            $user->getPhone(),
            $user->getEmail(),
            $user->getRoles(),
        );
    }
}
