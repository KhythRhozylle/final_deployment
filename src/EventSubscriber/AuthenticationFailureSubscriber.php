<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationFailureSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            $response = new JsonResponse([
                'error' => 'Authentication failed',
                'message' => 'Full authentication is required to access this resource'
            ], 401);
            
            $event->setResponse($response);
            return;
        }

        // Handle 401 status codes from API Platform
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
