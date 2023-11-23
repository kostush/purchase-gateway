<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions;

use ProBillerNG\PurchaseGateway\Code;

class MemberProfileGatewayTypeException extends MemberProfileGatewayException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::MEMBER_PROFILE_GATEWAY_TYPE_EXCEPTION;
}
