<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\NuData;

class NuDataCard
{
    /**
     * @var string
     */
    private $holderName;

    /**
     * @var string
     */
    private $cardNumber;

    /**
     * NuDataCard constructor.
     * @param string $holderName Holder Name
     * @param string $cardNumber Card Name
     */
    public function __construct(
        string $holderName,
        string $cardNumber
    ) {
        $this->holderName = $holderName;
        $this->cardNumber = $cardNumber;
    }

    /**
     * @return string
     */
    public function holderName(): string
    {
        return $this->holderName;
    }

    /**
     * @return string
     */
    public function cardNumber(): string
    {
        return $this->cardNumber;
    }
}