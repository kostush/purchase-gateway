<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class RepositoryException extends Exception
{
    protected $code = Code::REPOSITORY_EXCEPTION;

    /**
     * RepositoryException constructor.
     * @param string|null     $message  Message
     * @param int|null        $code     Code
     * @param \Throwable|null $previous Previous exception (if any)
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(?string $message = null, ?int $code = null, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $message, $code);
    }
}
