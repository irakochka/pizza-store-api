<?php

declare(strict_types=1);

namespace App\Tests\Cart\Presentation\Http\Controller;

use App\Cart\Domain\Entity\Cart;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CartControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::createClient();

        $this->loadFixtures();
    }

    public function testShowCartRequiresAuthentication(): void
    {
        $client = static::getClient();

        $client->request('GET', '/cart');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testShowCartReturnsEmptyCart(): void
    {
        $client = static::getClient();

        $client->request('GET', '/cart', [], [], $this->userAuthorizationHeader());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $data);
        self::assertSame([], $data['items']);
        self::assertSame(0, $data['totalPrice']);
    }

    public function testUpdateCartItemAddsProduct(): void
    {
        $productId = $this->productId('Маргарита');

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => 2],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(1, $data['items']);
        self::assertSame($productId, $data['items'][0]['productId']);
        self::assertSame('Маргарита', $data['items'][0]['name']);
        self::assertSame(2, $data['items'][0]['quantity']);
        self::assertSame(1000, $data['items'][0]['lineTotal']);
        self::assertSame(1000, $data['totalPrice']);
    }

    public function testUpdateCartItemChangesExistingProductQuantity(): void
    {
        $productId = $this->productId('Маргарита');

        $client = static::getClient();
        $headers = $this->userAuthorizationHeader();

        $client->jsonRequest('PATCH', '/cart/items/' . $productId, ['quantity' => 2], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('PATCH', '/cart/items/' . $productId, ['quantity' => 3], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(1, $data['items']);
        self::assertSame(3, $data['items'][0]['quantity']);
        self::assertSame(1500, $data['totalPrice']);
    }

    public function testDeleteCartItemRemovesProduct(): void
    {
        $productId = $this->productId('Маргарита');

        $client = static::getClient();
        $headers = $this->userAuthorizationHeader();

        $client->jsonRequest('PATCH', '/cart/items/' . $productId, ['quantity' => 2], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request('DELETE', '/cart/items/' . $productId, [], [], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame([], $data['items']);
        self::assertSame(0, $data['totalPrice']);
    }

    public function testClearCartRemovesAllProducts(): void
    {
        $pizzaId = $this->productId('Маргарита');
        $drinkId = $this->productId('Кола');

        $client = static::getClient();
        $headers = $this->userAuthorizationHeader();

        $client->jsonRequest('PATCH', '/cart/items/' . $pizzaId, ['quantity' => 2], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('PATCH', '/cart/items/' . $drinkId, ['quantity' => 3], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request('DELETE', '/cart', [], [], $headers);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/cart', [], [], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame([], $data['items']);
        self::assertSame(0, $data['totalPrice']);
    }

    public function testUpdateCartItemReturnsValidationErrorForInvalidQuantity(): void
    {
        $productId = $this->productId('Маргарита');

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => '2'],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateCartItemReturnsNotFoundForMissingProduct(): void
    {
        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/999999',
            ['quantity' => 1],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateCartItemRejectsMoreThanTenPizzas(): void
    {
        $productId = $this->productId('Маргарита');

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => 11],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Cart cannot contain more than 10 pizzas.', $data['message']);
    }

    public function testUpdateCartItemRejectsPizzaLimitAcrossDifferentProducts(): void
    {
        $margheritaId = $this->productId('Маргарита');
        $pepperoniId = $this->productId('Пепперони');

        $client = static::getClient();
        $headers = $this->userAuthorizationHeader();

        $client->jsonRequest('PATCH', '/cart/items/' . $margheritaId, ['quantity' => 6], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('PATCH', '/cart/items/' . $pepperoniId, ['quantity' => 5], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Cart cannot contain more than 10 pizzas.', $data['message']);
    }

    public function testUpdateCartItemRejectsMoreThanTwentyDrinks(): void
    {
        $productId = $this->productId('Кола');

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => 21],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Cart cannot contain more than 20 drinks.', $data['message']);
    }

    public function testParallelFirstCartCreationCreatesSingleCart(): void
    {
        $token = substr($this->userAuthorizationHeader()['HTTP_AUTHORIZATION'], 7);

        $responses = $this->runParallelRequests([
            [
                'method' => 'GET',
                'path' => '/cart',
                'token' => $token,
            ],
            [
                'method' => 'GET',
                'path' => '/cart',
                'token' => $token,
            ],
        ]);

        self::assertSame(Response::HTTP_OK, $responses[0]['status']);
        self::assertSame(Response::HTTP_OK, $responses[1]['status']);

        $user = $this->user();

        $carts = $this->entityManager
            ->getRepository(Cart::class)
            ->findBy(['user' => $user]);

        self::assertCount(1, $carts);
    }

    public function testParallelCartUpdatesDoNotExceedPizzaLimit(): void
    {
        $margheritaId = $this->productId('Маргарита');
        $pepperoniId = $this->productId('Пепперони');

        $headers = $this->userAuthorizationHeader();
        $token = substr($headers['HTTP_AUTHORIZATION'], 7);

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $margheritaId,
            ['quantity' => 9],
            $headers,
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $responses = $this->withConcurrencyBarrier(
            'cart.find_or_create_for_update',
            2,
            fn (): array => $this->runParallelRequests([
                [
                    'method' => 'PATCH',
                    'path' => '/cart/items/' . $pepperoniId,
                    'token' => $token,
                    'body' => ['quantity' => 1],
                ],
                [
                    'method' => 'PATCH',
                    'path' => '/cart/items/' . $pepperoniId,
                    'token' => $token,
                    'body' => ['quantity' => 2],
                ],
            ]),
        );

        $statuses = array_column($responses, 'status');
        sort($statuses);

        self::assertSame([
            Response::HTTP_OK,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ], $statuses);

        $client->request('GET', '/cart', [], [], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame(10, array_sum(array_column($data['items'], 'quantity')));
    }

    public function testParallelUpdatesOfSameCartItemDoNotCreateDuplicates(): void
    {
        $productId = $this->productId('Маргарита');

        $headers = $this->userAuthorizationHeader();
        $token = substr($headers['HTTP_AUTHORIZATION'], 7);

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => 1],
            $headers,
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $responses = $this->runParallelRequests([
            [
                'method' => 'PATCH',
                'path' => '/cart/items/' . $productId,
                'token' => $token,
                'body' => ['quantity' => 2],
            ],
            [
                'method' => 'PATCH',
                'path' => '/cart/items/' . $productId,
                'token' => $token,
                'body' => ['quantity' => 3],
            ],
        ]);

        self::assertSame(Response::HTTP_OK, $responses[0]['status']);
        self::assertSame(Response::HTTP_OK, $responses[1]['status']);

        $client->request('GET', '/cart', [], [], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(1, $data['items']);
        self::assertSame($productId, $data['items'][0]['productId']);
        self::assertContains($data['items'][0]['quantity'], [2, 3]);
    }
}
