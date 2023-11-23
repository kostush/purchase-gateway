<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Complete\Mgpg;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

interface CompleteThreeDDTOAssembler
{
    /**
     * @param PurchaseProcess $result Complete result
     * @param Site|null       $site   Site
     * @return CompleteThreeDHttpDTO
     */
    public function assemble($result, ?Site $site): CompleteThreeDHttpDTO;
}
