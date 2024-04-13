<?php

namespace Mosparo\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AssetsExtension extends AbstractExtension
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('add_path_prefix', [$this, 'addPathPrefix'], ['is_safe' => ['html']]),
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
}