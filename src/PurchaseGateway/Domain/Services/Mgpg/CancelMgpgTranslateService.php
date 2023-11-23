<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\SubsequentOperations\Cancel\OtherData;
use Ramsey\Uuid\Uuid;
use ProbillerMGPG\SubsequentOperations\Cancel\CancelRequest;
use ProbillerMGPG\SubsequentOperations\Cancel\CancelResponse;
use ProbillerMGPG\SubsequentOperations\Cancel\Invoice as InvoiceCancel;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\CancelRebillRequest;

class CancelMgpgTranslateService
{

    /**
     * @var RetrieveTransactionIdService
     */
    private $retrieveTransactionIdService;

    /**
     * InitPurchaseNgToMgpgService constructor.
     * @param RetrieveTransactionIdService $retrieveTransactionIdService
     */
    public function __construct(
        RetrieveTransactionIdService $retrieveTransactionIdService
    ) {
        $this->retrieveTransactionIdService = $retrieveTransactionIdService;
    }

    /**
     * @param CancelRebillRequest $request NG CancelRebill Request
     *
     * @return CancelRequest
     * @throws \Exception
     */
    public function translateCancelTo(CancelRebillRequest $request): CancelRequest
    {
        $cancelPayload = $request->toArray();
        $transactionId = $this->retrieveTransactionIdService->findByItemIdOrReturnItemId(($cancelPayload['itemId']));

        return new CancelRequest(
            $cancelPayload['businessGroupId'],
            new InvoiceCancel(
                Uuid::uuid4()->toString(),
                $cancelPayload['memberId'],
                $request->getUsingMemberProfile(),
                $transactionId,
                [],
                $cancelPayload['siteId'],
                new OtherData(),
                $request->getCancellationReason()
            )
        );
    }

    /**
     * @param CancelResponse $response
     *
     * @return array
     */
    public function translateCancelFrom(CancelResponse $response): array
    {
        $invoice = $response->invoice;
        return [
            'transactionId'     => ($invoice != null) ? $invoice->transactionId : "",
            'bundleOperationId' => ($invoice != null) ? $invoice->invoiceId : "",
            'sessionId'         => $response->sessionId,
            'correlationId'     => $response->correlationId,
            'nextAction'        => $response->nextAction,
            'invoice'           => $invoice != null ? $invoice->toArray(): []
        ];
    }
}