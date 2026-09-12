<?php

declare(strict_types=1);

namespace App\Order\Application;

use App\Cart\Application\CartService;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Enum\DeliveryType;
use App\Order\Domain\Enum\OrderStatus;
use App\Order\Domain\ValueObject\DeliveryAddress;
use App\Order\Infrastructure\Repository\OrderRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CartService $cartService,
        private EntityManagerInterface $entityManager,
        private OrderEmailNotifier $orderEmailNotifier,
    ) {
    }

    /**
     * @return Order[]
     */
    public function listForUser(User $user, int $page, int $limit): array
    {
        return $this->orderRepository->findByUser($user, $page, $limit);
    }

    public function findForUser(int $id, User $user): ?Order
    {
        return $this->orderRepository->findOneByIdAndUser($id, $user);
    }

    public function checkout(User $user, DeliveryType $deliveryType, ?DeliveryAddress $deliveryAddress): Order
    {
        return $this->entityManager->wrapInTransaction(function () use ($user, $deliveryType, $deliveryAddress): Order {
            $cart = $this->cartService->lockForUpdate($user);

            $order = Order::fromCart($cart, $deliveryType, $deliveryAddress);

            $this->entityManager->persist($order);

            $cart->clear();

            $this->entityManager->flush();

            $this->orderEmailNotifier->orderCreated($order);

            return $order;
        });
    }

    public function changeStatus(int $id, OrderStatus $status): Order
    {
        return $this->entityManager->wrapInTransaction(function () use ($id, $status): Order {
            $order = $this->orderRepository->find($id);

            if ($order === null) {
                throw new NotFoundHttpException('Order not found.');
            }

            $order->transitionTo($status);

            $this->entityManager->flush();

            $this->orderEmailNotifier->orderStatusChanged($order);

            return $order;
        });
    }
}
