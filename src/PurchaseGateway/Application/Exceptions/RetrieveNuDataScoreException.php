<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class RetrieveNuDataScoreException extends Exception
{
    protected $code = Code::RETRIEVE_NU_DATA_SCORE_EXCEPTION;

    /**
     * RetrieveNuDataScoreException constructor.
     * @param string          $messageType Message Type
     * @param \Throwable|null $previous    Previous Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $messageType, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $messageType);
    }
}