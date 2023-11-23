<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTimeImmutable;
use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class CCTransactionInformation extends TransactionInformation
{
    /**
     * @var string|null
     */
    private $first6;

    /**
     * @var string|null
     */
    private $last4;

    /**
     * @var int|null
     */
    private $cardExpirationYear;

    /**
     * @var int|null
     */
    private $cardExpirationMonth;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * TransactionInformation constructor.
     * @param RetrieveTransaction $response Api response
     * @throws Exception
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

        $this->paymentType         = CCPaymentInfo::PAYMENT_TYPE;
        $this->first6              = $response->getTransaction()->getFirst6();
        $this->last4               = $response->getTransaction()->getLast4();
        $this->cardExpirationYear  = $response->getCardExpirationYear();
        $this->cardExpirationMonth = $response->getCardExpirationMonth();
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return string|null
     */
    public function first6(): ?string
    {
        return $this->first6;
    }

    /**
     * @return string|null
     */
    public function last4(): ?string
    {
        return $this->last4;
    }

    /**
     * @return int|null
     */
    public function cardExpirationYear(): ?int
    {
        return $this->cardExpirationYear;
    }

    /**
     * @return int|null
     */
    public function cardExpirationMonth(): ?int
    {
        return $this->cardExpirationMonth;
    }
}
