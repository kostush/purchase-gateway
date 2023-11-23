<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;


class MerchantPassword
{
    /**
     * @var string
     */
    private $merchantPassword;

    /**
     * MerchantPassword constructor.
     *
     * @param string $merchantPassword
     */
    public function __construct(string $merchantPassword)
    {
        $this->merchantPassword = $merchantPassword;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->merchantPassword;
    }
}