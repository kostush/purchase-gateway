<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings;

use Throwable;

class EmailSettingsException extends \Exception
{
    /**
     * EmailSettingsException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}