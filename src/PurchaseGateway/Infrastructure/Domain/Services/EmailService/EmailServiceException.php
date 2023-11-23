<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\Projection\Domain\Exceptions\TransientException;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;

class EmailServiceException extends ServiceException implements TransientException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::EMAIL_SERVICE_EXCEPTION;
}
