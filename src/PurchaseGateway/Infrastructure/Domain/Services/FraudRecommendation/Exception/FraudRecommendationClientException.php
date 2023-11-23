<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;

class FraudRecommendationClientException extends ServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::FRAUD_SERVICE_CS_CLIENT_EXCEPTION;
}