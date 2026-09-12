<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Response;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;

use const DATE_ATOM;

final readonly class OrderResponse
{
    /**
     * @return array{
     *     id: int|null,
     *     status: string,
     *     deliveryType: string,
     *     deliveryAddress: array<string, string>|null,
     *     items: list<array{productId: int, productName: string, unitPrice: int, quantity: int, lineTotal: int}>,
     *     totalPrice: int,
     *     createdAt: string
     * }
     */
    public static function fromEntity(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'status' => $order->getStatus()->value,
            'deliveryType' => $order->getDeliveryType()->value,
            'deliveryAddress' => $order->getDeliveryAddress(),
            'items' => array_map(
                static fn (OrderItem $item): array => self::item($item),
                $order->getItems()->toArray(),
            ),
            'totalPrice' => $order->getTotalPrice(),
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array{productId: int, productName: string, unitPrice: int, quantity: int, lineTotal: int}
     */
    private static function item(OrderItem $item): array
    {
        return [
            'productId' => $item->getProductId(),
            'productName' => $item->getProductName(),
            'unitPrice' => $item->getUnitPrice(),
            'quantity' => $item->getQuantity(),
            'lineTotal' => $item->getTotalPrice(),
        ];
    }
}
