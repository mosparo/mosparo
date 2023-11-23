<?php

namespace Mosparo\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\ApiClient\RequestHelper;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\SecurityGuideline;
use Mosparo\Entity\Submission;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Util\IpUtil;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

class ProjectSubscriber implements EventSubscriberInterface
{
    protected Security $security;

    protected UrlGeneratorInterface $router;

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected Environment $twig;

    protected bool $installed;

    protected string $apiAccessIpAllowList;

    public function __construct(Security $security, UrlGeneratorInterface $router, EntityManagerInterface $entityManager, ProjectHelper $projectHelper, Environment $twig, $installed, string $apiAccessIpAllowList)
    {
        $this->security = $security;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->twig = $twig;
        $this->installed = ($installed == true);
        $this->apiAccessIpAllowList = $apiAccessIpAllowList;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ConsoleEvents::COMMAND => 'onConsoleCommand',
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
        $activeRoute = $request->attributes->get('_route');
        $activeProject = null;
        $projectRepository = $this->entityManager->getRepository(Project::class);

        if (strpos($activeRoute, 'frontend_api_') === 0) {
            if (!$request->request->has('publicKey')) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No public key sent.'], 400));
                return;
            }

            $publicKey = $request->request->get('publicKey');
            $activeProject = $projectRepository->findOneBy(['publicKey' => $publicKey]);

            if ($activeProject === null) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No project available for the sent public key.'], 403));
                return;
            }
        } else if (strpos($activeRoute, 'verification_api_') === 0 || strpos($activeRoute, 'statistic_api_') === 0) {
            // Check if the IP is allowed to access the backend APIs
            if (!IpUtil::isIpAllowed($request->getClientIp(), $this->apiAccessIpAllowList)) {
                throw new AccessDeniedHttpException(sprintf('Access to the API for this IP address (%s) is not allowed.', $request->getClientIp()));
            }

            if (!$request->headers->has('authorization') || empty($request->headers->get('authorization'))) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No authorization header found.'], 400));
                return;
            }

            $authorizationHeader = $request->headers->get('authorization');
            if (strpos($authorizationHeader, 'Basic ') === 0) {
                $authorizationHeader = substr($authorizationHeader, 6);
            }

            $authData = explode(':', base64_decode($authorizationHeader));
            if (count($authData) !== 2) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'Authorization header invalid.', 401]));
                return;
            }

            [$publicKey, $requestSignature] = $authData;

            // Search the active project
            $activeProject = $projectRepository->findOneBy(['publicKey' => $publicKey]);
            if ($activeProject === null) {
                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'No project available for the sent public key.'], 403));
                return;
            }

            $apiEndpoint = $this->router->generate($activeRoute);
            $requestData = array_merge($request->query->all(), $request->request->all());

            // Verify the request signature
            $requestHelper = new RequestHelper($publicKey, $activeProject->getPrivateKey());
            if ($requestSignature !== $requestHelper->createHmacHash($apiEndpoint . $requestHelper->toJson($requestData))) {
                // Prepare the API debug data
                $debugInformation = [];
                if ($activeProject->isApiDebugMode()) {
                    $debugInformation['debugInformation'] = [
                        'reason' => 'hmac_hash_invalid',
                        'expectedHmacHash' => $requestHelper->createHmacHash($apiEndpoint . $requestHelper->toJson($requestData)),
                        'receivedHmacHash' => $requestSignature,
                        'payload' => $apiEndpoint . $requestHelper->toJson($requestData),
                    ];
                }

                $event->setResponse(new JsonResponse(['error' => true, 'errorMessage' => 'Request invalid.'] + $debugInformation, 400));
                return;
            }
        } else if ($this->security->getToken() && $this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $session = $request->getSession();

            // Ignore all requests for general routes like project management, account or administration
            $abortRequest = true;
            if (preg_match('/^(project|account|security|password|administration)_/', $activeRoute)) {
                $abortRequest = false;
            }

            $activeProjectId = $session->get('activeProjectId', false);
            if ($activeProjectId !== false) {
                $activeProject = $projectRepository->find($activeProjectId);
            } else if (!$activeProjectId) {
                // Try to determine the entity and the project and switch to it if one of
                // these routes is requested.
                $entityReleatedRoutes = [
                    'rule_edit' => Rule::class,
                    'ruleset_edit' => Ruleset::class,
                    'ruleset_view' => Ruleset::class,
                    'ruleset_view_filtered' => Ruleset::class,
                    'ruleset_view_rule' => Ruleset::class,
                    'submission_view' => Submission::class,
                    'settings_security_guideline_edit' => SecurityGuideline::class,
                ];

                if (isset($entityReleatedRoutes[$activeRoute])) {
                    $projectId = $this->findProjectForRequestedEntity($request, $entityReleatedRoutes[$activeRoute]);

                    if ($projectId) {
                        $event->setResponse(new RedirectResponse($this->router->generate('project_switch', [
                            'project' => $projectId,
                            'targetPath' => $request->getRequestUri(),
                        ])));

                        return;
                    }
                }
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
            'settings_security_edit_general' => ProjectMember::ROLE_OWNER,
            'settings_security_guideline_add' => ProjectMember::ROLE_OWNER,
            'settings_security_guideline_edit' => ProjectMember::ROLE_OWNER,
            'settings_security_guideline_remove' => ProjectMember::ROLE_OWNER,
            'settings_design' => ProjectMember::ROLE_OWNER,
            'settings_reissue_keys' => ProjectMember::ROLE_OWNER,
            'tools_import' => ProjectMember::ROLE_OWNER,
            'tools_import_simulate' => ProjectMember::ROLE_OWNER,
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

    protected function findProjectForRequestedEntity(Request $request, string $class): ?int
    {
        $builder = $this->entityManager->createQueryBuilder();
        $builder
            ->select('IDENTITY(e.project) AS project_id')
            ->from($class, 'e')
            ->where('e.id = :id')
            ->setParameter('id', $request->attributes->get('id'));

        $result = $builder->getQuery()->getOneOrNullResult();

        if (!$result) {
            return null;
        }

        return $result['project_id'];
    }
}