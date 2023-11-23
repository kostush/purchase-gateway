<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use ProbillerNG\MemberProfileGatewayClient\Model\RetrieveMemberProfilePayload;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveMemberProfileAdapter;

class RetrieveMemberProfileServiceAdapter implements RetrieveMemberProfileAdapter
{
    /**
     * @var MemberProfileGatewayClient
     */
    protected $client;

    /**
     * @var MemberProfileTranslator
     */
    protected $translator;

    /**
     * RetrieveMemberProfileServiceAdapter constructor.
     * @param MemberProfileGatewayClient $client     Client
     * @param MemberProfileTranslator    $translator Translator
     */
    public function __construct(
        MemberProfileGatewayClient $client,
        MemberProfileTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }

    /**
     * @param string      $memberId       Payment Template Id
     * @param string      $siteId         Site Id Id
     * @param string      $publicKey      Public Key
     * @param string      $sessionId      Session Id
     * @param string|null $subscriptionId Subscription Id
     * @param string|null $entrySiteId    Entry Site id
     * @return MemberInfo
     * @throws Exceptions\MemberProfileGatewayErrorException
     * @throws Exceptions\MemberProfileNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     */
    public function retrieveMemberProfile(
        string $memberId,
        string $siteId,
        string $publicKey,
        string $sessionId,
        ?string $subscriptionId,
        ?string $entrySiteId
    ): MemberInfo {

        $payload = new RetrieveMemberProfilePayload();
        $payload->setSiteId($siteId);
        $payload->setSessionId($sessionId);

        $result = $this->client->retrieveMemberProfile($memberId, $publicKey, $payload);

        return $this->translator->translateRetrieveMemberInfo($memberId, $result, $subscriptionId, $entrySiteId);
    }
}
