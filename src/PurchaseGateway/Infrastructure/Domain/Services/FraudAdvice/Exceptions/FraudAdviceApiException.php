<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceException;

class FraudAdviceApiException extends FraudAdviceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::FRAUD_ADVICE_SERVICE_API_SUPPORTED;

    /**
     * FraudAdviceApiException constructor.
     * @param string $message Message
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $message)
    {
        parent::__construct(null, $message);
    }
}
