<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Product\Domain\Entity\Product;
use App\User\Domain\Entity\User;

trait FindsFixtureEntities
{
    protected function productId(string $name): int
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => $name]);

        self::assertNotNull($product);

        $id = $product->getId();

        self::assertNotNull($id);

        return $id;
    }

    protected function user(string $email = 'user@example.com'): User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        self::assertNotNull($user);

        return $user;
    }
}
