<?php

declare(strict_types=1);

namespace App\Tests\User\Presentation\Http\Controller;

use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Support\ApiTestCase;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthControllerTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        static::createClient();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $loader = new Loader();
        $loader->addFixture(new UserFixtures($passwordHasher));

        $purger = new ORMPurger($this->entityManager);

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testRegisterSuccess(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/register', [
            'name' => 'New User',
            'phone' => '+79990000003',
            'email' => 'new-user@example.com',
            'password' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $data);
        self::assertSame('new-user@example.com', $data['email']);
        self::assertSame('New User', $data['name']);
        self::assertSame('+79990000003', $data['phone']);
        self::assertSame(['ROLE_USER'], $data['roles']);
        self::assertArrayNotHasKey('password', $data);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'new-user@example.com']);

        self::assertNotNull($user);
    }

    public function testRegisterReturnsValidationErrorForInvalidPayload(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/register', [
            'name' => '',
            'phone' => '',
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterReturnsConflictForExistingEmail(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/register', [
            'name' => 'Existing User',
            'phone' => '+79990000002',
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testLoginSuccess(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ]);

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('accessToken', $data);
        self::assertSame('Bearer', $data['tokenType']);
        self::assertSame(3600, $data['expiresIn']);
    }

    public function testLoginReturnsUnauthorizedForInvalidCredentials(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Invalid credentials.', $data['message']);
    }

    public function testLoginReturnsValidationErrorForInvalidPayload(): void
    {
        $client = static::getClient();

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMeSuccess(): void
    {
        $client = static::getClient();

        $client->request(
            'GET',
            '/auth/me',
            [],
            [],
            $this->authorizationHeader('user@example.com', 'user123'),
        );

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('user@example.com', $data['email']);
        self::assertSame('User', $data['name']);
        self::assertSame('+79990000002', $data['phone']);
        self::assertSame(['ROLE_USER'], $data['roles']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testMeRequiresAuthentication(): void
    {
        $client = static::getClient();

        $client->request('GET', '/auth/me');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeReturnsUnauthorizedForInvalidToken(): void
    {
        $client = static::getClient();

        $client->request(
            'GET',
            '/auth/me',
            [],
            [],
            $this->bearerTokenHeader('invalid-token'),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Invalid token.', $data['message']);
    }
}
