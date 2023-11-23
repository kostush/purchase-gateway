<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

interface CryptService
{
    /**
     * @param string $stringToEncrypt The string to encrypt
     * @return string
     */
    public function encrypt(string $stringToEncrypt): string;

    /**
     * @param string $encryptedString The string for decrypting
     * @return string
     */
    public function decrypt(string $encryptedString): string;
}
