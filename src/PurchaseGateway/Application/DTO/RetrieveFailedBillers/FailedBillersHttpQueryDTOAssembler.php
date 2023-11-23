<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers;

use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;

class FailedBillersHttpQueryDTOAssembler implements FailedBillersDTOAssembler
{

    /**
     * @param FailedBillers $failedBillers Failed Billers VO
     * @param bool          $wasThreeDUsed Was 3DS used?
     * @return mixed
     */
    public function assemble(FailedBillers $failedBillers, bool $wasThreeDUsed)
    {
        return new FailedBillersQueryHttpDTO($failedBillers, $wasThreeDUsed);
    }
}
