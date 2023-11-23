<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class NetbillingBillerFields implements BillerFields
{
    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $siteTag;

    /**
     * @var string|null
     */
    private $binRouting;

    /**
     * @var string|null
     */
    private $merchantPassword;

    /**
     * @var bool
     */
    protected $disableFraudChecks;

    /**
     * NetbillingBillerFields constructor.
     * @param string      $accountId          AccountId
     * @param string      $siteTag            Site tag
     * @param string|null $binRouting         Bin routing
     * @param string|null $merchantPassword   Merchant password
     * @param boolean     $disableFraudChecks disable Fraud Checks flag to send Netbilling
     */
    private function __construct(
        string $accountId,
        string $siteTag,
        ?string $binRouting,
        ?string $merchantPassword,
        bool $disableFraudChecks = false
    ) {
        $this->accountId          = $accountId;
        $this->siteTag            = $siteTag;
        $this->binRouting         = $binRouting;
        $this->merchantPassword   = $merchantPassword;
        $this->disableFraudChecks = $disableFraudChecks;
    }

    /**
     * @param string      $accountId          accountId
     * @param string      $siteTag            site tag
     * @param string|null $binRouting         Bin routing
     * @param string|null $merchantPassword   Merchant password
     * @param boolean     $disableFraudChecks disable Fraud Checks flag to send Netbilling
     * @return NetbillingBillerFields
     */
    public static function create(
        string $accountId,
        string $siteTag,
        ?string $binRouting = null,
        ?string $merchantPassword = null,
        bool $disableFraudChecks = false
    ): NetbillingBillerFields {
        return new self($accountId, $siteTag, $binRouting, $merchantPassword, $disableFraudChecks);
    }

    /**
     * @return string
     */
    public function siteTag(): string
    {
        return $this->siteTag;
    }

    /**
     * @return string
     */
    public function accountId(): string
    {
        return $this->accountId;
    }

    /**
     * @return string|null
     */
    public function binRouting(): ?string
    {
        return $this->binRouting;
    }

    /**
     * @return string|null
     */
    public function merchantPassword(): ?string
    {
        return $this->merchantPassword;
    }

    public function disableFraudChecks(): bool
    {
        return $this->disableFraudChecks;
    }

    /**
     * @param bool $flag
     * @return bool
     */
    public function setDisableFraudChecks(bool $flag)
    {
        return $this->disableFraudChecks = $flag;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'accountId'          => $this->accountId(),
            'siteTag'            => $this->siteTag(),
            'binRouting'         => $this->binRouting(),
            'merchantPassword'   => $this->merchantPassword(),
            'disableFraudChecks' => $this->disableFraudChecks()
        ];
    }
}
