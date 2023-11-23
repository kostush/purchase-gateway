<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;

interface RetrieveMemberProfileAdapter
{
    /**
     * @param string      $memberId       Member Id
     * @param string      $siteId         Site Id
     * @param string      $publicKey      Public Key
     * @param string      $sessionId      Session Id
     * @param string|null $subscriptionId Subscription Id
     * @param string|null $entrySiteId    Entry Site Id
     * @return MemberInfo
     */
    public function retrieveMemberProfile(
        string $memberId,
        string $siteId,
        string $publicKey,
        string $sessionId,
        ?string $subscriptionId,
        ?string $entrySiteId
    ): MemberInfo;
}
