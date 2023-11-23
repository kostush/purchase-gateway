<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class RocketgateBillerFields implements BillerFields
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $merchantPassword;

    /**
     * @var string
     */
    private $billerSiteId;

    /**
     * @var string
     */
    private $merchantCustomerId;

    /**
     * @var string
     */
    private $merchantInvoiceId;

    /**
     * @var string
     */
    private $sharedSecret;

    /**
     * @var bool
     */
    private $simplified3DS;

    /**
     * RocketgateBillerFields constructor.
     * @param string      $merchantId         Merchant id
     * @param string      $merchantPassword   Merchant Password
     * @param string      $billerSiteId       Biller site id
     * @param string      $sharedSecret       Shared secret
     * @param bool        $simplified3DS      Simplified 3DS
     * @param string|null $merchantCustomerId Merchant Customer Id
     * @param string|null $merchantInvoiceId  Merchant Invoice Id
     */
    private function __construct(
        string $merchantId,
        string $merchantPassword,
        string $billerSiteId,
        string $sharedSecret,
        bool $simplified3DS,
        ?string $merchantCustomerId,
        ?string $merchantInvoiceId
    ) {
        $this->merchantId         = $merchantId;
        $this->merchantPassword   = $merchantPassword;
        $this->billerSiteId       = $billerSiteId;
        $this->sharedSecret       = $sharedSecret;
        $this->simplified3DS      = $simplified3DS;
        $this->merchantCustomerId = $merchantCustomerId;
        $this->merchantInvoiceId  = $merchantInvoiceId;
    }

    /**
     * @param string      $merchantId         Merchant id
     * @param string      $merchantPassword   Merchant Password
     * @param string      $billerSiteId       Biller site id
     * @param string      $sharedSecret       Shared secret
     * @param bool        $simplified3DS      Simplified 3DS
     * @param string|null $merchantCustomerId Merchant Customer Id
     * @param string|null $merchantInvoiceId  Merchant Invoice Id
     * @return RocketgateBillerFields
     */
    public static function create(
        string $merchantId,
        string $merchantPassword,
        string $billerSiteId,
        string $sharedSecret,
        bool $simplified3DS,
        ?string $merchantCustomerId = null,
        ?string $merchantInvoiceId = null
    ): RocketgateBillerFields {
        return new self(
            $merchantId,
            $merchantPassword,
            $billerSiteId,
            $sharedSecret,
            $simplified3DS,
            $merchantCustomerId,
            $merchantInvoiceId
        );
    }

    /**
     * @return string
     */
    public function merchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * @return string
     */
    public function merchantPassword(): string
    {
        return $this->merchantPassword;
    }

    /**
     * @return string
     */
    public function billerSiteId(): string
    {
        return $this->billerSiteId;
    }

    /**
     * @return string
     */
    public function sharedSecret(): string
    {
        return $this->sharedSecret;
    }

    /**
     * @return bool
     */
    public function simplified3DS(): bool
    {
        return $this->simplified3DS;
    }

    /**
     * @return string|null
     */
    public function merchantCustomerId(): ?string
    {
        return $this->merchantCustomerId;
    }

    /**
     * @return string|null
     */
    public function merchantInvoiceId(): ?string
    {
        return $this->merchantInvoiceId;
    }

    /**
     * @param string $merchantCustomerId Merchant Customer Id
     * @return void
     */
    public function setMerchantCustomerId(string $merchantCustomerId): void
    {
        $this->merchantCustomerId = $merchantCustomerId;
    }

    /**
     * @param string $merchantInvoiceId Merchant Invoice Id
     *
     * @return void
     */
    public function setMerchantInvoiceId(string $merchantInvoiceId): void
    {
        $this->merchantInvoiceId = $merchantInvoiceId;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'merchantId'         => $this->merchantId(),
            'merchantPassword'   => $this->merchantPassword(),
            'billerSiteId'       => $this->billerSiteId(),
            'sharedSecret'       => $this->sharedSecret(),
            'simplified3DS'      => $this->simplified3DS(),
            'merchantCustomerId' => $this->merchantCustomerId(),
            'merchantInvoiceId'  => $this->merchantInvoiceId(),
        ];
    }
}