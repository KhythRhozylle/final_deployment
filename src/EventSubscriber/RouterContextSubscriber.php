<?php

namespace App\EventSubscriber;

use App\Service\AppUrlService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Forces the router to use APP_URL in production so emails/OAuth links use the public host
 * (Railway proxy), not an internal container hostname.
 */
final class RouterContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AppUrlService $appUrlService,
        private readonly RouterInterface $router,
        private readonly string $kernelEnvironment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->kernelEnvironment !== 'prod') {
            return;
        }

        $baseUrl = $this->appUrlService->getBaseUrl();
        if ($baseUrl === null) {
            return;
        }

        $parts = parse_url($baseUrl);
        if ($parts === false || !isset($parts['host'])) {
            return;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $context = $this->router->getContext();
        $context->setHost($parts['host']);
        $context->setScheme($scheme);

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if ($port !== null) {
            if ($scheme === 'https') {
                $context->setHttpsPort($port);
            } else {
                $context->setHttpPort($port);
            }
        } elseif ($scheme === 'https') {
            $context->setHttpsPort(443);
        } else {
            $context->setHttpPort(80);
        }
    }
}
