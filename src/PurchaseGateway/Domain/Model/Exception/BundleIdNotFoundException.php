<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class BundleIdNotFoundException extends NotFoundException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::BUNDLE_NOT_FOUND;

    /**
     * InvalidCreditCardExpirationDate constructor.
     * @param string          $bundleId The invalid field
     * @param \Throwable|null $previous Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $bundleId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, 'BundleId: ' . $bundleId);
    }
}
