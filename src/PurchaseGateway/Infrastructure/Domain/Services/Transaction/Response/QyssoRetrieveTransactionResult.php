<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBillerFields;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;

class QyssoRetrieveTransactionResult extends RetrieveTransactionResult
{
    public const TYPE_REBILL = 'rebill';
    public const TYPE_SALE   = 'sale';

    /**
     * @var string
     */
    public $previousTransactionId;

    /**
     * @var BillerTransactionCollection
     */
    private $billerTransactions;

    /**
     * @var string
     */
    private $companyNum;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $paymentSubType;

    /**
     * QyssoRetrieveTransactionResult constructor.
     * @param RetrieveTransaction    $response               RetrieveTransaction
     * @param MemberInformation      $memberInformation      MemberInformation
     * @param TransactionInformation $transactionInformation TransactionInformation
     * @param QyssoBillerFields      $billerFields           QyssoBillerFields
     */
    public function __construct(
        RetrieveTransaction $response,
        MemberInformation $memberInformation,
        TransactionInformation $transactionInformation,
        QyssoBillerFields $billerFields
    ) {
        parent::__construct(
            $response->getBillerId(),
            $transactionInformation->billerName(),
            $response->getTransactionId(),
            $response->getCurrency(),
            $response->getSiteId(),
            $response->getPaymentType(),
            $memberInformation,
            $transactionInformation,
            $billerFields
        );

        $this->previousTransactionId = $response->getPreviousTransactionId();
        $this->companyNum            = null;
        $this->initBillerTransactions($response->getBillerTransactions());
        $this->paymentSubType = $response->getPaymentMethod();
    }

    /**
     * @return BillerFields
     */
    public function billerFields(): BillerFields
    {
        return $this->billerFields;
    }

    /**
     * @return BillerTransactionCollection
     */
    public function billerTransactions(): BillerTransactionCollection
    {
        return $this->billerTransactions;
    }

    /**
     * @return TransactionInformation|CCTransactionInformation
     */
    public function transactionInformation()
    {
        return $this->transactionInformation;
    }

    /**
     * @return bool
     */
    public function securedWithThreeD(): bool
    {
        return false;
    }

    /**
     * @return int|null
     */
    public function threeDSecureVersion(): ?int
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function previousTransactionId(): ?string
    {
        return $this->previousTransactionId;
    }

    /**
     * @param array $billerTransactions The biller transactions array
     * @return void
     */
    private function initBillerTransactions(array $billerTransactions): void
    {
        $billerTransactionsCollection = new BillerTransactionCollection();
        /** @var RetrieveTransactionBillerTransactions $billerTransaction */
        foreach ($billerTransactions as $billerTransaction) {
            if (is_null($billerTransaction->getBillerTransactionId())) {
                continue;
            }

            $type = $billerTransaction->getType();

            if ($billerTransaction->getType() !== self::TYPE_REBILL) {
                $type = self::TYPE_SALE;
            }

            $billerTransactionsCollection->add(
                QyssoBillerTransaction::create(
                    $type,
                    $billerTransaction->getBillerTransactionId(),
                    $billerTransaction->getRawBillerResponse(),
                    $billerTransaction->getInitialBillerTransactionId()
                )
            );

            $this->type       = $billerTransaction->getType();
            $this->companyNum = $billerTransaction->getCompanyNum();
        }
        $this->billerTransactions = $billerTransactionsCollection;
    }

    /**
 * @return string|null
 */
    public function companyNum(): ?string
    {
        return $this->companyNum;
    }

    /**
     * @return string|null
     */
    public function type(): ?string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function paymentSubtype(): string
    {
        return $this->paymentSubType;
    }

}
