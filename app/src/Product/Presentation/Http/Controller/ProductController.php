<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Controller;

use App\Product\Domain\Entity\Product;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Product\Presentation\Http\Request\CreateProductRequest;
use App\Product\Presentation\Http\Request\ListProductsRequest;
use App\Product\Presentation\Http\Request\UpdateProductRequest;
use App\Product\Presentation\Http\Response\ProductResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', format: 'json')]
final class ProductController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            return new JsonResponse(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(ProductResponse::fromEntity($product), Response::HTTP_OK);
    }

    #[Route('', name: 'product_list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST, mapWhenEmpty: true)]
        ListProductsRequest $query,
    ): JsonResponse {
        $products = $this->productRepository->findBy(
            [],
            ['id' => 'ASC'],
            $query->limit,
            ($query->page - 1) * $query->limit
        );

        $items = array_map(static fn(Product $product) => ProductResponse::fromEntity($product), $products);

        return new JsonResponse(
            [
                'items' => $items,
                'page' => $query->page,
                'limit' => $query->limit,
            ],
            Response::HTTP_OK,
        );
    }

    #[Route('', name: 'product_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductRequest $payload): JsonResponse
    {
        $product = new Product(
            $payload->normalizedName(),
            $payload->normalizedDescription(),
            $payload->price,
            $payload->weight,
            $payload->normalizedCategory(),
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return new JsonResponse(ProductResponse::fromEntity($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'product_update', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload] UpdateProductRequest $payload): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            return new JsonResponse(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $product->updateDetails(
            $payload->normalizedName() ?? $product->getName(),
            $payload->normalizedDescription() ?? $product->getDescription(),
            $payload->price ?? $product->getPrice(),
            $payload->weight ?? $product->getWeight(),
            $payload->normalizedCategory() ?? $product->getCategory(),
        );

        $this->entityManager->flush();

        return new JsonResponse(ProductResponse::fromEntity($product), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            return new JsonResponse(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
