<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

/**
 * Class ServicesList
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 */
class ServicesList
{
    public const FRAUD         = 'fraud';
    public const BIN_ROUTING   = 'bin-routing';
    public const EMAIL_SERVICE = 'email-service';
}
