<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;
use Ramsey\Uuid\Uuid;

class ExistingCCPaymentInfo extends CCPaymentInfo implements ExistingPaymentInfo
{
    /**
     * @var string
     */
    protected $cardHash;

    /**
     * @var string|null
     */
    protected $paymentTemplateId;

    /**
     * @var array|null
     */
    protected $billerFields;

    /**
     * ExistingCCPaymentInfo constructor.
     *
     * @param string      $cardHash          Credit Card Hash
     * @param string|null $paymentTemplateId Payment Template Id
     * @param string|null $paymentMethod     Payment Method
     * @param array|null  $billerFields      Biller fields
     *
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function __construct(
        string $cardHash,
        ?string $paymentTemplateId,
        ?string $paymentMethod,
        ?array $billerFields
    ) {
        parent::__construct(self::PAYMENT_TYPE, $paymentMethod);
        $this->initCardHash($cardHash);
        $this->initPaymentTemplateId($paymentTemplateId);
        $this->initBillerFields($billerFields);
    }

    /**
     * @param string      $cardHash          Credit Card Hash
     * @param string|null $paymentTemplateId Payment Template Id
     * @param string|null $paymentMethod     Payment Method
     * @param array|null  $billerFields      Biller fields
     *
     * @return self
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        string $cardHash,
        ?string $paymentTemplateId,
        ?string $paymentMethod,
        ?array $billerFields
    ): CCPaymentInfo {
        return new static($cardHash, $paymentTemplateId, $paymentMethod, $billerFields);
    }

    /**
     * @return string
     */
    public function cardHash(): string
    {
        return $this->cardHash;
    }

    /**
     * @return string|null
     */
    public function paymentTemplateId(): ?string
    {
        return $this->paymentTemplateId;
    }

    /**
     * @return array|null
     */
    public function billerFields(): ?array
    {
        return $this->billerFields;
    }

    /**
     * @param string $cardHash Card Hash
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidPaymentInfoException
     */
    private function initCardHash(string $cardHash): void
    {
        if (empty($cardHash)) {
            throw new InvalidPaymentInfoException('cardHash');
        }

        $this->cardHash = $cardHash;
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
     * @param array|null $billerFields Biller fields
     * @return void
     */
    private function initBillerFields(?array $billerFields): void
    {
        $this->billerFields = $billerFields;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray(): array
    {
        return [
            'cardHash'          => ObfuscatedData::OBFUSCATED_STRING,
            'paymentTemplateId' => $this->paymentTemplateId ?? Uuid::uuid4()
        ];
    }
}
