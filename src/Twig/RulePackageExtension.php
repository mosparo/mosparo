<?php

namespace Mosparo\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Attributes\RulePackageTypeInfo;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Enum\RulePackageType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RulePackageExtension extends AbstractExtension
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rule_package_type', [$this, 'getRulePackageType']),
            new TwigFunction('get_rule_package_type_info', [$this, 'getRulePackageTypeInfo']),
            new TwigFunction('rule_package_count_rule_items', [$this, 'countRuleItems']),
        ];
    }

    public function getRulePackageType(int $type): ?RulePackageType
    {
        return RulePackageType::from($type);
    }

    public function getRulePackageTypeInfo(RulePackageType $type): ?RulePackageTypeInfo
    {
        return RulePackageTypeInfo::from($type);
    }

    public function countRuleItems(RulePackageRuleCache $rprc): int
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('COUNT(rpric.id)')
            ->from(RulePackageRuleItemCache::class, 'rpric')
            ->where('rpric.rulePackageRuleCache = :rprc')
            ->setParameter('rprc', $rprc)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($result) {
            $rprc->setNumberOfItems($result);

            $this->entityManager->flush();

            return $result;
        }

        return 0;
    }
}