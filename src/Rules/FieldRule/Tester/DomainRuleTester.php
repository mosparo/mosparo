<?php

namespace Mosparo\Rules\FieldRule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rules\FieldRule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class DomainRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        $domains = $this->extractDomains($fieldData['fieldPath'], $value);
        $domainHashes = $this->getDomainHashes($domains);

        if (!$domainHashes) {
            return;
        }

        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('domain')))
            ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($domainHashes, ArrayParameterType::STRING)))
        );
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $itemValue = mb_strtolower($item->getValue());

        $pattern = '/(?<![\w-])' . preg_quote(trim($itemValue, './'), '/') . '(?![\w-]|\.[\w-])/iu';
        if (preg_match($pattern, $lowercaseValue)) {
            $matchingItems = [
                'type' => $item->getType(),
                'value' => $item->getValue(),
                'rating' => $this->calculateSpamRating($item),
                'uuid' => $item->getParent()->getUuid(),
            ];
        }

        return $matchingItems;
    }

    protected function extractDomains(string $fieldPath, string $value): array
    {
        if (str_starts_with($fieldPath, 'input[email]')) {
            $pattern = '#@([^\s@]+)#u';
        } else if (str_starts_with($fieldPath, 'input[url]') || str_starts_with($fieldPath, 'textarea')) {
            $pattern = '#(?:@|://)([^\s/?\#:@<>()\[\]{}"\',;!|\\\\]+)#u';
        } else {
            return [];
        }

        preg_match_all($pattern, $value, $matches);

        $domains = [];
        foreach ($matches[1] as $domain) {
            $domain = trim($domain, '.');
            if ($domain !== '') {
                $domains[$domain] = true;
            }
        }

        return array_keys($domains);
    }

    protected function getDomainHashes(array $domains, int $maxLabels = 10): array
    {
        $hashes = [];
        foreach ($domains as $domain) {
            $labels = array_slice(explode('.', $domain), -$maxLabels);
            for ($i = 0; $i < count($labels); $i++) {
                $hashes[HashUtil::hashFast(implode('.', array_slice($labels, $i)))] = true;

                if (count($hashes) >= 500) {
                    break 2;
                }
            }
        }

        return array_keys($hashes);
    }
}