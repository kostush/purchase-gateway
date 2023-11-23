<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;

class BundleSingleChargeInformation extends BundleChargeInformation
{
    /**
     * @var Amount
     */
    private $initialAmount;

    /**
     * @var Duration
     */
    private $validFor;

    /**
     * @var TaxBreakdown
     */
    private $initialTaxBreakDown;

    /**
     * BundleSingleChargeInformation constructor.
     * @param Amount            $initialAmount       The initial amount
     * @param Duration          $validFor            The duration stated in days
     * @param TaxBreakdown|null $initialTaxBreakDown The tax breakdown object
     */
    private function __construct(Amount $initialAmount, Duration $validFor, ?TaxBreakdown $initialTaxBreakDown)
    {
        $this->initialAmount       = $initialAmount;
        $this->validFor            = $validFor;
        $this->initialTaxBreakDown = $initialTaxBreakDown;
    }

    /**
     * @param Amount            $initialAmount       The initial amount
     * @param Duration          $validFor            The duration stated in days
     * @param TaxBreakdown|null $initialTaxBreakDown The tax breakdown object
     * @throws Exception\InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @return self
     */
    public static function create(
        Amount $initialAmount,
        Duration $validFor,
        ?TaxBreakdown $initialTaxBreakDown = null
    ): self {
        if (!empty($initialTaxBreakDown)) {
            if ($initialAmount->value() !== $initialTaxBreakDown->afterTaxes()->value()) {
                throw new InvalidAmountException('Initial amount and after tax amount miss match');
            }
        }

        return new static($initialAmount, $validFor, $initialTaxBreakDown);
    }

    /**
     * @return Amount
     */
    public function initialAmount(): Amount
    {
        return $this->initialAmount;
    }

    /**
     * @return Duration
     */
    public function validFor(): Duration
    {
        return $this->validFor;
    }

    /**
     * @return TaxBreakdown|null
     */
    public function initialTaxBreakDown(): ?TaxBreakdown
    {
        return $this->initialTaxBreakDown;
    }

    /**
     * @return array
     */
    public function fullTaxBreakDownArray(): array
    {
        return empty($this->initialTaxBreakDown()) ? [] : ['initialAmount' => $this->initialTaxBreakDown()->toArray()];
    }
}
