<?php

declare(strict_types=1);

namespace App\Product\Application;

use App\Product\Domain\Entity\Product;
use App\Product\Infrastructure\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function find(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    /**
     * @return Product[]
     */
    public function list(int $page, int $limit): array
    {
        return $this->productRepository->findBy(
            [],
            ['id' => 'ASC'],
            $limit,
            ($page - 1) * $limit,
        );
    }

    public function create(string $name, string $description, int $price, int $weight, string $category,): Product {
        $product = new Product($name, $description, $price, $weight, $category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    public function update(
        Product $product, ?string $name, ?string $description, ?int $price, ?int $weight, ?string $category
    ): void {
        $product->updateDetails(
            $name ?? $product->getName(),
            $description ?? $product->getDescription(),
            $price ?? $product->getPrice(),
            $weight ?? $product->getWeight(),
            $category ?? $product->getCategory(),
        );

        $this->entityManager->flush();
    }

    public function delete(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
}
