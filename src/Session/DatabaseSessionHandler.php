<?php

namespace Mosparo\Session;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * This session handler is only needed to get the native PDO connection. There are other options to do the same
 * thing, but at least the method with the factory in the services.yaml file will create a second connection
 * to the database server.
 *
 * With this method, we get the Doctrine connection, and from there, we take the native PDO connection for the
 * standard Symfony PDO session handler.
 */
class DatabaseSessionHandler extends PdoSessionHandler
{
    public function __construct(Connection $connection, $options = [])
    {
        parent::__construct($connection->getNativeConnection(), $options);
    }
}