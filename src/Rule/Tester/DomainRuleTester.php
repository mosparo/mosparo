<?php

namespace Mosparo\Rule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class DomainRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if (str_starts_with($fieldData['fieldPath'], 'input')) {
            $domain = '';
            if (str_starts_with($fieldData['fieldPath'], 'input[email]')) {
                preg_match('#@(.[^/\s]*)(?:\n|\s|$)#', $value, $matches);
                $domain = $matches[1] ?? null;
            } else if (str_starts_with($fieldData['fieldPath'], 'input[url]')) {
                preg_match('#://(.[^/\s]*)(?:/|\n|\s|$)#', $value, $matches);
                $domain = $matches[1] ?? null;
            }

            if (!$domain) {
                return;
            }

            $orExpr->add($qb->expr()->andX()
                ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('domain')))
                ->add($qb->expr()->eq('i.hashedValue', $qb->createNamedParameter(HashUtil::hashFast($domain))))
            );
        } else if (str_starts_with($fieldData['fieldPath'], 'textarea')) {
            preg_match_all('#(@|://)(.[^/\s]*)(?:/|\n|\s|$)#', $value, $matches, PREG_SET_ORDER);
            if ($matches) {
                $domains = [];
                foreach ($matches as $match) {
                    $domain = HashUtil::hashFast(trim($match[2]));

                    if (!in_array($domain, $domains)) {
                        $domains[] = $domain;
                    }
                }

                $orExpr->add($qb->expr()->andX()
                    ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('domain')))
                    ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($domains, ArrayParameterType::STRING)))
                );
            }
        }
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $itemValue = strtolower($item->getValue());

        $pattern = '/(^|\.|\/\/|@)' . preg_quote(trim($itemValue, './'), '/') . '($|\/|#|\?|&)/is';
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
}