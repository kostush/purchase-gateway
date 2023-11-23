<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use DateTimeImmutable;
use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;

class QyssoDebitPurchaseImportEvent extends CCPurchaseEvent
{
    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var
     */
    protected $memberName;

    /**
     * @var string
     */
    protected $companyNum;

    /**
     * QyssoRetrieveTransactionResult constructor.
     *
     * @param QyssoRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param PurchaseProcessed              $purchaseProcessedEvent    Purchase event
     *
     * @throws Exception
     */
    public function __construct(
        QyssoRetrieveTransactionResult $retrieveTransactionResult,
        PurchaseProcessed $purchaseProcessedEvent
    ) {
        $email = $purchaseProcessedEvent->memberInfo()['email'];

        if (empty($email)) {
            $email = $retrieveTransactionResult->memberInformation()->email();
        }

        parent::__construct(
            $purchaseProcessedEvent->purchaseId(),
            new DateTimeImmutable(),
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

        $this->paymentMethod = $retrieveTransactionResult->paymentSubtype();
        $this->memberName    = $retrieveTransactionResult->memberInformation()->name();
        $this->companyNum    = $retrieveTransactionResult->companyNum();
    }

    /**
     * @return string
     */
    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @return string|null
     */
    public function memberName(): ?string
    {
        return $this->memberName;
    }
}
