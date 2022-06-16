<?php

namespace Mosparo\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Exception;

class EncryptedJson extends Type
{

    const ENCRYPTEDJSON = 'encryptedJon';

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
        return 'LONGTEXT COMMENT \'(DC2Type:encryptedJson)\'';
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return self::ENCRYPTEDJSON;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return null|array
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (!isset($_ENV['ENABLE_ENCRYPTION']) || $_ENV['ENABLE_ENCRYPTION'] === 'false') {
            return $value;
        }

        [$nonce, $encryptedValue] = explode('|', $value);
        $decryptedValue = sodium_crypto_secretbox_open(sodium_hex2bin($encryptedValue), sodium_hex2bin($nonce), sodium_hex2bin($_ENV['ENCRYPTION_KEY']));
        $decodedValue = json_decode($decryptedValue, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ConversionException::conversionFailed($decryptedValue, $this->getName());
        }

        return $decodedValue;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return mixed
     * @throws Exception
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!isset($_ENV['ENABLE_ENCRYPTION']) || $_ENV['ENABLE_ENCRYPTION'] === 'false') {
            return $value;
        }

        if (empty($value)) {
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = sodium_hex2bin($_ENV['ENCRYPTION_KEY']);

        $preparedValue = json_encode($value);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ConversionException::conversionFailedSerialization($value, 'json', json_last_error_msg());
        }

        $encryptedValue = sodium_crypto_secretbox($preparedValue, $nonce, $key);
        return sodium_bin2hex($nonce) . '|' . sodium_bin2hex($encryptedValue);
    }
}
