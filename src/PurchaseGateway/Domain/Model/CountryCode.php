<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;

class CountryCode
{
    const COUNTRY_LENGTH = 2;

    /** @var string */
    private $country;

    /**
     * CountryCode constructor.
     * @param string $country Country
     *
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoCountry
     */
    protected function __construct(string $country)
    {
        $this->initCountry($country);
    }

    /**
     * @param string $country Country
     * @return void
     * @throws InvalidUserInfoCountry
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initCountry(?string $country): void
    {
        if (empty($country)
            || (strlen($country) != self::COUNTRY_LENGTH || ctype_alpha($country) == false)
        ) {
            throw new InvalidUserInfoCountry();
        }
        $this->country = strtoupper($country);
    }

    /**
     * @param string $country Country
     * @return CountryCode
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoCountry
     */
    public static function create(string $country): self
    {
        return new self(
            $country
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->country;
    }
}
