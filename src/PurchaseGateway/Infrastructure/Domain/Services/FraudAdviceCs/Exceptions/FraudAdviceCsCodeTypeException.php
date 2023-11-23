<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions;

use ProBillerNG\PurchaseGateway\Code;

/**
 * @deprecated
 * Class FraudAdviceCsCodeTypeException
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions
 */
class FraudAdviceCsCodeTypeException extends FraudAdviceCsException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::FRAUD_SERVICE_CS_CODE_TYPE_EXCEPTION;
}
