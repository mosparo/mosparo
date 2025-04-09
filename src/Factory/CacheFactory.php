<?php

namespace Mosparo\Factory;

use Mosparo\Exception;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class CacheFactory
{
    public static function getCache(string $cacheAdapter = null, string $redisUrl = null, string $memcachedUrl = null, string $filesystemCachePath = null): AbstractAdapter
    {
        $cacheAdapter = $cacheAdapter ?: 'filesystem';

        switch ($cacheAdapter) {
            case 'redis':
                $redisConnection = RedisAdapter::createConnection($redisUrl ?? 'redis://localhost');
                return new RedisAdapter($redisConnection);
            case 'memcached':
                if (!extension_loaded('memcached')) {
                    throw new Exception('The memcached extension is required but not loaded.');
                }

                $memcachedConnection = MemcachedAdapter::createConnection($memcachedUrl ?? 'memcached://localhost');
                return new MemcachedAdapter($memcachedConnection);
            case 'filesystem':
                if ($filesystemCachePath) {
                    return new FilesystemAdapter('', 0, $filesystemCachePath);
                } else {
                    return new FilesystemAdapter();
                }
            default:
                throw new \InvalidArgumentException("Unsupported cache adapter type: $cacheAdapter");
        }
    }
}