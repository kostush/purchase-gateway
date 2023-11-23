<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class TaxType
{
    /** @var string  */
    private const TAX_TYPE = 'taxType';

    /** @var string */
    public const NO_TAX = 'no';

    /** @var string */
    public const UNKNOWN = 'unknown';

    /**
     * @var string
     */
    private $typeName;

    /**
     * Tax Type constructor.
     * @param string $typeName Tax Type
     */
    private function __construct(?string $typeName)
    {
        $this->typeName = $this->initializeName($typeName);
    }

    /**
     * @param string $typeName Tax Type Name
     * @return TaxType
     */
    public static function create(?string $typeName): self
    {
        return new static($typeName);
    }

    /**
     * @param string $typeName Type Name
     * @return string
     */
    private function initializeName(?string $typeName): string
    {
        if (!empty($typeName)) {
            return strtolower($typeName);
        }
        return self::UNKNOWN;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function value(): string
    {
        return $this->typeName;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->typeName;
    }

    /**
     * @param array|null $taxInformation Tax Information.
     * @return string
     */
    public static function createFromTaxInformation(?array $taxInformation): string
    {
        if (!empty($taxInformation) && array_key_exists(self::TAX_TYPE, $taxInformation)) {
            return $taxInformation[self::TAX_TYPE];
        }
        return TaxType::NO_TAX;
    }
}
