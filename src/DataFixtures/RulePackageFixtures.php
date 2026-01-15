<?php

namespace Mosparo\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Enum\RulePackageType;

class RulePackageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $project = $this->getReference(ProjectFixtures::PROJECT_REFERENCE, Project::class);

        // Change the generator type
        $metadata = $manager->getClassMetadata(RulePackage::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        // Create the API rule package with cache (to test the hash index)
        $rulePackage = (new RulePackage())
            ->setType(RulePackageType::MANUALLY_VIA_API)
            ->setName('API Test Rule Package with cache')
            ->setProject($project)
            ->setStatus(true)
        ;

        // Force the ID of '1' to ensure the tests work
        $reflectionClass = new \ReflectionClass(RulePackage::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setValue($rulePackage, 1);

        $rpc = (new RulePackageCache())
            ->setRulePackage($rulePackage)
            ->setProject($project)
            ->setRefreshedAt(new \DateTime())
            ->setRefreshInterval(3600)
        ;
        $manager->persist($rpc);

        $rprc = (new RulePackageRuleCache())
            ->setRulePackageCache($rpc)
            ->setProject($project)
            ->setUuid('00000000-0000-0000-0000-100000000001')
            ->setType('word')
            ->setName('Test rule')
            ->setSpamRatingFactor(5)
        ;
        $manager->persist($rprc);

        for ($i = 0; $i < 1020; $i++) {
            $rpric = (new RulePackageRuleItemCache())
                ->setUuid('00000000-0000-0000-0000-200000' . str_pad($i, 6, '0', STR_PAD_LEFT))
                ->setRulePackageRuleCache($rprc)
                ->setProject($project)
                ->setType('text')
                ->setValue('item ' . $i)
                ->setSpamRatingFactor(2)
            ;
            $manager->persist($rpric);
        }

        // Create the API rule package without cache
        $rulePackage = (new RulePackage())
            ->setType(RulePackageType::MANUALLY_VIA_API)
            ->setName('API Test Rule Package')
            ->setProject($project)
            ->setStatus(true)
        ;

        // Force the ID of '1' to ensure the tests work
        $reflectionClass = new \ReflectionClass(RulePackage::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setValue($rulePackage, 2);

        $manager->persist($rulePackage);

        // Create the API rule package cache by importing the rule package in the legacy format.
        $rulePackage = (new RulePackage())
            ->setType(RulePackageType::MANUALLY_VIA_API)
            ->setName('API Test Rule Package imported via legacy API')
            ->setProject($project)
            ->setStatus(true)
        ;

        // Force the ID of '3' to ensure the tests work
        $reflectionClass = new \ReflectionClass(RulePackage::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setValue($rulePackage, 3);

        $manager->persist($rulePackage);

        // This is used to ensure that the rule package type is correct before importing anything.
        $rulePackage = (new RulePackage())
            ->setType(RulePackageType::AUTOMATICALLY_FROM_FILE)
            ->setName('API Test Rule Package automatically imported')
            ->setProject($project)
            ->setStatus(true)
        ;

        // Force the ID of '4' to ensure the tests work
        $reflectionClass = new \ReflectionClass(RulePackage::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setValue($rulePackage, 4);

        $manager->persist($rulePackage);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
        ];
    }
}
