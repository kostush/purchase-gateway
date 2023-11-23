<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\Base\Application\Services\Query;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class InvalidQueryException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_QUERY;

    /**
     * InvalidCommandException constructor.
     *
     * @param string $expecting Expected $query class name
     * @param Query  $query     Query given
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $expecting, $query)
    {
        parent::__construct(null, $expecting, get_class($query));
    }
}
