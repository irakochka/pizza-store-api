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
        $user = $this->currentUser();

        $cart = $this->cartService->getOrCreateForUser($user);

        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'cart_item_update', methods: ['PATCH'])]
    public function updateItem(int $productId, #[MapRequestPayload] UpdateCartItemRequest $payload): JsonResponse
    {
        $user = $this->currentUser();

        $cart = $this->cartService->setProductQuantity(
            $user,
            $productId,
            $payload->quantity(),
        );

        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'cart_item_delete', methods: ['DELETE'])]
    public function deleteItem(int $productId): JsonResponse
    {
        $user = $this->currentUser();

        $cart = $this->cartService->removeProduct($user, $productId);

        return new JsonResponse(CartResponse::fromEntity($cart), Response::HTTP_OK);
    }

    #[Route('', name: 'cart_clear', methods: ['DELETE'])]
    public function clear(): Response
    {
        $user = $this->currentUser();

        $this->cartService->clear($user);

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
