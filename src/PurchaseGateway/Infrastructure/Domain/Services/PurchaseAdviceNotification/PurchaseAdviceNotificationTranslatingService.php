<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Services\AdviceNotificationAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseAdviceNotificationService;

/**
 * @deprecated use EmailSettingsService instead
 * Class PurchaseAdviceNotificationTranslatingService
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification
 */
class PurchaseAdviceNotificationTranslatingService implements PurchaseAdviceNotificationService
{
    /** @var AdviceNotificationAdapter */
    private $adviceAdapter;

    /**
     * PurchaseAdviceNotificationTranslatingService constructor.
     * @param AdviceNotificationAdapter $adviceAdapter Advice Notification Adapter.
     */
    public function __construct(AdviceNotificationAdapter $adviceAdapter)
    {
        $this->adviceAdapter = $adviceAdapter;
    }

    /**
     * @deprecated use EmailSettingService retrieveEmailSettings method instead
     * @param string $siteId        Site Id.
     * @param string $taxType       Tax Type.
     * @param string $sessionId     SessionId
     * @param string $billerName    Biller Name.
     * @param string $memberType    MemberType
     * @param string $transactionId Transaction Id
     * @return bool
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

        Log::info(
            'PANS - Calling service',
            [
                'siteId'        => $siteId,
                'taxType'       => $taxType,
                'sessionId'     => $sessionId,
                'billerName'    => $billerName,
                'memberType'    => $memberType,
                'transactionId' => $transactionId
            ]
        );

        return $this->adviceAdapter->getAdvice(
            $siteId,
            $taxType,
            $sessionId,
            $billerName,
            $memberType,
            $transactionId
        );
    }
}
