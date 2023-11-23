<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

/**
 * Class CrossSaleSiteNotExistException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CrossSaleSiteNotExistException extends ValidationException
{
    protected $code = Code::CROSS_SALE_SITE_NOT_EXIST_EXCEPTION;

    /**
     * CrossSaleSiteNotExistException constructor.
     * @param string          $parameter Parameter.
     * @param \Throwable|null $previous  Previous.
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $parameter, \Throwable $previous = null)
    {
        parent::__construct($previous, $parameter);
    }
}
