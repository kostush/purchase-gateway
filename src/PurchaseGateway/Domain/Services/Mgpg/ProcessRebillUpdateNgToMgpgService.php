<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\SubsequentOperations\Process\Payment;
use ProbillerMGPG\SubsequentOperations\Process\ProcessRequest;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\ProcessRebillUpdateRequest;

class ProcessRebillUpdateNgToMgpgService
{
    /**
     * @var RebillUpdatePaymentInformationFactory
     */
    private $rebillUpdatePaymentInformationFactory;

    /**
     * ProcessPurchaseController constructor.
     * @param RebillUpdatePaymentInformationFactory $paymentInformationFactory
     */
    public function __construct(
        RebillUpdatePaymentInformationFactory $paymentInformationFactory
    ) {
        $this->rebillUpdatePaymentInformationFactory = $paymentInformationFactory;
    }

    /**
     * @param ProcessRebillUpdateRequest $ngRequest The NG Input Payload
     * @return ProcessRequest
     * @throws Exception
     */
    public function translate(
        ProcessRebillUpdateRequest $ngRequest
    ): ProcessRequest {
        return new ProcessRequest(
            new Payment($this->rebillUpdatePaymentInformationFactory->create($ngRequest))
        );
    }
}
