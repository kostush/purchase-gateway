<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception;

use ProBillerNG\PurchaseGateway\Code;

class CreateSiteException extends ConfigServiceException
{
    protected $code = Code::CONFIG_SERVICE_CREATE_SITE_EXCEPTION;

    public function __construct(string $message)
    {
        parent::__construct(null, $message);
    }
}