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
    protected $mosparoVersion;

    public function __construct(Connection $connection, $mosparoVersion, $options = [])
    {
        $this->mosparoVersion = $mosparoVersion;
        parent::__construct($connection->getNativeConnection(), $options);
    }

    protected function doRead(string $sessionId)
    {
        /**
         * This special check is needed because this database session handler was introduced in v0.3.9.
         * Before, the table `sessions` does not exist. When the user tries to update mosparo in the browser,
         * the session is required, so Symfony tries to initialize the session before creating the database,
         * which ends in an error 500.
         *
         * With this fix, we ensure that the handler does not try to read from the table if the update is pending.
         */
        if (version_compare($this->mosparoVersion, '0.3.9', 'lt')) {
            return '';
        }

        return parent::doRead($sessionId);
    }
}