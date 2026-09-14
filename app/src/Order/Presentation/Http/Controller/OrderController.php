<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Controller;

use App\Order\Application\OrderService;
use App\Order\Domain\Entity\Order;
use App\Order\Presentation\Http\Request\CreateOrderRequest;
use App\Order\Presentation\Http\Request\UpdateOrderStatusRequest;
use App\Order\Presentation\Http\Response\OrderResponse;
use App\Shared\Presentation\Http\Request\PaginationRequest;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders', format: 'json')]
#[IsGranted('ROLE_USER')]
final class OrderController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    #[Route('', name: 'order_list', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST, mapWhenEmpty: true)]
        PaginationRequest $query,
    ): JsonResponse {
        $orders = $this->orderService->listForUser($user, $query->page, $query->limit);

        return new JsonResponse([
            'items' => array_map(
                static fn (Order $order): array => OrderResponse::fromEntity($order),
                $orders,
            ),
            'page' => $query->page,
            'limit' => $query->limit,
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $order = $this->orderService->findForUser($id, $user);

        if ($order === null) {
            return new JsonResponse(['message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(OrderResponse::fromEntity($order), Response::HTTP_OK);
    }

    #[Route('', name: 'order_create', methods: ['POST'])]
    public function create(#[CurrentUser] User $user, #[MapRequestPayload] CreateOrderRequest $payload): JsonResponse
    {
        $order = $this->orderService->checkout(
            $user,
            $payload->deliveryType(),
            $payload->deliveryAddress(),
        );

        return new JsonResponse(OrderResponse::fromEntity($order), Response::HTTP_CREATED);
    }

    #[Route('/{id}/status', name: 'order_status_update', methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateOrderStatusRequest $payload,
    ): JsonResponse {
        $order = $this->orderService->changeStatus(
            $id,
            $payload->status,
        );

        return new JsonResponse(OrderResponse::fromEntity($order), Response::HTTP_OK);
    }
}
