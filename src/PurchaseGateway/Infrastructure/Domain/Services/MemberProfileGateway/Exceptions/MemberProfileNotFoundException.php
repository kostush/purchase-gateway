<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;
use ProBillerNG\Logger\Exception as LoggerException;

class MemberProfileNotFoundException extends ServiceException
{
    protected $code = Code::MEMBER_PROFILE_GATEWAY_NOT_FOUND_EXCEPTION;

    /**
     * MemberProfileNotFoundException constructor.
     * @param string          $memberId Member Id
     * @param \Throwable|null $previous Previous exception
     * @throws LoggerException
     */
    public function __construct(string $memberId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $memberId);
    }
}

