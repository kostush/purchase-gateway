<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers;

use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;

interface FailedBillersDTOAssembler
{
    /**
     * @param FailedBillers $failedBillers Failed bilellers VO
     * @param bool          $wasThreeDUsed Was 3DS used?
     * @return mixed
     */
    public function assemble(FailedBillers $failedBillers, bool $wasThreeDUsed);
}
