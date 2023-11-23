<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class TaxBreakdown
{
    /**
     * @var Amount
     */
    private $beforeTaxes;

    /**
     * @var Amount
     */
    private $taxes;

    /**
     * @var Amount
     */
    private $afterTaxes;

    /**
     * TaxBreakdown constructor.
     * @param Amount $beforeTaxes The amount before applying taxes
     * @param Amount $taxes       The taxes amount
     * @param Amount $afterTaxes  The amount after applying taxes
     */
    private function __construct(
        Amount $beforeTaxes,
        Amount $taxes,
        Amount $afterTaxes
    ) {
        $this->beforeTaxes = $beforeTaxes;
        $this->taxes       = $taxes;
        $this->afterTaxes  = $afterTaxes;
    }

    /**
     * @param Amount $beforeTaxes The amount before applying taxes
     * @param Amount $taxes       The taxes amount
     * @param Amount $afterTaxes  The amount after applying taxes
     * @return TaxBreakdown
     */
    public static function create(
        Amount $beforeTaxes,
        Amount $taxes,
        Amount $afterTaxes
    ): self {
        return new static($beforeTaxes, $taxes, $afterTaxes);
    }

    /**
     * @return Amount
     */
    public function beforeTaxes(): Amount
    {
        return $this->beforeTaxes;
    }

    /**
     * @return Amount
     */
    public function taxes(): Amount
    {
        return $this->taxes;
    }

    /**
     * @return Amount
     */
    public function afterTaxes(): Amount
    {
        return $this->afterTaxes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'beforeTaxes' => $this->beforeTaxes()->value(),
            'taxes'       => $this->taxes()->value(),
            'afterTaxes'  => $this->afterTaxes()->value(),
        ];
    }
}
