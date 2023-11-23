<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;
use Ramsey\Uuid\Uuid;

class ExistingOtherPaymentInfo extends OtherPaymentTypeInfo implements ExistingPaymentInfo
{
    /**
     * @var string|null
     */
    protected $paymentTemplateId;

    /**
     * ExistingOtherPaymentInfo constructor.
     * @param string $paymentTemplateId Payment Template Id
     * @param string|null $paymentType       Payment type
     * @param string|null $paymentMethod     Payment Method
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function __construct(
        string $paymentTemplateId,
        ?string $paymentType,
        ?string $paymentMethod
    ) {
        parent::__construct($paymentType, $paymentMethod);
        $this->initPaymentTemplateId($paymentTemplateId);
    }

    /**
     * @param string $paymentTemplateId Payment Template Id
     * @param string|null $paymentType       Payment Type
     * @param string|null $paymentMethod     Payment Method
     * @return self
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        string $paymentTemplateId,
        ?string $paymentType,
        ?string $paymentMethod
    ): self {
        return new static($paymentTemplateId, $paymentType, $paymentMethod);
    }

    /**
     * @return string|null
     */
    public function cardHash(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function paymentTemplateId(): ?string
    {
        return $this->paymentTemplateId;
    }

    /**
     * @param string|null $paymentTemplateId Payment Template Id
     * @return void
     */
    private function initPaymentTemplateId(?string $paymentTemplateId): void
    {
        $this->paymentTemplateId = $paymentTemplateId;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray(): array
    {
        return [
            'paymentTemplateId' => $this->paymentTemplateId ?? Uuid::uuid4()
        ];
    }
}
