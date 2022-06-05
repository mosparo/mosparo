<?php

namespace Mosparo\Controller;

use DateTimeInterface;
use DateTimeZone;
use Mosparo\Entity\Project;
use Mosparo\Helper\DesignHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/resources")
 */
class DynamicResourcesController extends AbstractController
{
    protected $designHelper;

    public function __construct(DesignHelper $designHelper)
    {
        $this->designHelper = $designHelper;
    }

    /**
     * @Route("/{projectUuid}.css", name="resources_project_css")
     * @Route("/{projectUuid}/{styleHash}.css", name="resources_project_hash_css")
     */
    public function projectUuidHashCss(string $projectUuid, string $styleHash = ''): Response
    {
        $repository = $this->getDoctrine()->getRepository(Project::class);
        $project = $repository->findOneBy(['uuid' => $projectUuid]);

        if ($project === null) {
            return new Response('404 Not Found', 404);
        }

        $cssFilePath = $this->designHelper->getCssFilePath($project);
        if (!file_exists($cssFilePath)) {
            $cssFilePath = $this->designHelper->getBaseCssFilePath();
        }

        $cssFileTime = filemtime($cssFilePath);
        $cssFileDate = (new \DateTime())->setTimestamp($cssFileTime)->setTimezone(new DateTimeZone('UTC'));

        $redirectResponse = $this->redirectToRoute(
            'resources_project_hash_css',
            ['projectUuid' => $projectUuid, 'styleHash' => $project->getConfigValue('designConfigHash')],
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
}
