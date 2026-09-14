<?php

declare(strict_types=1);

namespace App\Cart\Presentation\Http\Controller;

use App\Cart\Application\CartService;
use App\Cart\Presentation\Http\Request\UpdateCartItemRequest;
use App\Cart\Presentation\Http\Response\CartResponse;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart', format: 'json')]
#[IsGranted('ROLE_USER')]
final class CartController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    #[Route('', name: 'cart_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        $cart = $this->cartService->getOrCreateForUser($user);

        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'cart_item_update', methods: ['PATCH'])]
    public function updateItem(
        #[CurrentUser] User $user,
        int $productId,
        #[MapRequestPayload] UpdateCartItemRequest $payload,
    ): JsonResponse {
        $cart = $this->cartService->setProductQuantity(
            $user,
            $productId,
            $payload->quantity,
        );

        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'cart_item_delete', methods: ['DELETE'])]
    public function deleteItem(#[CurrentUser] User $user, int $productId): JsonResponse
    {
        $cart = $this->cartService->removeProduct($user, $productId);

        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('', name: 'cart_clear', methods: ['DELETE'])]
    public function clear(#[CurrentUser] User $user): Response
    {
        $this->cartService->clear($user);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
