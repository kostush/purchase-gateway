<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI\Processed;

use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class AttemptedTransactions
{
    public const ALLOWED_ATTEMPTS = 2;
    /**
     * @var int
     */
    private $submitAttempt;

    /**
     * @var string
     */
    private $billerName;

    /**
     * @var bool
     */
    private $success;

    /**
     * @var array
     */
    private $transactions;

    /** @var bool */
    private $existingPaymentTemplateUsed = false;

    /** @var bool */
    private $defaultBiller = false;

    /** @var int */
    private $configuredAllowedAttempts;

    /**
     * AttemptedTransactions constructor.
     * @param int        $submitAttempt        Submit attempt
     * @param string     $billerName           Biller name
     * @param string     $mainTransactionState Main Transaction state
     * @param array      $mainTransaction      Main transaction
     * @param array      $crossSales           Cross sales
     * @param null|array $paymentTemplate      Payment Template
     */
    private function __construct(
        int $submitAttempt,
        string $billerName,
        string $mainTransactionState,
        array $mainTransaction,
        array $crossSales,
        ?array $paymentTemplate
    ) {
        $this->billerName = $billerName;
        $this->initSuccess($mainTransactionState);
        $this->initSubmitAttempt($submitAttempt);
        $this->initTransactions($mainTransaction, $crossSales);
        $this->initPaymentMethodUsed($paymentTemplate);
        $this->initDefaultBiller();
        $this->initConfiguredAllowedAttempts();
    }

    /**
     * @param int        $submitAttempt        Submit attempt
     * @param string     $billerName           Biller name
     * @param string     $mainTransactionState Main Transaction state
     * @param array      $mainTransaction      Main transaction
     * @param array      $crossSales           Cross sales
     * @param null|array $paymentTemplate      Payment Template
     * @return static
     */
    public static function create(
        int $submitAttempt,
        string $billerName,
        string $mainTransactionState,
        array $mainTransaction,
        array $crossSales,
        ?array $paymentTemplate
    ): self {
        return new static(
            $submitAttempt,
            $billerName,
            $mainTransactionState,
            $mainTransaction,
            $crossSales,
            $paymentTemplate
        );
    }

    /**
     * @return void
     */
    private function initConfiguredAllowedAttempts(): void
    {
        $this->configuredAllowedAttempts = self::ALLOWED_ATTEMPTS;
    }

    /**
     * @param array|null $paymentTemplate
     * @return void
     */
    private function initPaymentMethodUsed(?array $paymentTemplate): void
    {
        if (!empty($paymentTemplate)) {
            $this->existingPaymentTemplateUsed = true;
        }
    }

    /**
     * @return void
     */
    private function initDefaultBiller(): void
    {
        if ($this->submitAttempt > 1) {
            $this->defaultBiller = true;
        }
    }

    /**
     * @param array $mainTransaction Main transaction
     * @param array $crossSales      Cross sales
     * @return void
     */
    private function initTransactions(
        array $mainTransaction,
        array $crossSales
    ): void {
        $this->addTransactions($mainTransaction, false);

        /** @var InitializedItem $crossSale */
        foreach ($crossSales as $crossSale) {
            $this->addTransactions($crossSale->transactionCollection()->toArray(), true);
        }
    }

    /**
     * @param array $transactions Array of transactions
     * @param bool  $isCrossSale  Is the transaction cross sale
     * @return void
     */
    private function addTransactions(
        array $transactions,
        bool $isCrossSale
    ): void {
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            if ($transaction->billerName() !== $this->billerName || $transaction->isPending()) {
                continue;
            }
            $routingCode = $transaction->successfulBinRouting() ? $transaction->successfulBinRouting()
                ->routingCode() : null;

            $this->transactions[] = [
                'transactionId' => (string) $transaction->transactionId(),
                'routingCode'   => $routingCode,
                'isCrossSale'   => $isCrossSale,
                'success'       => $transaction->isApproved(),
                'isNsf'         => $transaction->isNsf()
            ];
        }
    }

    /**
     * @param string $mainTransactionState Main transaction state
     * @return void
     */
    private function initSuccess(string $mainTransactionState): void
    {
        if ($mainTransactionState !== Transaction::STATUS_APPROVED) {
            $this->success = false;

            return;
        }

        $this->success = true;
    }

    /**
     * @param int $submitAttempt Submit attempt
     * @return void
     */
    private function initSubmitAttempt(int $submitAttempt): void
    {
        $this->submitAttempt = $submitAttempt;

        if ($this->success !== true) {
            $this->submitAttempt++;
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'submitAttempt'               => $this->submitAttempt,
            'billerName'                  => $this->billerName,
            'success'                     => $this->success,
            'defaultBiller'               => $this->defaultBiller,
            'existingPaymentTemplateUsed' => $this->existingPaymentTemplateUsed,
            'configuredAllowedAttempts'   => $this->configuredAllowedAttempts,
            'transactions'                => $this->transactions,
        ];
    }
}
