<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidLastFourException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

class LastFour
{
    /**
     * @var string
     */
    private $lastFour;


    /**
     * Bin constructor.
     *
     * @param string $lastFour Last4
     *
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(string $lastFour)
    {
        $this->validate($lastFour);

        $this->lastFour = $lastFour;
    }

    /**
     * @param string $ccNum Credit card number
     * @return LastFour
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function createFromCCNumber(string $ccNum)
    {
        return new static(substr($ccNum, -4));
    }

    /**
     * @param string $lastFour Bin number
     *
     * @return LastFour
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function createFromString(string $lastFour)
    {
        return new static($lastFour);
    }

    /**
     * @param string $lastFour Last4
     * @return void
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function validate(string $lastFour)
    {
        if (!(is_numeric($lastFour) && strlen($lastFour) === 4)) {
            throw new InvalidLastFourException($lastFour);
        };
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->lastFour;
    }

    /**
     * @param LastFour $lastFour Last4
     *
     * @return bool
     */
    public function equals(LastFour $lastFour): bool
    {
        return ((string) $this === (string) $lastFour);
    }
}
