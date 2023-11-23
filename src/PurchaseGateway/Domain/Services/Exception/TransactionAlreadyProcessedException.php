<?php

declare(strict_types=1);
namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;


use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Domain\Returns400Code;

class TransactionAlreadyProcessedException extends Exception implements Returns400Code
{
    protected $code = Code::TRANSACTION_ALREADY_PROCESSED_EXCEPTION;
}