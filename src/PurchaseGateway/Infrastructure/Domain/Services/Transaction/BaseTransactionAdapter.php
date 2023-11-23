<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

abstract class BaseTransactionAdapter
{
    /**
     * @var TransactionServiceClient
     */
    protected $client;

    /**
     * @var TransactionTranslator
     */
    protected $translator;

    /**
     * BaseTransactionAdapter constructor.
     * @param TransactionServiceClient $client     Client
     * @param TransactionTranslator    $translator Translator
     */
    public function __construct(
        TransactionServiceClient $client,
        TransactionTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }
}
