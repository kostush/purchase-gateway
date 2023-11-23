<?php

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class TransactionAlreadyProcessedException extends InvalidPayloadException
{
    /** @var int $code Error code */
    protected $code = Code::TRANSACTION_ALREADY_PROCESSED_EXCEPTION;

    /**
     * TransactionAlreadyProcessedException constructor.
     * @param array  $nextAction NextAction array
     * @param string $returnUrl  Return url to client
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(array $nextAction, string $returnUrl)
    {
        parent::__construct("", $nextAction, $returnUrl);
    }
}
