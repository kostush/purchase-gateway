<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class InvalidCommandException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_COMMAND;

    /**
     * InvalidCommandException constructor.
     *
     * @param string  $expecting Expected $query class name
     * @param Command $command   Command given
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $expecting, $command)
    {
        parent::__construct(null, $expecting, get_class($command));
    }
}
