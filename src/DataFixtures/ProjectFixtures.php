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

class ProjectFixtures extends Fixture
{
    public const PROJECT_REFERENCE = 'project';

    public function load(ObjectManager $manager): void
    {
        $project = (new Project())
            ->setName('Test project')
            ->setHosts(['mosparo.test'])
            ->setStatus(1)
            ->setPublicKey('mosparoPublicKey')
            ->setPrivateKey('mosparoPrivateKey')
        ;

        $project->setApiDebugMode(true);

        // Change the generator type
        $metadata = $manager->getClassMetadata(Project::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        // Force the ID of '1' to ensure the tests work
        $reflectionClass = new \ReflectionClass(Project::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setValue($project, 1);

        $manager->persist($project);
        $manager->flush();

        $this->addReference(self::PROJECT_REFERENCE, $project);
    }
}
