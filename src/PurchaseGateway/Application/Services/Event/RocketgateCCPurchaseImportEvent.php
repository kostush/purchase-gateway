<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;

class RocketgateCCPurchaseImportEvent extends CCPurchaseEvent
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
     * @var string|null
     */
    protected $cardHash;

    /**
     * @var string|null
     */
    protected $merchantAccount;

    /**
     * @var string|null
     */
    protected $cardDescription;

    /**
     * RocketgateCCPurchaseEvent constructor.
     *
     * @param RocketgateCCRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param PurchaseProcessed                     $purchaseProcessedEvent    Purchase event
     * @param PaymentTemplate|null                  $paymentTemplateData       PaymentTemplate
     *
     * @throws Exception
     */
    public function __construct(
        RocketgateCCRetrieveTransactionResult $retrieveTransactionResult,
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

        $this->merchantId       = $retrieveTransactionResult->merchantId();
        $this->merchantPassword = $retrieveTransactionResult->merchantPassword();
        $this->cardHash         = $retrieveTransactionResult->cardHash();
        $this->merchantAccount  = $retrieveTransactionResult->merchantAccount();
        $this->cardDescription  = $retrieveTransactionResult->cardDescription();
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
     * @return null|string
     */
    public function cardHash(): ?string
    {
        return $this->cardHash;
    }

    /**
     * @return null|string
     */
    public function merchantAccount(): ?string
    {
        return $this->merchantAccount;
    }

    /**
     * @return null|string
     */
    public function cardDescription(): ?string
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
