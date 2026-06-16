<?php

namespace Mosparo\Twig;

use Mosparo\Helper\InterfaceHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AssetsExtension extends AbstractExtension
{
    protected RequestStack $requestStack;

    protected InterfaceHelper $interfaceHelper;

    public function __construct(RequestStack $requestStack, InterfaceHelper $interfaceHelper)
    {
        $this->requestStack = $requestStack;
        $this->interfaceHelper = $interfaceHelper;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('add_path_prefix', [$this, 'addPathPrefix'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_illustration_path', [$this, 'getIllustrationPath']),
        ];
    }

    public function addPathPrefix(string $html): string
    {
        $request = $this->requestStack->getMainRequest();

        $prefix = $request->headers->get('x-forwarded-prefix', null);
        if ($request->isFromTrustedProxy() && $prefix && str_contains($request->getBaseUrl(), $prefix)) {
            $prefix = trim($prefix, '/');
            $html = preg_replace('%(^|")/build/%m', '$1/' . $prefix . '/build/', $html);
        }

        return $html;
    }

    public function getIllustrationPath(string $illustrationName): string
    {
        $colorPaths = ['light', 'dark'];
        $colorMode = $this->interfaceHelper->determineColorMode($this->requestStack->getMainRequest());
        $colorPath = in_array($colorMode, $colorPaths) ? $colorMode : 'light';
        return sprintf('%s/%s/%s', 'build/images/icons', $colorPath, $illustrationName);
    }
}