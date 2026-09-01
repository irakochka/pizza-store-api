<?php

declare(strict_types=1);

namespace App\User\Domain\Enum;

enum UserRole: string
{
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
}
