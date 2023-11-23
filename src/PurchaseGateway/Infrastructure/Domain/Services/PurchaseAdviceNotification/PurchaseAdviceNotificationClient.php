<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification;

use ProbillerNG\PurchaseAdviceNotificationServiceClient\Api\PurchaseAdviceNotificationApi;
use ProbillerNG\PurchaseAdviceNotificationServiceClient\Model\PurchaseAdviceNotificationPayload;

class PurchaseAdviceNotificationClient
{
    /**
     * @var PurchaseAdviceNotificationApi
     */
    private $purchaseAdviceNotificationApi;

    /**
     * PurchaseNotificationAdviceClient constructor.
     * @param PurchaseAdviceNotificationApi $purchaseAdviceNotificationApi Purchase Advice Notification API.
     */
    public function __construct(PurchaseAdviceNotificationApi $purchaseAdviceNotificationApi)
    {
        $this->purchaseAdviceNotificationApi = $purchaseAdviceNotificationApi;
    }

    /**
     * @param string                            $sessionId                 Session Id.
     * @param PurchaseAdviceNotificationPayload $adviceNotificationPayload Purchase Advice Notification Payload.
     *
     * @return \ProbillerNG\PurchaseAdviceNotificationServiceClient\Model\Error|\ProbillerNG\PurchaseAdviceNotificationServiceClient\Model\InlineResponse200
     * @throws \ProbillerNG\PurchaseAdviceNotificationServiceClient\ApiException
     */
    public function getAdvice(string $sessionId, PurchaseAdviceNotificationPayload $adviceNotificationPayload)
    {
        return $this->purchaseAdviceNotificationApi->apiV1AdviceSessionSessionIdGet(
            $sessionId,
            $adviceNotificationPayload
        );
    }
}
