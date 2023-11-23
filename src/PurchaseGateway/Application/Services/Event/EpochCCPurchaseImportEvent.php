<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;

class EpochCCPurchaseImportEvent extends CCPurchaseEvent
{
    /**
     * @var string
     */
    protected $paymentSubtype;

    /**
     * @var
     */
    protected $memberName;

    /**
     * EpochCCRetrieveTransactionResult constructor.
     *
     * @param EpochCCRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param PurchaseProcessed                $purchaseProcessedEvent    Purchase event
     * @param PaymentTemplate|null             $paymentTemplateData       PaymentTemplate
     *
     * @throws UnknownBillerIdException
     * @throws \Exception
     */
    public function __construct(
        EpochCCRetrieveTransactionResult $retrieveTransactionResult,
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData
    ) {
        $email = $purchaseProcessedEvent->memberInfo()['email'];

        if (empty($email)) {
            $email = $retrieveTransactionResult->memberInformation()->email();
        }

        parent::__construct(
            $purchaseProcessedEvent->purchaseId(),
            new \DateTimeImmutable(),
            $retrieveTransactionResult->billerName(),
            $purchaseProcessedEvent->purchaseId(),
            $retrieveTransactionResult->currency(),
            $retrieveTransactionResult->paymentType(),
            $purchaseProcessedEvent->memberId(),
            $email,
            $retrieveTransactionResult->memberInformation()->phoneNumber(),
            $retrieveTransactionResult->memberInformation()->firstName(),
            $retrieveTransactionResult->memberInformation()->lastName(),
            $retrieveTransactionResult->memberInformation()->address(),
            $retrieveTransactionResult->memberInformation()->city(),
            $retrieveTransactionResult->memberInformation()->state(),
            $retrieveTransactionResult->memberInformation()->zip(),
            $retrieveTransactionResult->memberInformation()->country(),
            null,
            null,
            $purchaseProcessedEvent->subscriptionUsername(),
            $purchaseProcessedEvent->subscriptionPassword(),
            $purchaseProcessedEvent->atlasCode(),
            $purchaseProcessedEvent->atlasData(),
            $purchaseProcessedEvent->ipAddress(),
            null,
            null
        );

        $this->paymentSubtype = $retrieveTransactionResult->paymentSubtype();
        $this->memberName     = $retrieveTransactionResult->memberInformation()->name();
    }

    /**
     * @return string
     */
    public function paymentSubtype(): string
    {
        return $this->paymentSubtype;
    }

    /**
     * @return string|null
     */
    public function memberName(): ?string
    {
        return $this->memberName;
    }
}
