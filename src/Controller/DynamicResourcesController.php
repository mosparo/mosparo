<?php

namespace Mosparo\Controller;

use DateTime;
use DateTimeZone;
use Mosparo\Helper\DesignHelper;
use Mosparo\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
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
     * @Route("/{projectUuid}.css", name="resources_project_css")
     * @Route("/{projectUuid}/{styleHash}.css", name="resources_project_hash_css")
     */
    public function redirectToStyleResource(string $projectUuid): Response
    {
        $project = $this->projectRepository->findOneBy(['uuid' => $projectUuid]);

        if ($project === null) {
            return new Response('404 Not Found', 404);
        }

        $cssFilePath = $this->designHelper->getCssFilePath($project);
        if (!file_exists($cssFilePath)) {
            $cssFilePath = $this->designHelper->getBaseCssFilePath();
        }

        $cssFileTime = filemtime($cssFilePath);
        $cssFileDate = (new DateTime())->setTimestamp($cssFileTime)->setTimezone(new DateTimeZone('UTC'));

        $resourceUrl = $this->generateUrl(
            'resources_project_hash_css',
            ['projectUuid' => $projectUuid, 'styleHash' => $project->getConfigValue('designConfigHash')],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $redirectResponse = new RedirectResponse(
            $resourceUrl,
            307
        );
        $redirectResponse->setPublic();
        $redirectResponse->setMaxAge(86400);
        $redirectResponse->setLastModified($cssFileDate);
        $redirectResponse->headers->addCacheControlDirective('must-revalidate');

        // Allow to cache the response since it is not a sensitive response
        $redirectResponse->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');

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
