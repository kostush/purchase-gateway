<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

interface ThirdPartyRedirectDTOAssembler
{
    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param Site|null       $site            Site
     * @return ThirdPartyRedirectHttpDTO
     */
    public function assemble(PurchaseProcess $purchaseProcess, ?Site $site = null): ThirdPartyRedirectHttpDTO;
}
