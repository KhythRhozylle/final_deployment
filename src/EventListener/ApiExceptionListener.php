<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();
        
        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Handle different authentication/authorization exceptions
        if ($exception instanceof AuthenticationException) {
            $response = new JsonResponse([
                'error' => 'Authentication failed',
                'message' => 'Full authentication is required to access this resource'
            ], 401);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof UnauthorizedHttpException) {
            $response = new JsonResponse([
                'error' => 'Authentication required',
                'message' => 'Full authentication is required to access this resource'
            ], 401);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof AccessDeniedHttpException) {
            $response = new JsonResponse([
                'error' => 'Access denied',
                'message' => 'You do not have permission to access this resource'
            ], 403);
            $event->setResponse($response);
            return;
        }

        // Handle API Platform 401 errors
        if (method_exists($exception, 'getStatusCode') && $exception->getStatusCode() === 401) {
            $response = new JsonResponse([
                'error' => 'Authentication required',
                'message' => 'Full authentication is required to access this resource'
            ], 401);
            $event->setResponse($response);
            return;
        }
    }
}
