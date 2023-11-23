<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceException;

class CouldNotSendEmailException extends EmailServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::EMAIL_SERVICE_COULD_NOT_SEND_EMAIL;
}
