<?php

namespace Mosparo\Subscriber;

use Mosparo\Kernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupSubscriber implements EventSubscriberInterface
{
    protected UrlGeneratorInterface $router;

    protected bool $installed;

    protected ?string $installedVersion;

    protected array $allowedRoutes = [
        'setup_start',
        'setup_prerequisites',
        'setup_database',
        'setup_other',
        'setup_install',
        'setup_install_continuation',
    ];

    public function __construct(UrlGeneratorInterface $router, $installed, $installedVersion, $debug = false)
    {
        $this->router = $router;
        $this->installed = ($installed == true);
        $this->installedVersion = $installedVersion;

        if ($debug) {
            $this->allowedRoutes = array_merge($this->allowedRoutes, [
                '_wdt',
                '_profiler',
                '_profiler_search',
                '_profiler_search_bar',
                '_profiler_search_results',
                '_profiler_router',
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->installed) {
            $this->checkForUpdate($event);

            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (in_array($route, $this->allowedRoutes)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->router->generate('setup_start')));
    }

    protected function checkForUpdate(RequestEvent $event)
    {
        // If the two versions aren't the same, redirect to the update controller
        if (Kernel::VERSION != $this->installedVersion) {
            $request = $event->getRequest();
            $route = $request->attributes->get('_route');

            // We redirect only GET requests
            if ($request->getMethod() !== 'GET') {
                return;
            }

            // Do nothing if it is one of these predefined requests
            $noRedirectsRoutes = [
                'administration_update_execute',
                'administration_update_execute_update',
                'administration_update_finalize',
                'frontend_api_request_submit_token',
                'frontend_api_check_form_data',
                'verification_api_verify',
                'statistic_api_daily',
                'cron_jobs_execute',
            ];
            if (in_array($route, $noRedirectsRoutes)) {
                return;
            }

            $event->setResponse(new RedirectResponse($this->router->generate('administration_update_finalize')));
        }
    }
}