<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;

class CascadeTranslatorException extends ServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::CASCADE_TRANSLATOR_EXCEPTION;
}
