<?php

namespace Mosparo\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFilter;
use zepi\Unicode\InvisibleCodepointIndex;

class DataExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('mark_invisible_characters', [$this, 'markInvisibleCharacters'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFilter('var_export', [$this, 'exportVariable']),
        ];
    }

    public function markInvisibleCharacters(Environment $env, $value): string
    {
        // The filter returns HTML and is marked as safe, so Twig does not escape the output. Escape the value with
        // the escaper of Twig before the markers are added, so the filter is safe in every template.
        $escaper = $env->getRuntime(EscaperRuntime::class);
        $value = (string) $escaper->escape($value);

        $invisibleCodepointIndex = new InvisibleCodepointIndex();
        $codepointPattern = $invisibleCodepointIndex->getRegexPattern(false);
        $fullPattern = '/([' . $codepointPattern . '])/um';

        preg_match_all($fullPattern, $value, $matches, PREG_SET_ORDER);

        $processedCharacters = [];
        foreach ($matches as $match) {
            if ($match[0] !== '') {
                $character = $match[0];
                if (in_array($character, $processedCharacters)) {
                    continue;
                }

                $hex = $invisibleCodepointIndex->getCharacterHex($character);
                $codepoint = $invisibleCodepointIndex->getCodepoint($hex);
                $value = str_replace($character, '<u title="' . $escaper->escape($codepoint['name'] . ' (' . $hex . ')') . '" data-bs-toggle="tooltip" data-bs-placement="top">' . $character . '</u>', $value);

                $processedCharacters[] = $character;
            }
        }

        $length = strlen($value);
        $value = ltrim($value);
        $leadingSpaces = $length - strlen($value);

        if ($leadingSpaces > 0) {
            $value = str_repeat('<u class="space" title="SPACE" data-bs-toggle="tooltip" data-bs-placement="top"> </u>', $leadingSpaces) . $value;
        }

        $length = strlen($value);
        $value = rtrim($value);
        $trailingSpaces = $length - strlen($value);

        if ($trailingSpaces > 0) {
            $value .= str_repeat('<u class="space" title="SPACE" data-bs-toggle="tooltip" data-bs-placement="top"> </u>', $trailingSpaces);
        }

        return $value;
    }

    public function exportVariable($value): string
    {
        return var_export($value, true);
    }
}