<?php

namespace Mosparo\Tests\UnitTests\Migrations;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MysqlMigrationsTest extends TestCase
{
    /**
     * Ensures that all MySQL transactions contain the isTransactional method which needs to return false.
     */
    public function testAllMysqlMigrationsAreNotTransactional()
    {
        $files = glob(__DIR__ . '/../../../migrations/mysql/Version*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            require_once $file;

            $className = 'DoctrineMigrations\\' . basename($file, '.php');
            $migration = (new ReflectionClass($className))->newInstanceWithoutConstructor();

            $this->assertFalse($migration->isTransactional(), sprintf('Migration "%s" must not be transactional.', $className));
        }
    }
}