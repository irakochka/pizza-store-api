<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

#[AsEventListener(event: 'kernel.exception')]
final readonly class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AuthenticationException) {
            $event->setResponse(new JsonResponse(
                ['message' => 'Authentication required.'],
                JsonResponse::HTTP_UNAUTHORIZED,
            ));

            return;
        }

        if ($exception instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse(
                ['message' => 'Access denied.'],
                JsonResponse::HTTP_FORBIDDEN,
            ));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['message' => $this->safeMessage($exception)],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));
        }
    }

    private function safeMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if ($message === '') {
            return 'Request failed.';
        }

        return $message;
    }
}
