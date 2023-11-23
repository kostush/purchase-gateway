<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveMemberProfileAdapter;

class MemberProfileGatewayTranslatingService implements MemberProfileGatewayService
{
    /**
     * @var RetrieveMemberProfileAdapter
     */
    private $retrieveMemberProfileAdapter;

    /**
     * MemberProfileGatewayTranslatingService constructor.
     * @param RetrieveMemberProfileAdapter $retrieveMemberProfileAdapter RetrieveMemberProfileAdapter
     */
    public function __construct(RetrieveMemberProfileAdapter $retrieveMemberProfileAdapter)
    {
        $this->retrieveMemberProfileAdapter = $retrieveMemberProfileAdapter;
    }

    /**
     * @param string      $memberId       Member Id
     * @param string      $siteId         Site Id
     * @param string      $publicKey      Public Key
     * @param string      $sessionId      Session Id
     * @param string|null $subscriptionId Subscription Id
     * @param string|null $entrySiteId    Entry Site Id
     * @return MemberInfo
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveMemberProfile(
        string $memberId,
        string $siteId,
        string $publicKey,
        string $sessionId,
        ?string $subscriptionId,
        ?string $entrySiteId = null
    ): MemberInfo {
        try {
            return $this->retrieveMemberProfileAdapter->retrieveMemberProfile(
                $memberId,
                $siteId,
                $publicKey,
                $sessionId,
                $subscriptionId,
                $entrySiteId
            );
        } catch (RuntimeException $e) {
            Log::error('Error contacting Member profile gateway');
            throw $e;
        }
    }
}
