<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use ProBillerNG\Crypt\Crypt;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;

class SodiumCryptService implements CryptService
{
    /**
     * @var Crypt
     */
    protected $sodiumCrypt;

    /**
     * AuthenticateKeyTranslatingService constructor.
     * @param Crypt $sodiumCrypt Sodium Crypt
     */
    public function __construct(Crypt $sodiumCrypt)
    {
        $this->sodiumCrypt = $sodiumCrypt;
    }

    /**
     * @param string $stringToEncrypt The string to encrypt
     * @return string
     * @throws \ProBillerNG\Crypt\UnableToEncryptException
     */
    public function encrypt(string $stringToEncrypt): string
    {
        return $this->sodiumCrypt->encrypt($stringToEncrypt);
    }

    /**
     * @param string $encryptedString The string for decrypting
     * @return string
     * @throws \ProBillerNG\Crypt\UnableToDecryptException
     */
    public function decrypt(string $encryptedString): string
    {
        return $this->sodiumCrypt->decrypt($encryptedString);
    }
}
