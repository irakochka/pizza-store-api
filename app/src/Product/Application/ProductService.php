<?php

declare(strict_types=1);

namespace App\Product\Application;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Enum\ProductCategory;
use App\Product\Infrastructure\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private ProductCatalogCache $productCatalogCache,
    ) {
    }

    public function find(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    public function create(string $name, string $description, int $price, int $weight, ProductCategory $category): Product
    {
        $product = new Product($name, $description, $price, $weight, $category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->productCatalogCache->invalidate();

        return $product;
    }

    public function update(
        Product $product, ?string $name, ?string $description, ?int $price, ?int $weight, ?ProductCategory $category,
    ): void {
        $product->updateDetails(
            $name ?? $product->getName(),
            $description ?? $product->getDescription(),
            $price ?? $product->getPrice(),
            $weight ?? $product->getWeight(),
            $category ?? $product->getCategory(),
        );

        $this->entityManager->flush();

        $this->productCatalogCache->invalidate();
    }

    public function delete(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->productCatalogCache->invalidate();
    }
}
