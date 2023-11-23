<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;

class BundleRebillChargeInformation extends BundleChargeInformation
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
     * @var Amount
     */
    private $rebillAmount;

    /**
     * @var Duration
     */
    private $repeatEvery;

    /**
     * @var TaxBreakdown
     */
    private $rebillTaxBreakDown;

    /**
     * BundleRebillChargeInformation constructor.
     * @param Amount       $initialAmount             The intial amount
     * @param Duration     $validFor                  Number of days the bundle is valid
     * @param TaxBreakdown $initialAmountTaxBreakdown The tax breakdown for the inital amount
     * @param Amount       $rebillAmount              The rebill  amount
     * @param Duration     $repeatEvery               Repeat the charge every number of days
     * @param TaxBreakdown $rebillAmountTaxBreakdown  The tax breakdown for the rebill amount
     */
    private function __construct(
        Amount $initialAmount,
        Duration $validFor,
        ?TaxBreakdown $initialAmountTaxBreakdown,
        Amount $rebillAmount,
        Duration $repeatEvery,
        ?TaxBreakdown $rebillAmountTaxBreakdown
    ) {
        $this->initialAmount       = $initialAmount;
        $this->validFor            = $validFor;
        $this->initialTaxBreakDown = $initialAmountTaxBreakdown;
        $this->rebillAmount        = $rebillAmount;
        $this->repeatEvery         = $repeatEvery;
        $this->rebillTaxBreakDown  = $rebillAmountTaxBreakdown;
    }

    /**
     * @param Amount       $initialAmount             The intial amount
     * @param Duration     $validFor                  Number of days the bundle is valid
     * @param TaxBreakdown $initialAmountTaxBreakdown The tax breakdown for the inital amount
     * @param Amount       $rebillAmount              The rebill  amount
     * @param Duration     $repeatEvery               Repeat the charge every number of days
     * @param TaxBreakdown $rebillAmountTaxBreakdown  The tax breakdown for the rebill amount
     * @throws Exception\InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @return BundleRebillChargeInformation|BundleSingleChargeInformation
     */
    public static function create(
        Amount $initialAmount,
        Duration $validFor,
        ?TaxBreakdown $initialAmountTaxBreakdown,
        Amount $rebillAmount,
        Duration $repeatEvery,
        ?TaxBreakdown $rebillAmountTaxBreakdown = null
    ): self {

        if (!empty($initialAmountTaxBreakdown)) {
            if ($initialAmount->value() !== $initialAmountTaxBreakdown->afterTaxes()->value()) {
                throw new InvalidAmountException('Initial amount and after tax amount miss match');
            }
        }

        if (!empty($rebillAmountTaxBreakdown)) {
            if ($rebillAmount->value() !== $rebillAmountTaxBreakdown->afterTaxes()->value()) {
                throw new InvalidAmountException('Rebill amount and after tax rebill amount miss match');
            }
        }

        return new static(
            $initialAmount,
            $validFor,
            $initialAmountTaxBreakdown,
            $rebillAmount,
            $repeatEvery,
            $rebillAmountTaxBreakdown
        );
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
     * @return Amount
     */
    public function rebillAmount(): Amount
    {
        return $this->rebillAmount;
    }

    /**
     * @return Duration
     */
    public function repeatEvery(): Duration
    {
        return $this->repeatEvery;
    }

    /**
     * @return TaxBreakdown|null
     */
    public function rebillTaxBreakDown(): ?TaxBreakdown
    {
        return $this->rebillTaxBreakDown;
    }

    /**
     * @return array
     */
    public function fullTaxBreakDownArray(): array
    {
        $response = [];
        if (!empty($this->initialTaxBreakDown())) {
            $response['initialAmount'] = empty($this->initialTaxBreakDown()) ? [] : (
                $this->initialTaxBreakDown()->toArray()
            );
        }
        if (!empty($this->rebillTaxBreakDown())) {
            $response['rebillAmount'] = empty($this->rebillTaxBreakDown()) ? [] : (
                $this->rebillTaxBreakDown()->toArray()
            );
        }

        return $response;
    }
}
