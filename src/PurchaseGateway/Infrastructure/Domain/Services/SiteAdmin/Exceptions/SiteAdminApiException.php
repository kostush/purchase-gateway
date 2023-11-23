<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;

class SiteAdminApiException extends ServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::SITE_ADMIN_API_EXCEPTION;
}
