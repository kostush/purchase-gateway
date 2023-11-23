<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Lookup;

use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

interface LookupThreeDDTOAssembler
{
    /**
     * @param GenericPurchaseProcess $result Lookup result
     * @param Site|null              $site   Site
     * @return LookupThreeDHttpDTO
     */
    public function assemble($result, ?Site $site): LookupThreeDHttpDTO;
}