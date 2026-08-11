<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Controller;

use App\Product\Domain\Entity\Product;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Product\Presentation\Http\Request\CreateProductRequest;
use App\Product\Presentation\Http\Request\UpdateProductRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
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

        return new JsonResponse($this->productToArray($product), Response::HTTP_OK);
    }

    #[Route('', name: 'product_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 10)));

        $products = $this->productRepository->findBy(
            [],
            ['id' => 'ASC'],
            $limit,
            ($page - 1) * $limit
        );

        $items = array_map(fn(Product $product) => $this->productToArray($product), $products);

        return new JsonResponse(
            [
                'items' => $items,
                'page' => $page,
                'limit' => $limit,
            ],
            Response::HTTP_OK,
        );
    }

    #[Route('', name: 'product_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductRequest $payload): JsonResponse
    {
        $product = new Product(
            $payload->name,
            $payload->description,
            $payload->price,
            $payload->weight,
            $payload->category,
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return new JsonResponse($this->productToArray($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'product_update', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload] UpdateProductRequest $payload): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            return new JsonResponse(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $product->updateDetails(
            $payload->name ?? $product->getName(),
            $payload->description ?? $product->getDescription(),
            $payload->price ?? $product->getPrice(),
            $payload->weight ?? $product->getWeight(),
            $payload->category ?? $product->getCategory(),
        );

        $this->entityManager->flush();

        return new JsonResponse($this->productToArray($product), Response::HTTP_OK);
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

    private function productToArray(Product $product): array
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
