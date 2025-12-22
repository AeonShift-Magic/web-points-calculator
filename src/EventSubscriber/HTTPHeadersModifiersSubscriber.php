<?php

declare(strict_types = 1);

namespace App\EventSubscriber;

use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class HTTPHeadersModifiersSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -2000],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $request->headers->remove('server');
        $request->headers->set('server', 'AeonShift');
        $request->headers->remove('X-Powered-By');
        $request->headers->set('X-Robots-Tag', 'all');
        $request->headers->set('x-robots-tag', 'all');

        if (! $event->isMainRequest()) {
            return;
        }

        /** @var string $route */
        $route = $request->attributes->get('_route');

        // 1. Skip for dev toolbar or in an array
        if (
            $route === '_wdt'
            || str_contains($route, 'admin')
        ) {
            return;
        }

        // 2. Only cache GET / HEAD
        if (! in_array($request->getMethod(), [Request::METHOD_GET, Request::METHOD_HEAD], true)) {
            return;
        }

        // 3. Only cache successful responses
        if ($response->getStatusCode() !== 200) {
            return;
        }

        // 4. Do NOT cache if a session exists (user connected or not)
        if ($request->hasSession() && $this->security->getUser() !== null && $request->getSession()->isStarted()) {
            return;
        }

        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate');
    }
}
