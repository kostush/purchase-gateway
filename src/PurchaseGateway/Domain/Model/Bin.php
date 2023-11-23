<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidBinException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

class Bin
{
    /**
     * @var string
     */
    private $bin;

    /**
     * Bin constructor.
     * @param string $bin Bin
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(string $bin)
    {
        $this->validate($bin);

        $this->bin = $bin;
    }

    /**
     * @param string $ccNum Credit card number
     * @return Bin
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function createFromCCNumber(string $ccNum)
    {
        return new static(substr($ccNum, 0, 6));
    }

    /**
     * @param string $bin Bin number
     * @return Bin
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function createFromString(string $bin)
    {
        return new static($bin);
    }

    /**
     * @param string $bin Bin
     * @return void
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function validate(string $bin)
    {
        if (!(is_numeric($bin) && strlen($bin) === 6)) {
            throw new InvalidBinException($bin);
        };
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->bin;
    }

    /**
     * @param Bin $bin Bin
     * @return bool
     */
    public function equals(Bin $bin): bool
    {
        return ((string) $this === (string) $bin);
    }
}
