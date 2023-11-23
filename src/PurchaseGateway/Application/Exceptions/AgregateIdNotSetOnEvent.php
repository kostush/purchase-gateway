<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class AgregateIdNotSetOnEvent extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::AGGREGATE_ID_NOT_SET_ON_EVENT;

    /**
     * InvalidAmountException constructor.
     * @param string          $parameter Parameter
     * @param \Throwable|null $previous  Previous Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $parameter, \Throwable $previous = null)
    {
        parent::__construct($previous, $parameter);
    }
}
