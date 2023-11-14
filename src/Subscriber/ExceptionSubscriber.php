<?php

namespace Mosparo\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ExceptionSubscriber implements EventSubscriberInterface
{
    protected Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelException', 0]],
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $apiRequest = ($request->headers->get('Accept', '') === 'application/json');

        if ($exception instanceof AccessDeniedHttpException) {
            if ($apiRequest) {
                $event->setResponse(new JsonResponse([
                    'error' => true,
                    'errorMessage' => $exception->getMessage(),
                ], 403));
            } else {
                $event->setResponse(new Response($this->twig->render('security/no-access.html.twig'), 403));
            }
        } else if ($exception instanceof NotFoundHttpException) {
            if ($apiRequest) {
                $event->setResponse(new JsonResponse([
                    'error' => true,
                    'errorMessage' => $exception->getMessage(),
                ], 404));
            } else {
                $event->setResponse(new Response($this->twig->render('security/not-found.html.twig'), 404));
            }
        }
    }
}