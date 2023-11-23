<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class EmptyTransactionInformation extends TransactionInformation
{
    /**
     * @var string
     */
    private $paymentType = '';

    /**
     * TransactionInformation constructor.
     * @param RetrieveTransaction $response Api response
     * @throws \Exception
     */
    public function __construct(RetrieveTransaction $response)
    {
        parent::__construct(
            $response->getTransaction()->getTransactionId(),
            (float) $response->getTransaction()->getAmount(),
            $response->getTransaction()->getStatus(),
            new \DateTimeImmutable($response->getTransaction()->getCreatedAt()),
            (float) $response->getTransaction()->getRebillAmount(),
            (int) $response->getTransaction()->getRebillFrequency(),
            (int) $response->getTransaction()->getRebillStart(),
            $response->getBillerId()
        );
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }
}
