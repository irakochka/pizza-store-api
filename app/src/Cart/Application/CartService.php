<?php

declare(strict_types=1);

namespace App\Cart\Application;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Shared\Application\ConcurrencyBarrier;
use App\User\Domain\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CartService
{
    public function __construct(
        private CartRepository $cartRepository,
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private ConcurrencyBarrier $concurrencyBarrier,
    ) {
    }

    public function getOrCreateForUser(User $user): Cart
    {
        return $this->entityManager->wrapInTransaction(function () use ($user): Cart {
            return $this->findOrCreateForUpdate($user);
        });
    }

    public function setProductQuantity(User $user, int $productId, int $quantity): Cart
    {
        return $this->entityManager->wrapInTransaction(function () use ($user, $productId, $quantity): Cart {
            $cart = $this->findOrCreateForUpdate($user);

            $product = $this->productRepository->find($productId);

            if ($product === null) {
                throw new ProductNotFoundException('Product not found.');
            }

            $cart->setProductQuantity($product, $quantity);

            $this->entityManager->flush();

            return $cart;
        });
    }

    public function removeProduct(User $user, int $productId): Cart
    {
        return $this->setProductQuantity($user, $productId, 0);
    }

    public function clear(User $user): void
    {
        $this->entityManager->wrapInTransaction(function () use ($user): void {
            $cart = $this->findOrCreateForUpdate($user);

            $cart->clear();

            $this->entityManager->flush();
        });
    }

    public function lockForUpdate(User $user): Cart
    {
        return $this->findOrCreateForUpdate($user);
    }

    private function findOrCreateForUpdate(User $user): Cart
    {
        $this->concurrencyBarrier->wait('cart.find_or_create_for_update');

        $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);

        $cart = $this->cartRepository->findOneByUser($user);

        if ($cart === null) {
            $cart = new Cart($user);

            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        $this->entityManager->lock($cart, LockMode::PESSIMISTIC_WRITE);

        return $cart;
    }
}
