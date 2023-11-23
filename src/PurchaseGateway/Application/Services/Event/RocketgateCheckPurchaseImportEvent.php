<?php

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCheckRetrieveTransactionResult;

class RocketgateCheckPurchaseImportEvent extends PurchaseEvent
{
    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $merchantPassword;

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

    public function __construct(
        RocketgateCheckRetrieveTransactionResult $retrieveTransactionResult,
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData = null
    ) {
        parent::__construct(
            $purchaseProcessedEvent->purchaseId(),
            new \DateTimeImmutable(),
            $retrieveTransactionResult->billerName(),
            $purchaseProcessedEvent->purchaseId(),
            $retrieveTransactionResult->currency(),
            $retrieveTransactionResult->paymentType(),
            $purchaseProcessedEvent->memberId(),
            $retrieveTransactionResult->memberInformation()->email(),
            $retrieveTransactionResult->memberInformation()->phoneNumber(),
            $retrieveTransactionResult->memberInformation()->firstName(),
            $retrieveTransactionResult->memberInformation()->lastName(),
            $retrieveTransactionResult->memberInformation()->address(),
            $retrieveTransactionResult->memberInformation()->city(),
            $retrieveTransactionResult->memberInformation()->state(),
            $retrieveTransactionResult->memberInformation()->zip(),
            $retrieveTransactionResult->memberInformation()->country(),
            $purchaseProcessedEvent->subscriptionUsername(),
            $purchaseProcessedEvent->subscriptionPassword(),
            $purchaseProcessedEvent->atlasCode(),
            $purchaseProcessedEvent->atlasData(),
            $purchaseProcessedEvent->ipAddress()
        );

        $this->merchantId       = $retrieveTransactionResult->merchantId();
        $this->merchantPassword = $retrieveTransactionResult->merchantPassword();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(get_object_vars($this), parent::toArray());
    }
}