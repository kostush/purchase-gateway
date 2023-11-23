<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback;

use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;

interface ThirdPartyPostbackCommandHandlerFactory
{
    public const CHARGE = 'charge';
    public const REBILL = 'rebill';

    /**
     * @param string $postbackType Postback type.
     * @return BasePaymentProcessCommandHandler
     */
    public function getHandler(string $postbackType): BasePaymentProcessCommandHandler;
}
