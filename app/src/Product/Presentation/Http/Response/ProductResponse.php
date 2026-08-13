<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Response;

use App\Product\Domain\Entity\Product;

final readonly class ProductResponse
{
    public static function fromEntity(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'weight' => $product->getWeight(),
            'category' => $product->getCategory(),
        ];
    }
}
