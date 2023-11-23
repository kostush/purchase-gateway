<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class TaxInformation
{
    /**
     * @var string|null
     */
    private $taxName;

    /**
     * @var Amount|null
     */
    private $taxRate;

    /**
     * @var string|null
     */
    private $taxApplicationId;

    /**
     * @var string|null
     */
    private $taxCustom;

    /**
     * @var TaxType
     */
    private $taxType;

    /**
     * TaxInformation constructor.
     * @param string|null $taxName          The tax name object
     * @param Amount|null $taxRate          The tax rate object
     * @param string|null $taxApplicationId The tax application id
     * @param string|null $taxCustom        Custom information for tax
     * @param TaxType     $taxType          Tax Type.
     */
    private function __construct(
        ?string $taxName,
        ?Amount $taxRate,
        ?string $taxApplicationId,
        ?string $taxCustom,
        TaxType $taxType
    ) {
        $this->taxName          = $taxName;
        $this->taxRate          = $taxRate;
        $this->taxApplicationId = $taxApplicationId;
        $this->taxCustom        = $taxCustom;
        $this->taxType          = $taxType;
    }

    /**
     * @param string|null $taxName          The tax name object
     * @param Amount|null $taxRate          The tax rate object
     * @param string|null $taxApplicationId The tax application id
     * @param string|null $taxCustom        Custom information for tax
     * @param TaxType     $taxType          Tax Type.
     * @return TaxInformation
     */
    public static function create(
        ?string $taxName,
        ?Amount $taxRate,
        ?string $taxApplicationId,
        ?string $taxCustom,
        TaxType $taxType
    ): self {
        return new static($taxName, $taxRate, $taxApplicationId, $taxCustom, $taxType);
    }

    /**
     * @return string|null
     */
    public function taxName(): ?string
    {
        return $this->taxName;
    }

    /**
     * @return Amount|null
     */
    public function taxRate(): ?Amount
    {
        return $this->taxRate;
    }

    /**
     * @return string|null
     */
    public function taxApplicationId(): ?string
    {
        return $this->taxApplicationId;
    }

    /**
     * @return string|null
     */
    public function taxCustom(): ?string
    {
        return $this->taxCustom;
    }

    /**
     * @return TaxType
     */
    public function taxType(): TaxType
    {
        return $this->taxType;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $taxInfo = [];

        if (!empty($this->taxApplicationId())) {
            $taxInfo['taxApplicationId'] = (string) $this->taxApplicationId();
        }

        if (!empty($this->taxName())) {
            $taxInfo['taxName'] = (string) $this->taxName();
        }

        if (!empty($this->taxRate())) {
            $taxInfo['taxRate'] = (float) $this->taxRate()->value();
        }

        if (!empty($this->taxCustom())) {
            $taxInfo['custom'] = (string) $this->taxCustom();
        }

        if (!empty($this->taxType())) {
            $taxInfo['taxType'] = (string) $this->taxType();
        }

        return $taxInfo;
    }
}
