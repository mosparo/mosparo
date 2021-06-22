<?php

namespace Mosparo\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Helper\ActiveProjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProjectSubscriber implements EventSubscriberInterface
{
    protected $security;

    protected $router;

    protected $entityManager;

    protected $activeProjectHelper;

    public function __construct(Security $security, UrlGeneratorInterface $router, EntityManagerInterface $entityManager, ActiveProjectHelper $activeProjectHelper)
    {
        $this->security = $security;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->activeProjectHelper = $activeProjectHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $activeRoute = $request->get('_route');
        $activeProject = null;
        $projectRepository = $this->entityManager->getRepository(Project::class);

        if (strpos($activeRoute, 'frontend_api_') === 0) {
            if (!$request->request->has('_mosparo_publicKey')) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No public key sent.']));
                return;
            }

            $publicKey = $request->request->get('_mosparo_publicKey');
            $activeProject = $projectRepository->findOneBy(['publicKey' => $publicKey]);

            if ($activeProject === null) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No project available for the sent public key.']));
                return;
            }
        } else if ($this->security->getToken() && $this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $session = $request->getSession();

            // Ignore all requests with the project management routes
            $abortRequest = true;
            if (preg_match('/(project|account|security|password|administration)_/', $activeRoute)) {
                $abortRequest = false;
            }

            $activeProjectId = $session->get('activeProjectId', false);
            if ($activeProjectId !== false) {
                $activeProject = $projectRepository->find($activeProjectId);
            }

            // If the project does not exists we have to clean the session value and return to the project list
            if ($activeProject === null) {
                if ($abortRequest) {
                    $event->setResponse(new RedirectResponse($this->router->generate('project_list')));
                }

                return;
            }

            // @todo: Check if the user has really access to the active project
        } else {
            // do nothing
            return;
        }

        $this->activeProjectHelper->setActiveProject($activeProject);

        // Enable the doctrine filter
        $filter = $this->entityManager
            ->getFilters()
            ->enable('project_related_filter')
            ->setActiveProjectHelper($this->activeProjectHelper);
    }
}