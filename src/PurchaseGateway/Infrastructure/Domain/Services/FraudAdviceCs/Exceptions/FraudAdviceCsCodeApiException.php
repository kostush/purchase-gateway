<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions;

use ProBillerNG\PurchaseGateway\Code;

class FraudAdviceCsCodeApiException extends FraudAdviceCsException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::FRAUD_SERVICE_CS_CODE_API_EXCEPTION;
}
