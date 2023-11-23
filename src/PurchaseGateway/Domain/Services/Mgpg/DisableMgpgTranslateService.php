<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Ramsey\Uuid\Uuid;
use ProBillerNG\Logger\Exception as LoggerException;
use ProbillerMGPG\SubsequentOperations\Disable\DisableRequest;
use ProbillerMGPG\SubsequentOperations\Disable\DisableResponse;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\DisableAccessRequest;
use ProbillerMGPG\SubsequentOperations\Disable\Invoice as InvoiceDisable;

class DisableMgpgTranslateService
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
     * @param DisableAccessRequest $request NG Disable Request
     *
     * @return DisableRequest
     * @throws LoggerException
     * @throws \Exception
     */
    public function translateDisableTo(DisableAccessRequest $request): DisableRequest
    {
        $disablePayload = $request->toArray();
        $transactionId = $this->retrieveTransactionIdService->findByItemIdOrReturnItemId(($disablePayload['itemId']));

        return new DisableRequest(
            $disablePayload['businessGroupId'],
            new InvoiceDisable(
                Uuid::uuid4()->toString(),
                $disablePayload['memberId'],
                $request->getUsingMemberProfile(),
                $transactionId,
                [],
                $disablePayload['siteId']
            )
        );
    }

    /**
     * @param DisableResponse $response
     *
     * @return array
     */
    public function translateDisableFrom(DisableResponse $response): array
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