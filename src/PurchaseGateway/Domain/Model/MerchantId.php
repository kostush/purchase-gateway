<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;


class MerchantId
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * MerchantId constructor.
     *
     * @param string $merchantId
     */
    public function __construct(string $merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->merchantId;
    }
}