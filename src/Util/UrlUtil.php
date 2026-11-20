<?php

namespace Mosparo\Util;

class UrlUtil
{
    // Characters which can be part of a URL in a text. Everything else ends the URL.
    protected const URL_CHARACTERS = '[^\s<>"\'(){}\[\]|\\\\^`]+';

    /**
     * Normalizes the URL by removing the scheme, fragment as well as trailing punctuation and slash.
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (preg_match('#^([a-z][a-z0-9+.-]*):(?=//)#i', $url, $matches)) {
            $url = substr($url, strlen($matches[0]));
        }

        $url = preg_replace('#^//#', '', $url);
        $url = preg_replace('/#.*$/s', '', $url);
        $url = rtrim($url, '.,;:!?');
        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * Extracts all URLs from the given text. Nested URLs (for example, in a redirect parameter)
     * are extracted as well. Returns a list of URLs.
     */
    public static function extractUrls(string $text, int $maxUrls = 100): array
    {
        // The lookahead allows overlapping matches, so a URL inside another URL is found as well.
        preg_match_all('#(?<![a-z0-9+.-])(?=([a-z][a-z0-9+.-]*://' . self::URL_CHARACTERS . '))#iu', $text, $matches);

        $urls = [];
        foreach ($matches[1] ?? [] as $url) {
            $urlWithoutScheme = self::normalizeUrl($url);
            if ($urlWithoutScheme === '') {
                continue;
            }

            $urls[$urlWithoutScheme] = true;

            if (count($urls) >= $maxUrls) {
                break;
            }
        }

        return array_keys($urls);
    }

    /**
     * Returns the host, every path segment prefix and the full value (including the query) of the
     * normalized URL, for example:
     * example.com/a/b?c=1 => example.com, example.com/a, example.com/a/b, example.com/a/b?c=1
     */
    public static function getPrefixCandidates(string $url, int $maxSegments = 10): array
    {
        $candidates = [];
        $path = explode('?', $url, 2)[0];
        $parts = array_slice(explode('/', $path), 0, $maxSegments + 1);

        for ($i = 1; $i <= count($parts); $i++) {
            $candidate = rtrim(implode('/', array_slice($parts, 0, $i)), '/');
            if ($candidate !== '') {
                $candidates[$candidate] = true;
            }
        }

        $candidates[$url] = true;

        return array_keys($candidates);
    }
}