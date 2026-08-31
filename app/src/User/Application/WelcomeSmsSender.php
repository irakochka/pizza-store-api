<?php

declare(strict_types=1);

namespace App\User\Application;

use App\User\Domain\Entity\User;
use Psr\Log\LoggerInterface;

final readonly class WelcomeSmsSender
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function send(User $user): void
    {
        $this->logger->info('Welcome SMS sent.', [
            'userId' => $user->getId(),
            'phone' => $user->getPhone(),
        ]);
    }
}
