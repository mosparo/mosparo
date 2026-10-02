<?php

namespace Mosparo\Rules\FieldRule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rules\FieldRule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class EmailRuleTester extends AbstractRuleTester
{
    protected const BOUNDARY_BEFORE = '(?<![\w.+-])';
    protected const BOUNDARY_AFTER = '(?![\w-]|\.[\w-])';

    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if ($value === null) {
            return;
        }

        $emails = [];
        if (str_starts_with($fieldData['fieldPath'], 'input[email]')) {
            $emails = array_filter([trim($value)]);
        } else if (str_starts_with($fieldData['fieldPath'], 'textarea')) {
            $emails = $this->extractEmails($value);
        }

        if (!$emails) {
            return;
        }

        $hashes = array_map(fn($email) => HashUtil::hashFast($email), $emails);

        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('email')))
            ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($hashes, ArrayParameterType::STRING)))
        );
    }

    protected function extractEmails(string $value, int $maxEmails = 500): array
    {
        preg_match_all('/' . self::BOUNDARY_BEFORE . '[\w.+-]+@(?:[\w-]+\.)+[\w-]{2,}' . self::BOUNDARY_AFTER . '/u', $value, $matches);

        return array_slice(array_values(array_unique($matches[0])), 0, $maxEmails);
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $value = trim($lowercaseValue);
        $itemValue = trim(mb_strtolower($item->getValue()));
        $pattern = '/' . self::BOUNDARY_BEFORE . preg_quote($itemValue, '/') . self::BOUNDARY_AFTER . '/u';

        if ($value === $itemValue || preg_match($pattern, $value)) {
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