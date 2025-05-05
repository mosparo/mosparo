<?php

namespace Mosparo\Twig;

use Mosparo\Util\StringUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('obfuscate', [StringUtil::class, 'obfuscateString']),
        ];
    }
}