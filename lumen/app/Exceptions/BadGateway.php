<?php
declare(strict_types=1);

namespace App\Exceptions;

use ProBillerNG\PurchaseGateway\Code;

class BadGateway extends \Exception
{
    public $code = Code::BAD_GATEWAY;

    /**
     * @param string|null $message Message
     */
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }
}
