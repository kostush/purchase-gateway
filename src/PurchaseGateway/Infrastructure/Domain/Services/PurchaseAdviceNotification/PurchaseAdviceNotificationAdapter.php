<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification;

use ProBillerNG\Logger\Log;
use ProbillerNG\PurchaseAdviceNotificationServiceClient\ApiException;
use ProbillerNG\PurchaseAdviceNotificationServiceClient\Model\PurchaseAdviceNotificationPayload;
use ProBillerNG\PurchaseGateway\Domain\Services\AdviceNotificationAdapter;

class PurchaseAdviceNotificationAdapter implements AdviceNotificationAdapter
{
    /**
     * @var PurchaseAdviceNotificationClient
     */
    private $client;

    /**
     * PurchaseNotificationAdviceAdapter constructor.
     * @param PurchaseAdviceNotificationClient $client Purchase Notification Advice Client.
     */
    public function __construct(
        PurchaseAdviceNotificationClient $client
    ) {
        $this->client = $client;
    }

    /**
     * @param string $siteId        Site ID.
     * @param string $taxType       Tax Type.
     * @param string $sessionId     Session Id.
     * @param string $billerName    Biller Name.
     * @param string $memberType    Member Type.
     * @param string $transactionId Transaction Id.
     * @return bool
     * @throws ApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function getAdvice(
        string $siteId,
        string $taxType,
        string $sessionId,
        string $billerName,
        string $memberType,
        string $transactionId
    ): bool {
        try {
            $advicePayload = new PurchaseAdviceNotificationPayload();
            $advicePayload->setTaxType($taxType);
            $advicePayload->setTransactionId($transactionId);
            $advicePayload->setBillerName($billerName);
            $advicePayload->setMemberType($memberType);
            $advicePayload->setSiteId($siteId);

            $response = $this->client->getAdvice(
                $sessionId,
                $advicePayload
            );
            Log::info(
                'PANS RESPONSE',
                ['response' => (string) $response]
            );

            return $response->getNotify();

        } catch (ApiException $apiException) {
            Log::error(
                'PANS - Exception',
                ['message' => $apiException->getMessage(), 'code' => $apiException->getCode()]
            );
            throw $apiException;
        }
    }
}
