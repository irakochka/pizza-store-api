<?php

declare(strict_types=1);

namespace App\Tests\Product\Presentation\Http\Controller;

use App\Product\Domain\Entity\Product;
use App\Tests\DataFixtures\ProductFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProductControllerTest extends WebTestCase
{
    private const MISSING_PRODUCT_ID = 999999;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        static::createClient();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $loader = new Loader();
        $loader->addFixture(new ProductFixtures());

        $purger = new ORMPurger($this->entityManager);

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testListProductsSuccess(): void
    {
        $client = static::getClient();
        $client->request('GET', '/products');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(3, $data['items']);
        self::assertSame('Маргарита', $data['items'][0]['name']);
        self::assertSame(1, $data['page']);
        self::assertSame(10, $data['limit']);
    }

    public function testListProductsErrorWithInvalidPagination(): void
    {
        $client = static::getClient();
        $client->request('GET', '/products?page=0');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testShowProductSuccess(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $client = static::getClient();
        $client->request('GET', '/products/' . $product->getId());

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame($product->getId(), $data['id']);
        self::assertSame('Маргарита', $data['name']);
        self::assertSame(500, $data['price']);
    }

    public function testShowProductReturnsNotFoundForMissingProduct(): void
    {
        $client = static::getClient();
        $client->request('GET', '/products/' . self::MISSING_PRODUCT_ID);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Product not found.', $data['message']);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateProductSuccess(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', '/products',
            [
                'name' => 'Гавайская',
                'description' => 'Пицца с курицей и ананасами',
                'price' => 720,
                'weight' => 500,
                'category' => 'pizza',
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Гавайская', $data['name']);
        self::assertSame(720, $data['price']);
        self::assertSame('pizza', $data['category']);
        self::assertArrayHasKey('id', $data);
    }

    public function testCreateProductReturnsValidationErrorForInvalidPayload(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', '/products',
            [
                'name' => '',
                'description' => '',
                'price' => 0,
                'weight' => 0,
                'category' => '',
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateProductSuccess(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $client = static::getClient();
        $client->jsonRequest('PATCH', '/products/' . $product->getId(),
            [
                'name' => 'Маргарита большая',
                'price' => 800,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Маргарита большая', $data['name']);
        self::assertSame(800, $data['price']);
        self::assertSame('Классическая пицца с томатами и сыром', $data['description']);
        self::assertSame(450, $data['weight']);
        self::assertSame('pizza', $data['category']);
    }

    public function testUpdateProductReturnsNotFoundForMissingProduct(): void
    {
        $client = static::getClient();
        $client->jsonRequest('PATCH', '/products/' . self::MISSING_PRODUCT_ID,
            [
                'name' => 'Маргарита большая',
                'price' => 800,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Product not found.', $data['message']);
    }

    public function testDeleteProductSuccess(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $client = static::getClient();
        $client->jsonRequest('DELETE', '/products/' . $product->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteProductReturnsNotFoundForMissingProduct(): void
    {
        $client = static::getClient();
        $client->jsonRequest('DELETE', '/products/' . self::MISSING_PRODUCT_ID);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Product not found.', $data['message']);
    }
}
