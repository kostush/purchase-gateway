<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

interface ReturnDTOAssembler
{
    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param Site            $site            Site
     * @return ReturnHttpDTO
     */
    public function assemble(
        PurchaseProcess $purchaseProcess,
        Site $site
    ): ReturnHttpDTO;
}
