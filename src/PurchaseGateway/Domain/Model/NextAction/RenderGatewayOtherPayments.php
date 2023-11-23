<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

class RenderGatewayOtherPayments extends NextAction
{
    /**
     * @var string
     */
    public const TYPE = 'renderGatewayOtherPayments';

    /**
     * @var array
     */
    private $availablePaymentMethods;

    /**
     * RenderGatewayOtherPayments constructor.
     * @param array $availablePaymentMethods Available payment methods
     */
    private function __construct(array $availablePaymentMethods)
    {
        $this->availablePaymentMethods = $availablePaymentMethods;
    }

    /**
     * @param array $availablePaymentMethods Available payment methods
     * @return static
     */
    public static function create(array $availablePaymentMethods): self
    {
        return new static($availablePaymentMethods);
    }

    /**
     * @return array
     */
    public function availablePaymentMethods(): array
    {
        return $this->availablePaymentMethods;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'                    => $this->type(),
            'availablePaymentMethods' => $this->availablePaymentMethods()
        ];
    }
}
