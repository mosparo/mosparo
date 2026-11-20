<?php

namespace Mosparo\Rules\FieldRule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rules\FieldRule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;
use Mosparo\Util\UrlUtil;

class WebsiteRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if ($value === null) {
            return;
        }

        $hashes = $this->getUrlHashes($this->extractUrls($fieldData['fieldPath'], $value));
        if (!$hashes) {
            return;
        }

        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('url')))
            ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($hashes, ArrayParameterType::STRING)))
        );
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $itemUrl = UrlUtil::normalizeUrl(mb_strtolower($item->getValue()));

        if ($itemUrl === '') {
            return [];
        }

        // validateData() does not know the field type ($key is the field name), so we use the URLs in the text and,
        // if there are none, the value itself (for example, an input[url] field without scheme).
        $urls = UrlUtil::extractUrls($lowercaseValue);
        if (!$urls && !preg_match('/\s/', trim($lowercaseValue))) {
            $urls = $this->extractUrls('input[url]', $lowercaseValue);
        }

        $matchingItems = [];
        foreach ($urls as $url) {
            if (in_array($itemUrl, UrlUtil::getPrefixCandidates($url), true)) {
                $matchingItems = [
                    'type' => $item->getType(),
                    'value' => $item->getValue(),
                    'rating' => $this->calculateSpamRating($item),
                    'uuid' => $item->getParent()->getUuid(),
                ];

                break;
            }
        }

        return $matchingItems;
    }

    protected function extractUrls(string $fieldPath, string $value): array
    {
        if (str_starts_with($fieldPath, 'input[url]')) {
            $url = UrlUtil::normalizeUrl($value);

            return ($url !== '') ? [$url] : [];
        } else if (str_starts_with($fieldPath, 'textarea')) {
            return UrlUtil::extractUrls($value);
        }

        return [];
    }

    protected function getUrlHashes(array $urls, int $maxHashes = 500): array
    {
        $hashes = [];
        foreach ($urls as $url) {
            foreach (UrlUtil::getPrefixCandidates($url) as $candidate) {
                $hashes[HashUtil::hashFast($candidate)] = true;

                if (count($hashes) >= $maxHashes) {
                    break 2;
                }
            }
        }

        return array_keys($hashes);
    }
}