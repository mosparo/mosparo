<?php

namespace Mosparo\Rule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class EmailRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if (str_starts_with($fieldData['fieldPath'], 'input[email]')) {
            $orExpr->add($qb->expr()->andX()
                ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('email')))
                ->add($qb->expr()->eq('i.hashedValue', $qb->createNamedParameter(HashUtil::hashFast($value))))
            );
        } else if (str_starts_with($fieldData['fieldPath'], 'textarea')) {
            // This pattern is not perfect. With this pattern, we try to find everything that looks like an email address,
            // the full validation will happen later.
            preg_match_all('/(^|\n|\s)[\w\-\.\+]+@([\w-]+\.)+[\w-]{2,}($|\s|\n)/', $value, $matches, PREG_SET_ORDER);
            if ($matches) {
                $emails = [];
                foreach ($matches as $match) {
                    $email = HashUtil::hashFast(trim($match[0]));

                    if (!in_array($email, $emails)) {
                        $emails[] = $email;
                    }
                }

                $orExpr->add($qb->expr()->andX()
                    ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('email')))
                    ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($emails, ArrayParameterType::STRING)))
                );
            }
        }
    }

    public function validateData($key, $value, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $value = trim(strtolower($value));
        $itemValue = trim(strtolower($item->getValue()));

        if ($value === $itemValue || preg_match('/(^|\s+)' . preg_quote($itemValue, '/') . '(\s+|$)/', $value)) {
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