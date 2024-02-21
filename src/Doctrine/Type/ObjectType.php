<?php

namespace Mosparo\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Mosparo\Exception;

class ObjectType extends Type
{

    const OBJECT = 'object';

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $column The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return self::OBJECT;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return null|array
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        set_error_handler(function (int $code, string $message): bool {
            throw new Exception(sprintf('Could not unserialize database value of type "object". Error: %s', $message));
        });

        try {
            return unserialize($value);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return serialize($value);
    }
}
