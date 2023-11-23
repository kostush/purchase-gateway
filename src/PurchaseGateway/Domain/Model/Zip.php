<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;

class Zip
{
    /**
     * @var string
     */
    private $zip;

    const MIN_ZIP_CODE_LENGTH = 2;

    const MAX_ZIP_CODE_LENGTH = 20;

    /**
     * Zip constructor.
     * @param string $zip zip code
     * @throws InvalidZipCodeException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(string $zip)
    {
        $this->initZip(trim($zip));
    }

    /**
     * @param string $zip zip code
     * @throws InvalidZipCodeException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initZip(string $zip): void
    {
        // we allow space in the zip code.
        $sanitizedZipCode = preg_replace('/[^A-Za-z0-9 ]+/', '', $zip);
        if (empty($sanitizedZipCode)
            || strlen($sanitizedZipCode) < self::MIN_ZIP_CODE_LENGTH
            || strlen($sanitizedZipCode) > self::MAX_ZIP_CODE_LENGTH
        ) {
            throw new InvalidZipCodeException();
        }

        $this->zip = $sanitizedZipCode;
    }

    /**
     * @param string $zip zip code
     * @return Zip
     * @throws InvalidZipCodeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(string $zip)
    {
        return new static($zip);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->zip;
    }

    /**
     * @param Zip $zip zip code
     * @return bool
     */
    public function equals(Zip $zip)
    {
        return (string) $this == (string) $zip;
    }
}
