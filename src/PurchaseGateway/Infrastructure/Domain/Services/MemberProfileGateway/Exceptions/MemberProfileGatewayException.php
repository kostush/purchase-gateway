<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions;

use ProBillerNG\Projection\Domain\Exceptions\TransientException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;

class MemberProfileGatewayException extends ServiceException implements TransientException
{
}
