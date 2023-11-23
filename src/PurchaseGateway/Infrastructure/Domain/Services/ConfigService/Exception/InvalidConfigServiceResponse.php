<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidConfigServiceResponse extends ConfigServiceException
{
    protected $code = Code::CONFIG_SERVICE_RESPONSE_EXCEPTION;

    /**
     * InvalidConfigServiceResponse constructor.
     *
     * @param string $message
     *
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $message)
    {
        parent::__construct(null, $message);
    }
}