<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Response;

use App\Product\Domain\Entity\Product;

final readonly class ProductResponse
{
    /**
     * @return array{id: int|null, name: string, description: string, price: int, weight: int, category: string}
     */
    public static function fromEntity(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'weight' => $product->getWeight(),
            'category' => $product->getCategory()->value,
        ];
    }
}
