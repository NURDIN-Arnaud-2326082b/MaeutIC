<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BannedUserApiAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TokenStorageInterface $tokenStorage
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Keep auth and availability endpoints reachable.
        $allowedPaths = [
            '/api/login',
            '/api/register',
            '/api/check-email',
            '/api/check-username',
            '/api/logout',
        ];
        $user = $this->security->getUser();

        $isBlockedApiPath = str_starts_with($path, '/api') && !in_array($path, $allowedPaths, true);
        if ($isBlockedApiPath && $user instanceof User && $user->isBanned()) {
            $this->tokenStorage->setToken(null);
            if ($request->hasSession()) {
                $request->getSession()->invalidate();
            }

            $event->setController(static fn () => new JsonResponse([
                'error' => 'Ce compte est banni. Accès refusé.'
            ], 403));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }
}
