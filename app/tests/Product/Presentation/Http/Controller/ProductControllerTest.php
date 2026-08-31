<?php

declare(strict_types=1);

namespace App\Tests\Product\Presentation\Http\Controller;

use App\Product\Domain\Entity\Product;
use App\Tests\DataFixtures\ProductFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Support\ApiTestCase;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProductControllerTest extends ApiTestCase
{
    private const MISSING_PRODUCT_ID = 999999;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        static::createClient();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $loader = new Loader();
        $loader->addFixture(new ProductFixtures());
        $loader->addFixture(new UserFixtures($passwordHasher));

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
            ],
            $this->adminAuthorizationHeader(),
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
            ],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateProductRequiresAuthentication(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/products', [
            'name' => 'Гавайская',
            'description' => 'Пицца с курицей и ананасами',
            'price' => 720,
            'weight' => 500,
            'category' => 'pizza',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateProductForbiddenForRegularUser(): void
    {
        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/products',
            [
                'name' => 'Гавайская',
                'description' => 'Пицца с курицей и ананасами',
                'price' => 720,
                'weight' => 500,
                'category' => 'pizza',
            ],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateProductReturnsUnauthorizedForInvalidToken(): void
    {
        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/products',
            [
                'name' => 'Гавайская',
                'description' => 'Пицца с курицей и ананасами',
                'price' => 720,
                'weight' => 500,
                'category' => 'pizza',
            ],
            $this->bearerTokenHeader('invalid-token'),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateProductReturnsUnauthorizedForExpiredToken(): void
    {
        $client = static::getClient();

        $client->jsonRequest(
            'POST',
            '/products',
            [
                'name' => 'Гавайская',
                'description' => 'Пицца с курицей и ананасами',
                'price' => 720,
                'weight' => 500,
                'category' => 'pizza',
            ],
            $this->bearerTokenHeader($this->expiredAdminToken()),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateProductSuccess(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $productId = $product->getId();

        $client = static::getClient();
        $client->jsonRequest('PATCH', '/products/' . $product->getId(),
            [
                'name' => 'Маргарита большая',
                'price' => 800,
            ],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request('GET', '/products/' . $productId);

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
            ],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Product not found.', $data['message']);
    }

    public function testUpdateProductRequiresAuthentication(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $client = static::getClient();
        $client->jsonRequest('PATCH', '/products/' . $product->getId(), [
            'name' => 'Маргарита большая',
            'price' => 800,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateProductForbiddenForRegularUser(): void
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
            ],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteProductSuccess(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $productId = $product->getId();

        $client = static::getClient();
        $client->request(
            'DELETE',
            '/products/' . $product->getId(),
            [],
            [],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/products/' . $productId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteProductReturnsNotFoundForMissingProduct(): void
    {
        $client = static::getClient();
        $client->request(
            'DELETE',
            '/products/' . self::MISSING_PRODUCT_ID,
            [],
            [],
            $this->adminAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Product not found.', $data['message']);
    }

    public function testDeleteProductRequiresAuthentication(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $client = static::getClient();
        $client->request('DELETE', '/products/' . $product->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteProductForbiddenForRegularUser(): void
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Маргарита']);

        self::assertNotNull($product);

        $client = static::getClient();
        $client->request(
            'DELETE',
            '/products/' . $product->getId(),
            [],
            [],
            $this->userAuthorizationHeader(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function expiredAdminToken(): string
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);

        self::assertInstanceOf(User::class, $user);

        $now = new DateTimeImmutable();

        return (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->issuedAt($now->modify('-2 hours'))
            ->expiresAt($now->modify('-1 hour'))
            ->relatedTo($user->getUserIdentifier())
            ->withClaim('roles', $user->getRoles())
            ->getToken(
                new Sha256(),
                InMemory::file('/var/www/app/config/jwt/private.pem', $_ENV['JWT_PASSPHRASE']),
            )
            ->toString();
    }
}
