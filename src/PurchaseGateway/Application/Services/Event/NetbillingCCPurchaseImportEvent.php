<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use DateTimeImmutable;
use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NetbillingCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;

class NetbillingCCPurchaseImportEvent extends CCPurchaseEvent
{
    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string|null
     */
    private $cardHash;

    /**
     * @var string|null
     */
    private $cardDescription;

    /**
     * NetbillingCCPurchaseEvent constructor.
     *
     * @param NetbillingCCRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param PurchaseProcessed                     $purchaseProcessedEvent    Purchase event
     * @param PaymentTemplate|null                  $paymentTemplateData       PaymentTemplate
     *
     * @throws Exception
     */
    public function __construct(
        NetbillingCCRetrieveTransactionResult $retrieveTransactionResult,
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData = null
    ) {
        if ($retrieveTransactionResult->transactionInformation() instanceof NewCCTransactionInformation) {
            $firstSix            = $retrieveTransactionResult->transactionInformation()->first6();
            $lastFour            = $retrieveTransactionResult->transactionInformation()->last4();
            $cardExpirationYear  = $retrieveTransactionResult->transactionInformation()->cardExpirationYear();
            $cardExpirationMonth = $retrieveTransactionResult->transactionInformation()->cardExpirationMonth();
        } else {
            $firstSix            = $paymentTemplateData ? $paymentTemplateData->firstSix() : null;
            $lastFour            = $paymentTemplateData ? $paymentTemplateData->lastFour() : null;
            $cardExpirationYear  = $paymentTemplateData ? $paymentTemplateData->expirationYear() : null;
            $cardExpirationMonth = $paymentTemplateData ? $paymentTemplateData->expirationMonth() : null;
        }

        parent::__construct(
            $purchaseProcessedEvent->purchaseId(),
            new DateTimeImmutable(),
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
            $firstSix,
            $lastFour,
            $purchaseProcessedEvent->subscriptionUsername(),
            $purchaseProcessedEvent->subscriptionPassword(),
            $purchaseProcessedEvent->atlasCode(),
            $purchaseProcessedEvent->atlasData(),
            $purchaseProcessedEvent->ipAddress(),
            (string) $cardExpirationYear,
            (string) $cardExpirationMonth
        );

        $this->accountId       = $retrieveTransactionResult->billerFields()->accountId();
        $this->cardHash        = $retrieveTransactionResult->cardHash();
        $this->cardDescription = $retrieveTransactionResult->cardDescription();
    }

    /**
     * @return string
     */
    public function accountId()
    {
        return $this->accountId;
    }

    /**
     * @return string|null
     */
    public function cardHash()
    {
        return $this->cardHash;
    }

    /**
     * @return string|null
     */
    public function cardDescription()
    {
        return $this->cardDescription;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(get_object_vars($this), parent::toArray());
    }
}
