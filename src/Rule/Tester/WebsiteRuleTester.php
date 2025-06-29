<?php

namespace Mosparo\Rule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class WebsiteRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if (str_starts_with($fieldData['fieldPath'], 'input[url]')) {
            $orExpr->add($qb->expr()->andX()
                ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('url')))
                ->add($qb->expr()->eq('i.hashedValue', $qb->createNamedParameter(HashUtil::hashFast($value))))
            );
        } else if (str_starts_with($fieldData['fieldPath'], 'textarea')) {
            // This pattern is not perfect. With this pattern, we try to find everything that looks like a URL,
            // the full validation will happen later.
            preg_match_all('/(^|\n|\s)([a-zA-Z0-9]+):\/\/([\w\-\.]+\.)*[\w\-\.]+\.\w{2,}(.[^\s]*)($|\s|\n)/', $value, $matches, PREG_SET_ORDER);
            if ($matches) {
                $urls = [];
                foreach ($matches as $match) {
                    $url = HashUtil::hashFast(trim($match[0]));

                    if (!in_array($url, $urls)) {
                        $urls[] = $url;
                    }
                }

                $orExpr->add($qb->expr()->andX()
                    ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('url')))
                    ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($urls, ArrayParameterType::STRING)))
                );
            }
        }
    }

    public function validateData($key, $value, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $preparedValue = $item->getValue();
        if (!preg_match('/((https?:)?\/\/)/i', $preparedValue)) {
            $preparedValue = '//' . $preparedValue;
        }

        $value = strtolower($value);
        $preparedValue = strtolower($preparedValue);

        if (strpos($value, $preparedValue) !== false) {
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