<?php

declare(strict_types=1);

namespace App\Cart\Presentation\Http\Controller;

use App\Cart\Application\CartService;
use App\Cart\Presentation\Http\Request\UpdateCartItemRequest;
use App\Cart\Presentation\Http\Response\CartResponse;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/cart', format: 'json')]
final class CartController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Security $security,
    ) {
    }

    #[Route('', name: 'cart_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        // 1. получить текущего пользователя
        $user = $this->currentUser();

        // 2. получить или создать корзину пользователя
        $cart = $this->cartService->getOrCreateForUser($user);

        // 3. вернуть CartResponse
        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'cart_item_update', methods: ['PATCH'])]
    public function updateItem(int $productId, #[MapRequestPayload] UpdateCartItemRequest $payload): JsonResponse
    {
        // 1. получить текущего пользователя
        $user = $this->currentUser();

        // 2. получить quantity из request DTO
        // 3. вызвать CartService::setProductQuantity($user, $productId, $quantity)
        $cart = $this->cartService->setProductQuantity(
            $user,
            $productId,
            $payload->quantity(),
        );

        // 4. вернуть CartResponse
        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'cart_item_delete', methods: ['DELETE'])]
    public function deleteItem(int $productId): JsonResponse
    {
        // 1. получить текущего пользователя
        $user = $this->currentUser();

        // 2. вызвать CartService::removeProduct($user, $productId)
        $cart = $this->cartService->removeProduct($user, $productId);

        // 3. вернуть CartResponse
        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('', name: 'cart_clear', methods: ['DELETE'])]
    public function clear(): Response
    {
        // 1. получить текущего пользователя
        $user = $this->currentUser();

        // 2. вызвать CartService::clear($user)
        $this->cartService->clear($user);

        // 3. вернуть 204 No Content
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AuthenticationException('Authentication required.');
        }

        return $user;
    }
}
