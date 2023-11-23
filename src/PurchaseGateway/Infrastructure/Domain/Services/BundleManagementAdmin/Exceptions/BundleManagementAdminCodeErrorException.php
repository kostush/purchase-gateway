<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\Exceptions;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ServiceException;
use ProBillerNG\PurchaseGateway\Code;

class BundleManagementAdminCodeErrorException extends ServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::BUNDLE_MANAGEMENT_ADMIN_CODE_ERROR_EXCEPTION;
}
