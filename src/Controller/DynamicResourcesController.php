<?php

namespace Mosparo\Controller;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Helper\DesignHelper;
use Mosparo\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/resources')]
class DynamicResourcesController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected ProjectRepository $projectRepository;

    protected DesignHelper $designHelper;

    protected UrlHelper $urlHelper;

    protected CacheInterface $cache;

    protected bool $prepareCssFilesInSharedCache;

    public function __construct(EntityManagerInterface $entityManager, ProjectRepository $projectRepository, DesignHelper $designHelper, UrlHelper $urlHelper, CacheInterface $cache, bool $prepareCssFilesInSharedCache)
    {
        $this->entityManager = $entityManager;
        $this->projectRepository = $projectRepository;
        $this->designHelper = $designHelper;
        $this->urlHelper = $urlHelper;
        $this->cache = $cache;
        $this->prepareCssFilesInSharedCache = $prepareCssFilesInSharedCache;
    }

    #[Route('/{projectUuid}.css', name: 'resources_project_css', stateless: true)]
    #[Route('/{projectUuid}/{styleHash}.css', name: 'resources_project_hash_css', stateless: true)]
    public function redirectToStyleResource(Request $request, string $projectUuid): Response
    {
        $project = null;
        $hash = $request->attributes->get('styleHash');

        if ($this->prepareCssFilesInSharedCache && $hash) {
            // If we store the prepared CSS caches in the shared cache, we have load the content
            // from the cache and return it to the browser.
            $baseKey = 'design_' . $projectUuid;
            $contentKey = $baseKey . '_content';
            $hashKey = $baseKey . '_hash';

            $cacheContent = $this->cache->getItem($contentKey);
            $cacheHash = $this->cache->getItem($hashKey);

            $needsRegeneration = !$cacheContent->isHit() || !$cacheHash->isHit();

            if (!$needsRegeneration && $cacheHash->get() !== $hash) {
                $project = $this->projectRepository->findOneBy([
                    'uuid' => $projectUuid
                ]);

                // Regenerate, if the given hash is the current one in the project which means we have a stale cache.
                // Otherwise, redirect to the correct hash.
                $needsRegeneration = $project && $project->getConfigValue('designConfigHash') === $hash;
            }

            if ($needsRegeneration) {
                $project ??= $this->projectRepository->findOneBy(['uuid' => $projectUuid]);

                if ($project) {
                    $this->designHelper->generateCssCache($project);

                    $this->entityManager->flush();

                    // Reload the two items from the cache
                    $cacheContent = $this->cache->getItem($contentKey);
                    $cacheHash = $this->cache->getItem($hashKey);
                }
            }

            // Return the content only if the hash matches, otherwise redirect to the new design hash.
            if ($cacheContent->isHit() && $cacheHash->isHit() && $cacheHash->get() === $hash) {
                $response = new Response($cacheContent->get());
                $response->headers->set('Content-Type', 'text/css');

                $response->headers->set('Access-Control-Allow-Origin', '*');

                $response->setMaxAge(365 * 86400); // Cache for one year
                $response->setPublic();

                return $response;
            }
        }

        // If we do not use the shared cache to store the resource files or if the request is for an old
        // design hash, we have to load the project and then determine the correct URL.
        if (!$project) {
            $project = $this->projectRepository->findOneBy(['uuid' => $projectUuid]);

            if ($project === null) {
                return new Response('404 Not Found', 404);
            }
        }

        $cssFilePath = $this->designHelper->getCssFilePath($project);
        if (file_exists($cssFilePath)) {
            $resourceUrl = $this->getResourceUrl($project, $projectUuid);
        } else if ($this->prepareCssFilesInSharedCache) {
            $resourceUrl = $this->getResourceUrl($project, $projectUuid);
        } else {
            $resourceUrl = $this->designHelper->getBaseCssFileName();
            $cssFilePath = $this->designHelper->getBuildFilePath($resourceUrl);
        }

        $redirectResponse = new RedirectResponse(
            $resourceUrl,
            307
        );

        if (file_exists($cssFilePath)) {
            $cssFileTime = filemtime($cssFilePath);
            $cssFileDate = (new DateTime())->setTimestamp($cssFileTime)->setTimezone(new DateTimeZone('UTC'));

            $redirectResponse->setLastModified($cssFileDate);
        }

        $redirectResponse->setPublic();
        $redirectResponse->headers->addCacheControlDirective('no-cache');

        return $redirectResponse;
    }

    #[Route('/{projectUuid}/url', name: 'resources_project_css_url')]
    public function returnStyleResourceUrl(Request $request, string $projectUuid): Response
    {
        $redirectResponse = $this->redirectToStyleResource($request, $projectUuid);
        if (!($redirectResponse instanceof RedirectResponse)) {
            return new Response('404 Not Found', 404);
        }

        return new Response($this->urlHelper->getAbsoluteUrl($redirectResponse->getTargetUrl()));
    }

    #[Route('/logo.svg', name: 'resources_frontend_logo', stateless: true)]
    public function returnTextLogo(Request $request): Response
    {
        $forcedColors = false;
        $prefersColorScheme = 'u';
        if ($request->query->get('fc', 0)) {
            $forcedColors = true;
            $prefersColorScheme = $request->query->get('pcs', 'l');
        }

        $response = new Response($this->designHelper->getTextLogoContent($forcedColors, $prefersColorScheme));

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'logo.svg');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/svg+xml');

        $response->headers->set('Access-Control-Allow-Origin', '*');

        $response->setMaxAge(365 * 86400); // Cache for one year
        $response->setPublic();

        return $response;
    }

    protected function getResourceUrl(Project $project, string $projectUuid)
    {
        return $this->generateUrl(
            'resources_project_hash_css',
            ['projectUuid' => $projectUuid, 'styleHash' => $project->getConfigValue('designConfigHash')],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
