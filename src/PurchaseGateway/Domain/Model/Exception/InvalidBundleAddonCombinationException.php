<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidBundleAddonCombinationException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_BUNDLE_ADDON_COMBINATION;

    /**
     * InvalidCreditCardExpirationDate constructor.
     * @param string          $bundleId The invalid field
     * @param string          $addonId  The invalid field
     * @param \Throwable|null $previous Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $bundleId, string $addonId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, 'BundleId: ' . $bundleId, 'AddonId: ' . $addonId);
    }
}
