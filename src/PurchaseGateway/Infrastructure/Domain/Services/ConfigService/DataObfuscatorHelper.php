<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService;

class DataObfuscatorHelper
{
    const OBFUSCATED_STRING = '*******';

    /**
     * @param array $payload
     * @param array $obfuscationKeys
     *
     * @return array
     */
    public static function obfuscateSensitiveData(array $payload, array $obfuscationKeys)
    {
        foreach ($obfuscationKeys as $obfuscateKey) {
            $payload = self::obfuscateItem($payload, $obfuscateKey);
        }

        return $payload;
    }

    /**
     * @param array $payload
     * @param       $obfuscateKey
     *
     * @return array
     */
    private static function obfuscateItem(array $payload, $obfuscateKey): array
    {
        foreach ($payload as $key => $item) {
            if ($obfuscateKey == $key && is_array($item)) {
                $payload[$key] = self::obfuscateAll($item);
                continue;
            }

            if ($obfuscateKey === $key) {
                $payload[$key] = self::OBFUSCATED_STRING;
                continue;
            }

            if (is_array($item)) {
                $payload[$key] = self::obfuscateItem($item, $obfuscateKey);
                continue;
            }
        }

        return $payload;
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    private static function obfuscateAll(array $payload): array
    {
        foreach ($payload as $key => $item) {
            $payload[$key] = self::OBFUSCATED_STRING;
        }

        return $payload;
    }
}