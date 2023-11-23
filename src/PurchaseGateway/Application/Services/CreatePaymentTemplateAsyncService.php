<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\PaymentTemplateBillerFieldsFactory;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PaymentTemplateBaseEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PaymentTemplateCreatedEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Id;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\ServiceBus\Event as MessageEvent;
use ProBillerNG\ServiceBus\InvalidMessageException;

class CreatePaymentTemplateAsyncService
{
    /**
     * @var array
     */
    private $acceptedBillers = [
        RocketgateBiller::BILLER_NAME,
        NetbillingBiller::BILLER_NAME,
        EpochBiller::BILLER_NAME,
    ];

    /**
     * @var ServiceBusFactory
     */
    private $serviceBusFactory;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var Id|SessionId
     */
    private $requestSessionId;

    /**
     * CreatePaymentTemplateAsyncService constructor.
     *
     * @param ServiceBusFactory  $serviceBusFactory
     * @param TransactionService $transactionService
     *
     * @throws \Exception
     */
    public function __construct(
        ServiceBusFactory $serviceBusFactory,
        TransactionService $transactionService
    ) {
        $this->serviceBusFactory  = $serviceBusFactory;
        $this->transactionService = $transactionService;
        $this->requestSessionId   = SessionId::createFromString(Log::getSessionId());
    }

    /**
     * @param string $transactionId
     * @param string $purchaseId
     * @param string $memberId
     *
     * @throws InvalidBillerFieldsDataException
     * @throws InvalidMessageException
     * @throws LoggerException
     * @throws UnknownBillerNameException
     * @throws \Exception
     */
    public function create(
        string $transactionId,
        string $purchaseId,
        string $memberId
    ): void {
        $mainTransactionData = $this->retrieveTransactionData($transactionId);

        $billerName = $mainTransactionData->billerName();
        $this->validateBillerName($billerName);

        $billerFields = PaymentTemplateBillerFieldsFactory::create($mainTransactionData);

        $first6   = null;
        $last4    = null;
        $expYear  = null;
        $expMonth = null;

        if ($mainTransactionData->transactionInformation() instanceof CCTransactionInformation) {
            $first6   = $mainTransactionData->transactionInformation()->first6();
            $last4    = $mainTransactionData->transactionInformation()->last4();
            $expMonth = $mainTransactionData->transactionInformation()->cardExpirationMonth();
            $expYear  = $mainTransactionData->transactionInformation()->cardExpirationYear();
        }

        $paymentCreatedEvent = new PaymentTemplateCreatedEvent(
            $purchaseId,
            $mainTransactionData->transactionInformation()->paymentType(),
            $first6,
            $last4,
            $expYear,
            $expMonth,
            $mainTransactionData->transactionInformation()->createdAt(),
            $mainTransactionData->billerName(),
            $memberId,
            $billerFields
        );

        $this->pushEventToServiceBus($paymentCreatedEvent);
    }

    /**
     * @param string $billerName The biller name
     *
     * @return void
     *
     * @throws UnknownBillerNameException
     */
    private function validateBillerName(string $billerName): void
    {
        if (!in_array($billerName, $this->acceptedBillers)) {
            throw new UnknownBillerNameException($billerName);
        }
    }

    /**
     * @param PaymentTemplateBaseEvent $event
     *
     * @throws InvalidMessageException
     * @throws LoggerException
     */
    private function pushEventToServiceBus(PaymentTemplateBaseEvent $event): void
    {
        $messageEvent = new MessageEvent($event->toArray());

        Log::info('PaymentTemplateCreationEvent Before publication message created', ['type' => $messageEvent->type()]);
        $serviceBus = $this->serviceBusFactory()->make();
        $serviceBus->publish($messageEvent);
        Log::info('PaymentTemplateCreationEvent Published message',
            [
                'type' => $messageEvent->type(),
                'body' => $messageEvent->body()
            ]);
        Log::info('PaymentTemplateCreationEvent After publication message', $event->toArray());
    }

    /**
     * @param string $transactionId The transaction id
     *
     * @return RetrieveTransactionResult
     * @throws \Exception
     */
    protected function retrieveTransactionData(string $transactionId): RetrieveTransactionResult
    {
        return $this->transactionService()
            ->getTransactionDataBy(
                TransactionId::createFromString($transactionId),
                $this->requestSession()
            );
    }

    /**
     * @return ServiceBusFactory
     */
    private function serviceBusFactory(): ServiceBusFactory
    {
        return $this->serviceBusFactory;
    }

    /**
     * @return TransactionService
     */
    private function transactionService(): TransactionService
    {
        return $this->transactionService;
    }

    /**
     * @return Id|SessionId
     */
    private function requestSession(): Id
    {
        return $this->requestSessionId;
    }
}