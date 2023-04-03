<?php

namespace Mosparo\Session;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * This session handler is only needed to get the native PDO connection. There are other options to do the same
 * thing, but at least the method with the factory in the services.yaml file will create a second connection
 * to the database server.
 *
 * With this method, we get the Doctrine connection, and from there, we take the native PDO connection for the
 * standard Symfony PDO session handler.
 */
class DatabaseSessionHandler extends \SessionHandler
{
    protected $sessionHandler;

    public function __construct(Connection $connection, $mosparoInstalled, $mosparoVersion, $options = [])
    {
        /**
         * The sessions can only be stored in the database if mosparo is fully installed and the version of
         * mosparo is greater than 0.3.9.
         */
        if ($mosparoInstalled && version_compare($mosparoVersion, '0.3.9', 'gt')) {
            $this->sessionHandler = new PdoSessionHandler($connection->getNativeConnection(), $options);
        } else {
            $this->sessionHandler = new NativeFileSessionHandler();
        }
    }

    public function close(): bool
    {
        return $this->sessionHandler->close();
    }

    public function destroy($sessionId): bool
    {
        return $this->sessionHandler->destroy($sessionId);
    }

    #[\ReturnTypeWillChange]
    public function gc($maxLifetime)
    {
        return $this->sessionHandler->gc($maxLifetime);
    }

    public function open($path, $name): bool
    {
        return $this->sessionHandler->open($path, $name);
    }

    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        return $this->sessionHandler->read($sessionId);
    }

    public function write($sessionId, $data): bool
    {
        return $this->sessionHandler->write($sessionId, $data);
    }

    public function validateId($sessionId)
    {
        return $this->sessionHandler->validateId($sessionId);
    }

    public function updateTimestamp($sessionId, $session_data)
    {
        return $this->sessionHandler->updateTimestamp($sessionId, $session_data);
    }
}