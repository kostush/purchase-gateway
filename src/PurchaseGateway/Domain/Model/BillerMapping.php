<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class BillerMapping
{
    /** @var SiteId */
    private $siteId;

    /** @var BusinessGroupId */
    private $businessGroupId;

    /** @var string */
    private $currencyCode;

    /** @var string */
    private $billerName;

    /** @var BillerFields */
    private $billerFields;

    /**
     * BillerFields constructor.
     *
     * @param SiteId          $siteId          Site id
     * @param BusinessGroupId $businessGroupId Business group id
     * @param string          $currencyCode    Currency code
     * @param string          $billerName      Biller name
     * @param BillerFields    $billerFields    Biller fields
     */
    private function __construct(
        SiteId $siteId,
        BusinessGroupId $businessGroupId,
        string $currencyCode,
        string $billerName,
        BillerFields $billerFields
    ) {
        $this->siteId          = $siteId;
        $this->businessGroupId = $businessGroupId;
        $this->currencyCode    = $currencyCode;
        $this->billerName      = $billerName;
        $this->billerFields    = $billerFields;
    }

    /**
     * @param SiteId          $siteId          Site id
     * @param BusinessGroupId $businessGroupId Business group id
     * @param string          $currencyCode    Currency code
     * @param string          $billerName      Biller name
     * @param BillerFields    $billerFields    Biller fields
     * @return BillerMapping
     */
    public static function create(
        SiteId $siteId,
        BusinessGroupId $businessGroupId,
        string $currencyCode,
        string $billerName,
        BillerFields $billerFields
    ): self {
        return new static($siteId, $businessGroupId, $currencyCode, $billerName, $billerFields);
    }

    /**
     * @return SiteId
     */
    public function siteId(): SiteId
    {
        return $this->siteId;
    }

    /**
     * @return BusinessGroupId
     */
    public function businessGroupId(): BusinessGroupId
    {
        return $this->businessGroupId;
    }

    /**
     * @return string
     */
    public function currencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @return string
     */
    public function billerName(): string
    {
        return $this->billerName;
    }

    /**
     * @return BillerFields
     */
    public function billerFields(): BillerFields
    {
        return $this->billerFields;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'siteId'          => $this->siteId()->value(),
            'businessGroupId' => $this->businessGroupId()->value(),
            'currencyCode'    => $this->currencyCode(),
            'billerName'      => $this->billerName(),
            'billerFields'    => $this->billerFields()->toArray(),
        ];
    }
}
