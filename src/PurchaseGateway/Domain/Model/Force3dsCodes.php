<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use MyCLabs\Enum\Enum;

/**
 * Class Force3dsCodes
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 */
class Force3dsCodes extends Enum
{
    public const FORCE_3DS                             = 300;
    public const FORCE_3DS_DECLINE_COUNT_VELOCITY      = 311;
    public const FORCE_3DS_NONRECURRING_COUNT_VELOCITY = 312;
    public const FORCE_3DS_SIGN_UP_ALLOWANCE_VELOCITY  = 313;
    public const FORCE_3DS_NAME_COUNT_VELOCITY         = 314;
    public const FORCE_3DS_CREDITCARD_COUNT_VELOCITY   = 315;
    public const FORCE_3DS_UNIQUEIP_COUNT_VELOCITY     = 316;
    public const FORCE_3DS_ZIP_COUNT_VELOCITY          = 317;
}
