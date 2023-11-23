<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\NuData;

class NuDataPurchasedProduct
{
    /**
     * @var float
     */
    private $price;

    /**
     * @var string
     */
    private $bundleId;

    /**
     * @var bool
     */
    private $purchaseSuccessful;

    /**
     * @var string|null
     */
    private $subscriptionId;

    /**
     * @var bool
     */
    private $isTrial;

    /**
     * @var bool
     */
    private $isRecurring;

    /**
     * NuDataPurchasedProduct constructor.
     * @param float       $price              Product Price
     * @param string      $bundleId           Bundle Id
     * @param bool        $purchaseSuccessful Purchase Is Successful
     * @param string|null $subscriptionId     Subscription Id
     * @param bool        $isTrial            Product Is Trial
     * @param bool        $isRecurring        Product Is Recurring
     */
    public function __construct(
        float $price,
        string $bundleId,
        bool $purchaseSuccessful,
        ?string $subscriptionId,
        bool $isTrial = false,
        bool $isRecurring = false
    ) {
        $this->price              = $price;
        $this->bundleId           = $bundleId;
        $this->purchaseSuccessful = $purchaseSuccessful;
        $this->subscriptionId     = $subscriptionId;
        $this->isTrial            = $isTrial;
        $this->isRecurring        = $isRecurring;
    }

    /**
     * @return float
     */
    public function price(): float
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function bundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * @return bool
     */
    public function purchaseSuccessful(): bool
    {
        return $this->purchaseSuccessful;
    }

    /**
     * @return string|null
     */
    public function subscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    /**
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->isTrial;
    }

    /**
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

}