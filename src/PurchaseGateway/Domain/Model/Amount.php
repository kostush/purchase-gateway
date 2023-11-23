<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;

class Amount
{
    /**
     * @var float
     */
    private $value;

    /**
     * Amount constructor.
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @param float $value The amount value
     */
    private function __construct($value)
    {
        $this->initAmount($value);
    }

    /**
     * @param float $value Value
     * @return Amount
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(float $value): self
    {
        return new static($value);
    }

    /**
     * @return float
     */
    public function value(): float
    {
        return $this->value;
    }

    /**
     * @param float $value Value
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initAmount(float $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false || $value < 0) {
            throw new InvalidAmountException('amount');
        }

        $this->value = $value;
    }

    /**
     * @param Amount $value
     * @return Amount
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function increment(Amount $value): self
    {
        return new self($this->value() + $value->value());
    }
}
