<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Controller;

use App\User\Application\AuthService;
use App\User\Domain\Entity\User;
use App\User\Presentation\Http\Request\LoginRequest;
use App\User\Presentation\Http\Request\RegisterUserRequest;
use App\User\Presentation\Http\Response\UserResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/auth', format: 'json')]
final readonly class AuthController
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterUserRequest $request): JsonResponse
    {
        $user = $this->authService->register($request);

        return new JsonResponse(UserResponse::fromEntity($user), Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginRequest $request): JsonResponse
    {
        return new JsonResponse($this->authService->login($request), Response::HTTP_OK);
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse(UserResponse::fromEntity($user), Response::HTTP_OK);
    }
}
