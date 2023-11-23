<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\Projection\Domain\Exceptions\TransientException;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class RepositoryConnectionException extends Exception implements TransientException
{
    protected $code = Code::REPOSITORY_CONNECTION_EXCEPTION;
}
