<?php

declare(strict_types=1);

namespace App\Order\Application;

use App\Order\Domain\Entity\Order;
use Psr\Log\LoggerInterface;

final readonly class OrderEmailNotifier
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function orderCreated(Order $order): void
    {
        $user = $order->getUser();

        $this->logger->info('Order created email sent.', [
            'orderId' => $order->getId(),
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }

    public function orderStatusChanged(Order $order): void
    {
        $user = $order->getUser();

        $this->logger->info('Order status changed email sent.', [
            'orderId' => $order->getId(),
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'status' => $order->getStatus()->value,
        ]);
    }
}
