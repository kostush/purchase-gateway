<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTimeImmutable;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class OtherPaymentTypeTransactionInformation extends TransactionInformation
{
    /**
     * @var string
     */
    private $paymentType;

    /**
     * OtherPaymentTypeTransactionInformation constructor.
     * @param RetrieveTransaction $response Response
     * @throws \Exception
     */
    public function __construct(RetrieveTransaction $response)
    {
        parent::__construct(
            $response->getTransaction()->getTransactionId(),
            (float) $response->getTransaction()->getAmount(),
            $response->getTransaction()->getStatus(),
            new DateTimeImmutable($response->getTransaction()->getCreatedAt()),
            (float) $response->getTransaction()->getRebillAmount(),
            (int) $response->getTransaction()->getRebillFrequency(),
            (int) $response->getTransaction()->getRebillStart(),
            $response->getBillerId()
        );

        $this->initPaymentType($response->getPaymentType());
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @param string $paymentType Payment type
     * @return void
     * @throws InvalidResponseException
     * @throws Exception
     */
    private function initPaymentType(string $paymentType): void
    {
        if (!in_array($paymentType, OtherPaymentTypeInfo::PAYMENT_TYPES)) {
            throw new InvalidResponseException('Invalid payment type.');
        }

        $this->paymentType = $paymentType;
    }
}
