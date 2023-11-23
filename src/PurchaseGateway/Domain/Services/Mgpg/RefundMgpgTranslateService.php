<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Url;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Ramsey\Uuid\Uuid;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProbillerMGPG\SubsequentOperations\Refund\Invoice;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\RefundRequest;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProbillerMGPG\SubsequentOperations\Refund\RefundRequest as MgpgSdkRefundRequest;

/**
 * Class RefundMgpgTranslateService
 * @package ProBillerNG\PurchaseGateway\Domain\Services\Mgpg
 */
class RefundMgpgTranslateService
{
    public const USE_MEMBER_PROFILE = false;

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
     * @param RefundRequest $refundRequest
     *
     * @return MgpgSdkRefundRequest
     * @throws Exception
     */
    public function translateTo(RefundRequest $refundRequest): MgpgSdkRefundRequest
    {
        $correlationId = $refundRequest->headers->get('X-CORRELATION-ID', Uuid::uuid4()->toString());

        $refundPayload = $refundRequest->toArray();
        $transactionId = $this->retrieveTransactionIdService->findByItemIdOrReturnItemId(($refundPayload['itemId']));

        $amount = $refundPayload['amount'] ?? null;

        $invoice = new Invoice(
            Uuid::uuid4()->toString(),
            $refundPayload['memberId'],
            self::USE_MEMBER_PROFILE,
            $transactionId,
            (float) $amount,
            $refundPayload['reason'],
            [],
            $refundPayload['siteId'],
            null
        );

        return new MgpgSdkRefundRequest(
            $refundPayload['businessGroupId'],
            $invoice
        );
    }
}
