<?php

declare(strict_types=1);

namespace App\User\Application;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Repository\UserRepository;
use App\User\Presentation\Http\Request\LoginRequest;
use App\User\Presentation\Http\Request\RegisterUserRequest;
use App\User\Presentation\Http\Response\AuthTokenResponse;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private WelcomeSmsSender $welcomeSmsSender,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtTokenManager,
        private int $jwtTokenTtl,
    ) {
    }

    public function register(RegisterUserRequest $request): User
    {
        if ($this->userRepository->findOneBy(['email' => $request->email]) !== null) {
            throw new ConflictHttpException('User with this email already exists.');
        }

        $user = new User(
            $request->name,
            $request->phone,
            $request->email,
        );
        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));
        $user->setRoles([UserRole::User->value]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->welcomeSmsSender->send($user);

        return $user;
    }

    public function login(LoginRequest $request): AuthTokenResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $request->email]);

        if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        return new AuthTokenResponse(
            $this->jwtTokenManager->create($user),
            'Bearer',
            $this->jwtTokenTtl,
        );
    }
}
