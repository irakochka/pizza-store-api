<?php

declare(strict_types=1);

namespace App\Tests\Product\Application;

use App\Product\Application\ProductCatalogCache;
use App\Product\Application\ProductCatalogReader;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Enum\ProductCategory;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Tests\Support\ApiTestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class ProductCatalogCacheTest extends ApiTestCase
{
    private ProductCatalogCache $productCatalogCache;

    protected function setUp(): void
    {
        static::createClient();

        $this->loadFixtures();

        /** @var ProductRepository $productRepository */
        $productRepository = self::getContainer()->get(ProductRepository::class);

        $productCatalogReader = new ProductCatalogReader($productRepository);

        $this->productCatalogCache = new ProductCatalogCache(
            $productCatalogReader,
            new ArrayAdapter(),
            new NullLogger(),
            3600,
        );
    }

    public function testListStoresResponseInCacheAndReturnsCachedValue(): void
    {
        $firstResponse = $this->productCatalogCache->list(1, 10);

        $this->entityManager->persist(new Product(
            'Гавайская',
            'Пицца с курицей и ананасами',
            720,
            500,
            ProductCategory::Pizza,
        ));
        $this->entityManager->flush();

        $secondResponse = $this->productCatalogCache->list(1, 10);

        self::assertSame($firstResponse, $secondResponse);
        self::assertCount(3, $secondResponse['items']);
        self::assertSame('Маргарита', $secondResponse['items'][0]['name']);
        self::assertSame(1, $secondResponse['page']);
        self::assertSame(10, $secondResponse['limit']);
    }

    public function testListUsesDifferentCacheKeysForDifferentPages(): void
    {
        $firstPage = $this->productCatalogCache->list(1, 1);
        $secondPage = $this->productCatalogCache->list(2, 1);

        self::assertSame('Маргарита', $firstPage['items'][0]['name']);
        self::assertSame('Пепперони', $secondPage['items'][0]['name']);
        self::assertSame(1, $firstPage['page']);
        self::assertSame(2, $secondPage['page']);
        self::assertSame(1, $firstPage['limit']);
        self::assertSame(1, $secondPage['limit']);
    }

    public function testInvalidateChangesCatalogVersion(): void
    {
        $beforeInvalidate = $this->productCatalogCache->list(1, 10);

        $this->entityManager->persist(new Product(
            'Гавайская',
            'Пицца с курицей и ананасами',
            720,
            500,
            ProductCategory::Pizza,
        ));
        $this->entityManager->flush();

        $cachedResponse = $this->productCatalogCache->list(1, 10);

        $this->productCatalogCache->invalidate();

        $afterInvalidate = $this->productCatalogCache->list(1, 10);

        self::assertSame($beforeInvalidate, $cachedResponse);
        self::assertCount(3, $cachedResponse['items']);
        self::assertCount(4, $afterInvalidate['items']);
        self::assertSame('Гавайская', $afterInvalidate['items'][3]['name']);
    }

    public function testListFallsBackToDatabaseWhenCacheIsUnavailable(): void
    {
        /** @var ProductRepository $productRepository */
        $productRepository = self::getContainer()->get(ProductRepository::class);

        $productCatalogReader = new ProductCatalogReader($productRepository);

        $productCatalogCache = new ProductCatalogCache(
            $productCatalogReader,
            new UnavailableCache(),
            new NullLogger(),
            3600,
        );

        $response = $productCatalogCache->list(1, 10);

        self::assertCount(3, $response['items']);
        self::assertSame('Маргарита', $response['items'][0]['name']);
        self::assertSame('pizza', $response['items'][0]['category']);
    }
}

final class UnavailableCache implements CacheInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        throw new RuntimeException('Cache is unavailable.');
    }

    public function delete(string $key): bool
    {
        throw new RuntimeException('Cache is unavailable.');
    }
}
