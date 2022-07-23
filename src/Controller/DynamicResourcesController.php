<?php

namespace Mosparo\Controller;

use DateTime;
use DateTimeZone;
use Mosparo\Helper\DesignHelper;
use Mosparo\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\SessionListener;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/resources")
 */
class DynamicResourcesController extends AbstractController
{
    protected ProjectRepository $projectRepository;

    protected DesignHelper $designHelper;

    public function __construct(ProjectRepository $projectRepository, DesignHelper $designHelper)
    {
        $this->projectRepository = $projectRepository;
        $this->designHelper = $designHelper;
    }

    /**
     * @Route("/{projectUuid}.css", name="resources_project_css", stateless=true)
     * @Route("/{projectUuid}/{styleHash}.css", name="resources_project_hash_css", stateless=true)
     */
    public function redirectToStyleResource(string $projectUuid): Response
    {
        $project = $this->projectRepository->findOneBy(['uuid' => $projectUuid]);

        if ($project === null) {
            return new Response('404 Not Found', 404);
        }

        $cssFilePath = $this->designHelper->getCssFilePath($project);
        if (file_exists($cssFilePath)) {
            $resourceUrl = $this->generateUrl(
                'resources_project_hash_css',
                ['projectUuid' => $projectUuid, 'styleHash' => $project->getConfigValue('designConfigHash')],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } else {
            $resourceUrl = $this->designHelper->getBaseCssFileName();
            $cssFilePath = $this->designHelper->getBuildFilePath($resourceUrl);
        }

        $cssFileTime = filemtime($cssFilePath);
        $cssFileDate = (new DateTime())->setTimestamp($cssFileTime)->setTimezone(new DateTimeZone('UTC'));

        $redirectResponse = new RedirectResponse(
            $resourceUrl,
            307
        );
        $redirectResponse->setLastModified($cssFileDate);
        $redirectResponse->setPublic();
        $redirectResponse->headers->addCacheControlDirective('no-cache');

        return $redirectResponse;
    }

    /**
     * @Route("/{projectUuid}/url", name="resources_project_css_url")
     */
    public function returnStyleResourceUrl(string $projectUuid): Response
    {
        $redirectResponse = $this->redirectToStyleResource($projectUuid);
        if (!($redirectResponse instanceof RedirectResponse)) {
            return new Response('404 Not Found', 404);
        }

        return new Response($redirectResponse->getTargetUrl());
    }
}
