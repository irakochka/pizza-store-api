<?php

declare(strict_types=1);

namespace App\Product\Application;

use App\Product\Domain\Entity\Product;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Product\Presentation\Http\Response\ProductResponse;

final readonly class ProductCatalogReader
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {
    }

    /**
     * @return array{
     *     items: array<int, array{id: int|null, name: string, description: string, price: int, weight: int, category: string}>,
     *     page: int,
     *     limit: int
     * }
     */
    public function list(int $page, int $limit): array
    {
        $products = $this->productRepository->findBy(
            [],
            ['id' => 'ASC'],
            $limit,
            ($page - 1) * $limit,
        );

        return [
            'items' => array_map(static fn (Product $product) => ProductResponse::fromEntity($product), $products),
            'page' => $page,
            'limit' => $limit,
        ];
    }
}
