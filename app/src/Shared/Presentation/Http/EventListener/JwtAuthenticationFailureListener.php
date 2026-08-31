<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class JwtAuthenticationFailureListener
{
    #[AsEventListener(event: Events::JWT_NOT_FOUND)]
    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $event->setResponse(new JsonResponse(
            ['message' => 'Authentication required.'],
            JsonResponse::HTTP_UNAUTHORIZED,
        ));
    }

    #[AsEventListener(event: Events::JWT_INVALID)]
    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse(new JsonResponse(
            ['message' => 'Invalid token.'],
            JsonResponse::HTTP_UNAUTHORIZED,
        ));
    }

    #[AsEventListener(event: Events::JWT_EXPIRED)]
    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $event->setResponse(new JsonResponse(
            ['message' => 'Token expired.'],
            JsonResponse::HTTP_UNAUTHORIZED,
        ));
    }
}
