<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\HttpFoundation\Response;

trait ManagesCart
{
    protected function addProductToCart(int $productId, int $quantity = 1): void
    {
        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => $quantity],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
