<?php

declare(strict_types=1);

namespace App\Cart\Presentation\Http\Response;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;

final readonly class CartResponse
{
    /**
     * @return array{id: int|null, items: list<array{productId: int|null, name: string, price: int, quantity: int, lineTotal: int}>, totalPrice: int}
     */
    public static function fromEntity(Cart $cart): array
    {
        $items = array_map(
            static fn (CartItem $item): array => self::item($item),
            $cart->getItems()->toArray(),
        );

        return [
            'id' => $cart->getId(),
            'items' => $items,
            'totalPrice' => array_sum(array_column($items, 'lineTotal')),
        ];
    }

    /**
     * @return array{productId: int|null, name: string, price: int, quantity: int, lineTotal: int}
     */
    private static function item(CartItem $item): array
    {
        $product = $item->getProduct();

        return [
            'productId' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'quantity' => $item->getQuantity(),
            'lineTotal' => $product->getPrice() * $item->getQuantity(),
        ];
    }
}
