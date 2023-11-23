<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers;

use ProBillerNG\PurchaseGateway\Application\DTO\QueryHttpDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;

class FailedBillersQueryHttpDTO extends QueryHttpDTO
{
    /**
     * @var array
     */
    protected $failedBillers;

    /**
     * @var bool
     */
    protected $wasThreeDUsed;

    /**
     * BillerTransactionQueryHttpDTO constructor.
     * @param FailedBillers $failedBillers The failed billers vo
     * @param bool          $wasThreeDUsed Was 3DS used?
     */
    public function __construct(FailedBillers $failedBillers, bool $wasThreeDUsed)
    {
        $this->failedBillers = $failedBillers->toArray();
        $this->wasThreeDUsed = $wasThreeDUsed;
    }

    /**
     * @return false|mixed|string
     */
    public function jsonSerialize()
    {
        return [
            'was3DSUsed'    => $this->wasThreeDUsed,
            'failedBillers' => $this->failedBillers,
        ];
    }
}
