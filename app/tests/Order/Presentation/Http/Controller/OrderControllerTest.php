<?php

declare(strict_types=1);

namespace App\Tests\Order\Presentation\Http\Controller;

use App\Order\Domain\Entity\Order;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class OrderControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::createClient();

        $this->loadFixtures();
    }

    public function testCreateOrderRejectsEmptyCart(): void
    {
        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/orders',
            ['deliveryType' => 'pickup'],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Cart must contain at least one product.', $data['message']);
    }

    public function testCreatePickupOrderSuccess(): void
    {
        $productId = $this->productId('Маргарита');

        $this->addProductToCart($productId, 2);

        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/orders',
            ['deliveryType' => 'pickup'],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('created', $data['status']);
        self::assertSame('pickup', $data['deliveryType']);
        self::assertNull($data['deliveryAddress']);
        self::assertCount(1, $data['items']);
        self::assertSame($productId, $data['items'][0]['productId']);
        self::assertSame('Маргарита', $data['items'][0]['productName']);
        self::assertSame(500, $data['items'][0]['unitPrice']);
        self::assertSame(2, $data['items'][0]['quantity']);
        self::assertSame(1000, $data['totalPrice']);
    }

    public function testCreateOrderClearsCart(): void
    {
        $productId = $this->productId('Маргарита');

        $this->addProductToCart($productId, 2);

        $client = static::getClient();
        $headers = $this->userAuthorizationHeader();

        $client->jsonRequest('POST', '/orders', ['deliveryType' => 'pickup'], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('GET', '/cart', [], [], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame([], $data['items']);
        self::assertSame(0, $data['totalPrice']);
    }

    public function testCreateCourierOrderSuccess(): void
    {
        $productId = $this->productId('Кола');

        $this->addProductToCart($productId, 3);

        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/orders',
            [
                'deliveryType' => 'courier',
                'deliveryAddress' => [
                    'region' => 'Москва',
                    'city' => 'Москва',
                    'street' => 'Тверская',
                    'house' => '1',
                    'entrance' => '2',
                    'apartment' => '10',
                    'postalCode' => '125009',
                ],
            ],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('courier', $data['deliveryType']);
        self::assertSame('Москва', $data['deliveryAddress']['region']);
        self::assertSame('Москва', $data['deliveryAddress']['city']);
        self::assertSame('Тверская', $data['deliveryAddress']['street']);
        self::assertSame('1', $data['deliveryAddress']['house']);
        self::assertSame('2', $data['deliveryAddress']['entrance']);
        self::assertSame('10', $data['deliveryAddress']['apartment']);
        self::assertSame('125009', $data['deliveryAddress']['postalCode']);
        self::assertSame(450, $data['totalPrice']);
    }

    public function testCreateCourierOrderRequiresDeliveryAddress(): void
    {
        $productId = $this->productId('Маргарита');

        $this->addProductToCart($productId);

        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/orders',
            ['deliveryType' => 'courier'],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateCourierOrderRejectsInvalidDeliveryAddress(): void
    {
        $productId = $this->productId('Маргарита');

        $this->addProductToCart($productId);

        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/orders',
            [
                'deliveryType' => 'courier',
                'deliveryAddress' => [
                    'region' => 'Москва',
                    'city' => 'Москва',
                    'street' => '',
                    'house' => '1',
                    'entrance' => '2',
                    'apartment' => '10',
                ],
            ],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testListOrdersSuccess(): void
    {
        $productId = $this->productId('Маргарита');

        $this->addProductToCart($productId, 1);

        $client = static::getClient();
        $headers = $this->userAuthorizationHeader();

        $client->jsonRequest('POST', '/orders', ['deliveryType' => 'pickup'], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('GET', '/orders?page=1&limit=10', [], [], $headers);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(1, $data['items']);
        self::assertSame(1, $data['page']);
        self::assertSame(10, $data['limit']);
        self::assertSame('created', $data['items'][0]['status']);
    }

    public function testListOrdersReturnsBadRequestForInvalidPagination(): void
    {
        $client = static::getClient();

        $client->request('GET', '/orders?page=0', [], [], $this->userAuthorizationHeader());

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testShowOrderSuccess(): void
    {
        $orderId = $this->createPickupOrder();

        $client = static::getClient();

        $client->request('GET', '/orders/' . $orderId, [], [], $this->userAuthorizationHeader());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame($orderId, $data['id']);
        self::assertSame('created', $data['status']);
    }

    public function testShowOrderReturnsNotFoundForMissingOrder(): void
    {
        $client = static::getClient();

        $client->request('GET', '/orders/999999', [], [], $this->userAuthorizationHeader());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateOrderStatusSuccess(): void
    {
        $orderId = $this->createPickupOrder();

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/orders/' . $orderId . '/status',
            ['status' => 'paid'],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('paid', $data['status']);
    }

    public function testParallelPaidAndCancelledStatusUpdatesDoNotOverwriteCancelledWithPaid(): void
    {
        $orderId = $this->createPickupOrder();

        $headers = $this->adminAuthorizationHeader();
        $token = substr($headers['HTTP_AUTHORIZATION'], 7);

        $responses = $this->withConcurrencyBarrier(
            'order.change_status_before_find',
            2,
            fn (): array => $this->runParallelRequests([
                [
                    'method' => 'PATCH',
                    'path' => '/orders/' . $orderId . '/status',
                    'token' => $token,
                    'body' => ['status' => 'paid'],
                ],
                [
                    'method' => 'PATCH',
                    'path' => '/orders/' . $orderId . '/status',
                    'token' => $token,
                    'body' => ['status' => 'cancelled'],
                ],
            ]),
        );

        self::assertCount(2, $responses);

        $statuses = array_column($responses, 'status');

        foreach ($statuses as $status) {
            self::assertContains($status, [
                Response::HTTP_OK,
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ]);
        }

        self::assertContains(Response::HTTP_OK, $statuses);

        $client = static::getClient();

        $client->request('GET', '/orders/' . $orderId, [], [], $this->userAuthorizationHeader());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('cancelled', $data['status']);
    }

    public function testUpdateOrderStatusRejectsInvalidTransition(): void
    {
        $orderId = $this->createPickupOrder();

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/orders/' . $orderId . '/status',
            ['status' => 'completed'],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Cannot change order status from created to completed.', $data['message']);
    }

    public function testUpdateOrderStatusReturnsValidationErrorForInvalidStatus(): void
    {
        $orderId = $this->createPickupOrder();

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/orders/' . $orderId . '/status',
            ['status' => 'unknown'],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testShowOrderReturnsNotFoundForAnotherUserOrder(): void
    {
        $orderId = $this->createPickupOrder();

        $client = static::getClient();

        $client->request('GET', '/orders/' . $orderId, [], [], $this->adminAuthorizationHeader());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateOrderStatusForbiddenForRegularUser(): void
    {
        $orderId = $this->createPickupOrder();

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/orders/' . $orderId . '/status',
            ['status' => 'paid'],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListOrdersRequiresAuthentication(): void
    {
        $client = static::getClient();

        $client->request('GET', '/orders');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateOrderRequiresAuthentication(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/orders', ['deliveryType' => 'pickup']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testParallelCheckoutCreatesSingleOrderFromCart(): void
    {
        $productId = $this->productId('Маргарита');

        $headers = $this->userAuthorizationHeader();
        $token = substr($headers['HTTP_AUTHORIZATION'], 7);

        $client = static::getClient();

        $client->jsonRequest(
            'PATCH',
            '/cart/items/' . $productId,
            ['quantity' => 2],
            $headers,
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $responses = $this->runParallelRequests([
            [
                'method' => 'POST',
                'path' => '/orders',
                'token' => $token,
                'body' => ['deliveryType' => 'pickup'],
            ],
            [
                'method' => 'POST',
                'path' => '/orders',
                'token' => $token,
                'body' => ['deliveryType' => 'pickup'],
            ],
        ]);

        $statuses = array_column($responses, 'status');
        sort($statuses);

        self::assertSame([
            Response::HTTP_CREATED,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ], $statuses);

        $orders = $this->entityManager
            ->getRepository(Order::class)
            ->findAll();

        self::assertCount(1, $orders);
    }

    private function createPickupOrder(): int
    {
        $productId = $this->productId('Маргарита');

        $this->addProductToCart($productId, 1);

        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/orders',
            ['deliveryType' => 'pickup'],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $data);

        return $data['id'];
    }
}
