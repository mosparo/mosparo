<?php

namespace Mosparo\Doctrine;

class DatabaseConfigurationBuilder
{
    public static function getDatabaseConfiguration()
    {
        $options = [];

        if (($_ENV['DATABASE_MYSQL_SSL'] ?? false)) {
            if ($_ENV['DATABASE_MYSQL_SSL_KEY'] ?? null) {
                $options[\PDO::MYSQL_ATTR_SSL_KEY] = $_ENV['DATABASE_MYSQL_SSL_KEY'];
            }

            if ($_ENV['DATABASE_MYSQL_SSL_CERT'] ?? null) {
                $options[\PDO::MYSQL_ATTR_SSL_CERT] = $_ENV['DATABASE_MYSQL_SSL_CERT'];
            }

            if ($_ENV['DATABASE_MYSQL_SSL_CA'] ?? null) {
                $options[\PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DATABASE_MYSQL_SSL_CA'];
            }

            if ($_ENV['DATABASE_MYSQL_SSL_VERIFY_SERVER_CERT'] ?? null) {
                $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $_ENV['DATABASE_MYSQL_SSL_VERIFY_SERVER_CERT'];
            }
        }

        return $options;
    }
}