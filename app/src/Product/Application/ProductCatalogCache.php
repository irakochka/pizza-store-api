<?php

declare(strict_types=1);

namespace App\Product\Application;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

final readonly class ProductCatalogCache
{
    private const CACHE_FORMAT_VERSION = 1;
    private const VERSION_KEY = 'product_catalog.version';

    public function __construct(
        private ProductCatalogReader $productCatalogReader,
        private CacheInterface $productCatalogCache,
        private LoggerInterface $logger,
        private int $productCatalogCacheTtl,
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
        $key = $this->key($page, $limit);

        try {
            return $this->productCatalogCache->get($key, function ($item) use ($page, $limit): array {
                $item->expiresAfter($this->productCatalogCacheTtl);

                return $this->productCatalogReader->list($page, $limit);
            });
        } catch (Throwable $exception) {
            $this->logger->warning('Product catalog cache is unavailable, falling back to database.', [
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return $this->productCatalogReader->list($page, $limit);
        }
    }

    public function invalidate(): void
    {
        try {
            $this->productCatalogCache->delete(self::VERSION_KEY);
        } catch (Throwable $exception) {
            $this->logger->warning('Product catalog cache version invalidation failed.', [
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }
    }

    private function key(int $page, int $limit): string
    {
        return sprintf(
            'product_catalog.format.%d.version.%s.page.%d.limit.%d',
            self::CACHE_FORMAT_VERSION,
            $this->version(),
            $page,
            $limit,
        );
    }

    private function version(): string
    {
        try {
            return $this->productCatalogCache->get(
                self::VERSION_KEY,
                static fn (): string => self::newVersion(),
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Product catalog cache version is unavailable, using request-local version.', [
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return self::newVersion();
        }
    }

    private static function newVersion(): string
    {
        return str_replace('.', '_', sprintf('%.6F', microtime(true)));
    }
}
