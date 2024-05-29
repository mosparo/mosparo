<?php

namespace Mosparo\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
class PostgreSqlGenerateSchemaSchemaSubscriber
{
    /**
     * Creates the namespace for non-existing namespace when generating the schema.
     * This fixes the issue with PostgreSQL when make:migration creates an additional
     * line in the migration to create the schema `public` in the `down` method.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        $schemaManager = $event->getEntityManager()->getConnection()->createSchemaManager();
        if (!($schemaManager instanceof PostgreSQLSchemaManager)) {
            return;
        }

        $schema = $event->getSchema();
        foreach ($schemaManager->listSchemaNames() as $namespace) {
            if (!$schema->hasNamespace($namespace)) {
                $schema->createNamespace($namespace);
            }
        }
    }
}