<?php

namespace Mosparo\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Helper\ProjectHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class ProjectSubscriber implements EventSubscriberInterface
{
    protected ContainerInterface $container;

    protected Security $security;

    protected UrlGeneratorInterface $router;

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected Environment $twig;

    protected bool $installed;

    public function __construct(ContainerInterface $container, Security $security, UrlGeneratorInterface $router, EntityManagerInterface $entityManager, ProjectHelper $projectHelper, Environment $twig, $installed)
    {
        $this->container = $container;
        $this->security = $security;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->twig = $twig;
        $this->installed = $installed;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ConsoleEvents::COMMAND => 'onConsoleCommand'
        ];
    }

    public function onConsoleCommand()
    {
        $this->enableDoctrineFilter();
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->installed) {
            return;
        }

        $request = $event->getRequest();
        $activeRoute = $request->get('_route');
        $activeProject = null;
        $projectRepository = $this->entityManager->getRepository(Project::class);

        if (strpos($activeRoute, 'frontend_api_') === 0 || strpos($activeRoute, 'verification_api_') === 0) {
            if (!$request->request->has('publicKey')) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No public key sent.']));
                return;
            }

            $publicKey = $request->request->get('publicKey');
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

            // If the project does not exist we have to clean the session value and return to the project list
            if ($activeProject === null || (!$this->security->isGranted('ROLE_ADMIN') && !$activeProject->isProjectMember($this->security->getUser()))) {
                if ($abortRequest) {
                    $event->setResponse(new RedirectResponse($this->router->generate('project_list')));
                }

                return;
            }
        } else {
            // do nothing
            return;
        }

        $this->projectHelper->setActiveProject($activeProject);

        // Check if the user has access to the active route
        $result = $this->checkAccess($request, $activeRoute);
        if ($result !== null) {
            $event->setResponse($result);
        }

        $this->enableDoctrineFilter();
    }

    protected function checkAccess(Request $request, $activeRoute): ?Response
    {
        $checkForProject = $this->projectHelper->getActiveProject();
        $managerRoutes = [
            'rule_create_choose_type' => ProjectMember::ROLE_EDITOR,
            'rule_create_with_type' => ProjectMember::ROLE_EDITOR,
            'rule_delete' => ProjectMember::ROLE_EDITOR,
            'ruleset_add' => ProjectMember::ROLE_EDITOR,
            'ruleset_edit' => ProjectMember::ROLE_EDITOR,
            'ruleset_delete' => ProjectMember::ROLE_EDITOR,
            'settings_general' => ProjectMember::ROLE_OWNER,
            'settings_member_list' => ProjectMember::ROLE_OWNER,
            'settings_member_add' => ProjectMember::ROLE_OWNER,
            'settings_member_edit' => ProjectMember::ROLE_OWNER,
            'settings_member_remove' => ProjectMember::ROLE_OWNER,
            'settings_security' => ProjectMember::ROLE_OWNER,
            'settings_design' => ProjectMember::ROLE_OWNER,
            'settings_reissue_keys' => ProjectMember::ROLE_OWNER
        ];

        if ($activeRoute === 'rule_edit' && $request->getMethod() === 'POST') {
            $managerRoutes['rule_edit'] = ProjectMember::ROLE_EDITOR;
        }

        if ($activeRoute === 'project_delete') {
            $managerRoutes['project_delete'] = ProjectMember::ROLE_OWNER;

            $projectId = $request->attributes->get('project');
            $projectRepository = $this->entityManager->getRepository(Project::class);

            $project = $projectRepository->find($projectId);
            if ($project !== null) {
                $checkForProject = $project;
            }
        }

        if (isset($managerRoutes[$activeRoute])) {
            $targetRole = $managerRoutes[$activeRoute];
            $targetRoles = [$targetRole];

            if ($targetRole == ProjectMember::ROLE_EDITOR) {
                $targetRoles[] = ProjectMember::ROLE_OWNER;
            }

            if (!$this->projectHelper->hasRequiredRole($targetRoles, $checkForProject)) {
                return new Response($this->twig->render('security/no-access.html.twig'));
            }
        }

        return null;
    }

    protected function enableDoctrineFilter()
    {
        $this->entityManager
            ->getFilters()
            ->enable('project_related_filter')
            ->setProjectHelper($this->projectHelper);
    }
}